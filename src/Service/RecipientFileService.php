<?php

namespace D4rk0snet\NamingFileImport\Service;

use D4rk0snet\Adoption\Entity\Friend;
use D4rk0snet\Adoption\Entity\GiftAdoption;
use D4rk0snet\Coralguardian\Event\GiftCodeSent;
use D4rk0snet\Coralguardian\Event\RecipientDone;
use D4rk0snet\GiftCode\Entity\GiftCodeEntity;
use Hyperion\Doctrine\Service\DoctrineService;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

class RecipientFileService extends FileService
{
    public static function importDataFromFile(string $filename, GiftAdoption $forceAdoptionEntity = null)
    {
        /** @var GiftAdoption $adoptionEntity */
        $adoptionEntity = $forceAdoptionEntity ?? self::getAdoptionEntity($filename);
        $reader = new Xlsx();
        $spreadsheet = $reader->load($filename);
        $lineIndex = 8;

        try {
            DoctrineService::getEntityManager()->beginTransaction();

            /** @var GiftCodeEntity $giftCode */
            foreach ($adoptionEntity->getGiftCodes() as $giftCode) {
                $friend = new Friend(
                    $spreadsheet->getSheet(0)->getCell('B' . $lineIndex)->getValue(),
                    $spreadsheet->getSheet(0)->getCell('C' . $lineIndex)->getValue(),
                    $spreadsheet->getSheet(0)->getCell('D' . $lineIndex)->getValue(),
                    $giftCode
                );
                $giftCode->setFriend($friend);
                DoctrineService::getEntityManager()->persist($friend);
                $lineIndex++;
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

        foreach ($adoptionEntity->getGiftCodes() as $giftCode) {
            GiftCodeSent::sendEvent($giftCode, 1);
        }

        RecipientDone::sendEvent($adoptionEntity);
    }
}