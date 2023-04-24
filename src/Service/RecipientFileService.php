<?php

namespace D4rk0snet\NamingFileImport\Service;

use D4rk0snet\Adoption\Entity\Friend;
use D4rk0snet\Adoption\Entity\GiftAdoption;
use D4rk0snet\Coralguardian\Event\GiftCodeSent;
use D4rk0snet\Coralguardian\Event\RecipientDone;
use D4rk0snet\Donation\Entity\DonationEntity;
use D4rk0snet\GiftCode\Entity\GiftCodeEntity;
use Hyperion\Doctrine\Service\DoctrineService;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

class RecipientFileService extends FileService
{
    public static function importDataFromFile(string $uuid, string $filename)
    {
        /** @var GiftAdoption $adoptionEntity */
        $adoptionEntity = DoctrineService::getEntityManager()->getRepository(DonationEntity::class)->find($uuid);
        if(!$adoptionEntity instanceof GiftAdoption || is_null($adoptionEntity)) {
            unlink($filename);
            throw new \Exception("Adoption non trouvé", 400);
        }

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