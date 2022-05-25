<?php

namespace D4rk0snet\NamingFileImport\Service;

use D4rk0snet\Adoption\Entity\AdoptionEntity;
use D4rk0snet\Adoption\Entity\GiftAdoption;
use D4rk0snet\Coralguardian\Enums\Language;
use D4rk0snet\Donation\Entity\DonationEntity;
use Hyperion\Doctrine\Service\DoctrineService;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

abstract class FileService
{
    public static function getExcelFilename(DonationEntity $adoptionEntity, string $filename) : string
    {
        $reader = new Xlsx();
        /** @var \PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet */
        $spreadSheet = $reader->load(__DIR__ . "/../Files/$filename");

        $url = $adoptionEntity->getLang() === Language::FR ? "/entreprise" : "/company";
        $url = home_url("$url?orderId={$adoptionEntity->getUuid()}&step=adoption");

        $worksheet = $spreadSheet->getActiveSheet();
        $worksheet->getCell('B4')->setValue("Lien de dépose");
        $worksheet->getCell("B4")->getHyperlink()->setUrl($url);
        $worksheet->getCell('B5')->setValue($adoptionEntity->getUuid());

        for ($index = 0; $index <$adoptionEntity->getQuantity(); $index++) {
            $rowIndex = $index + 8;
            $worksheet->getCell('A' . $rowIndex)->setValue($index + 1);
        }
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadSheet);
        $filename = tempnam("/tmp", "") . ".xlsx";
        $writer->save($filename);

        return $filename;
    }

    public static function getAdoptionEntity(string $filename) : DonationEntity
    {
        $reader = new Xlsx();
        $spreadsheet = $reader->load($filename);
        $adoptionUUID = $spreadsheet->getSheet(0)->getCell('B5')->getValue();

        /** @var AdoptionEntity | null $adoptionEntity */
        $adoptionEntity = DoctrineService::getEntityManager()->getRepository(DonationEntity::class)->getByPostId($adoptionUUID);
        if (null === $adoptionEntity) {
            unlink($filename);
            throw new \Exception("Impossible de retrouver l'adoption ", 400);
        }

        if (count($adoptionEntity->getAdoptees()) === $adoptionEntity->getQuantity()) {
            unlink($filename);
            throw new \Exception("Les adoptions de cette commande ont déjà été réalisées", 400);
        }

        return $adoptionEntity;
    }

    public static function fillFileAccordingToAdoption(string $adoptionUuid)
    {
        /** @var DonationEntity | null $adoptionEntity */
        $adoptionEntity = DoctrineService::getEntityManager()->getRepository(GiftAdoption::class)->find($adoptionUuid);
        if ($adoptionEntity === null) {
            throw new \Exception("Adoption non trouvé");
        }
        if(static::class === NamingFileService::class) {
            $filename = $adoptionEntity->getLang() === Language::FR ? 'FR-coralguardian-coral-sheet-name.xlsx' : 'EN-coralguardian-coral-sheet-name.xlsx';
        } else if(static::class === RecipientFileService::class) {
            $filename = $adoptionEntity->getLang() === Language::FR ? 'FR-coralguardian-recipient-sheet-name.xlsx' : 'EN-coralguardian-recipient-sheet-name.xlsx';
        }

        return self::getExcelFilename($adoptionEntity, $filename);
    }
}