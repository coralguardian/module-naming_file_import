<?php

namespace D4rk0snet\NamingFileImport\Service;

use D4rk0snet\Adoption\Entity\AdopteeEntity;
use D4rk0snet\Adoption\Entity\AdoptionEntity;
use D4rk0snet\Adoption\Enums\Seeder;
use D4rk0snet\Coralguardian\Enums\Language;
use Hyperion\Doctrine\Service\DoctrineService;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

class NamingFileService
{
    public static function fillNamingFileAccordingToAdoption(string $adoptionUuid) : string
    {
        /** @var AdoptionEntity | null $adoptionEntity */
        $adoptionEntity = DoctrineService::getEntityManager()->getRepository(AdoptionEntity::class)->find($adoptionUuid);
        if ($adoptionEntity === null) {
            throw new \Exception("Adoption non trouvé");
        }

        $filename = $adoptionEntity->getLang() === Language::FR ? 'FR-coralguardian-coral-sheet-name.xlsx' : 'EN-coralguardian-coral-sheet-name.xlsx';

        $reader = new Xlsx();
        /** @var \PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet */
        $spreadSheet = $reader->load(__DIR__ . "/../Files/$filename");

        $url = $adoptionEntity->getLang() === Language::FR ? "/entreprise" : "/company";
        $url = home_url("$url?orderId={$adoptionEntity->getUuid()}&step=adoption");

        $worksheet = $spreadSheet->getActiveSheet();
        $worksheet->getCell('B4')->setValue("Lien de dépose");
        $worksheet->getCell("B4")->getHyperlink()->setUrl($url);
        $worksheet->getCell('B5')->setValue($adoptionUuid);

        for ($index = 0; $index <$adoptionEntity->getQuantity(); $index++) {
            $rowIndex = $index + 8;
            $worksheet->getCell('A' . $rowIndex)->setValue($index + 1);
        }
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadSheet);
        $filename = tempnam("/tmp", "") . ".xlsx";
        $writer->save($filename);

        return $filename;
    }

    public static function importDataFromFile()
    {
        $filename = self::saveUploadedFile();
        $adoptionEntity = self::getAdoptionEntity($filename);
        $reader = new Xlsx();
        $spreadsheet = $reader->load($filename);
        $names = [];

        for ($i = 0; $i < $adoptionEntity->getQuantity(); $i++) {
            $index = $i + 8;
            $productName = $spreadsheet->getSheet(0)->getCell('B' . $index)->getValue();
            $names[] = $productName;
        }

        if (count($names) !== $adoptionEntity->getQuantity()) {
            unlink($filename);
            throw new \Exception("Le nombre de noms renseignés est incorrect", 400);
        }

        foreach ($names as $name) {
            $adopteeEntity = new AdopteeEntity(
                name: $name,
                seeder: Seeder::DULAH,
                adoption: $adoptionEntity,
                adopteeDatetime: new \DateTime()
            );

            DoctrineService::getEntityManager()->persist($adopteeEntity);
        }

        DoctrineService::getEntityManager()->flush();
    }

    private static function saveUploadedFile() : string
    {
        $filename = tempnam("/tmp", "") . ".xlsx";
        move_uploaded_file($_FILES['adoption_file']["tmp_name"], $filename);

        return $filename;
    }

    private static function getAdoptionEntity(string $filename) : AdoptionEntity
    {
        $reader = new Xlsx();
        $spreadsheet = $reader->load($filename);
        $adoptionUUID = $spreadsheet->getSheet(0)->getCell('B5')->getValue();

        /** @var AdoptionEntity | null $adoptionEntity */
        $adoptionEntity = DoctrineService::getEntityManager()->getRepository(AdoptionEntity::class)->getByPostId($adoptionUUID);
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
}
