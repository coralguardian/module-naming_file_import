<?php

namespace D4rk0snet\NamingFileImport\Service;

use D4rk0snet\Adoption\Entity\Friend;
use D4rk0snet\Adoption\Entity\GiftAdoption;
use Doctrine\Common\Collections\Collection;
use Hyperion\Doctrine\Service\DoctrineService;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

class RecipientFileService extends FileService
{
        public static function importDataFromFile(string $filename)
    {
        /** @var GiftAdoption $adoptionEntity */
        $adoptionEntity = self::getAdoptionEntity($filename);
        $reader = new Xlsx();
        $spreadsheet = $reader->load($filename);
        $lineIndex = 8;

        try {
            DoctrineService::getEntityManager()->beginTransaction();
            /** @var Collection $giftCodes */
            $giftCodes = $adoptionEntity->getGiftCodes();

            while ($spreadsheet->getSheet(0)->getCell('A' . $lineIndex)->getValue() !== "") {
                $friend = new Friend(
                    $spreadsheet->getSheet(0)->getCell('B' . $lineIndex)->getValue(),
                    $spreadsheet->getSheet(0)->getCell('C' . $lineIndex)->getValue(),
                    $spreadsheet->getSheet(0)->getCell('D' . $lineIndex)->getValue(),
                    $adoptionEntity,
                    $giftCodes->current()
                );
                DoctrineService::getEntityManager()->persist($friend);

                $lineIndex++;
                $giftCodes->next();
            }

            if ($lineIndex - 8 !== $adoptionEntity->getQuantity()) {
                unlink($filename);
                throw new \Exception("Le nombre de noms renseignés est incorrect", 400);
            }

            DoctrineService::getEntityManager()->flush();
            DoctrineService::getEntityManager()->commit();
        } catch (\Exception $exception) {
            DoctrineService::getEntityManager()->rollback();
            throw new $exception;
        }
    }
}