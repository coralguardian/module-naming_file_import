<?php

namespace D4rk0snet\NamingFileImport\Service;

use D4rk0snet\Adoption\Entity\AdopteeEntity;
use D4rk0snet\Adoption\Entity\AdoptionEntity;
use D4rk0snet\Adoption\Enums\AdoptedProduct;
use D4rk0snet\Adoption\Enums\Seeder;
use D4rk0snet\Coralguardian\Event\NamingDone;
use Hyperion\Doctrine\Service\DoctrineService;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

class NamingFileService extends FileService
{
    public static function importDataFromFile(string $filename, AdoptionEntity $forceAdoptionEntity = null)
    {
        $adoptionEntity = $forceAdoptionEntity ?? self::getAdoptionEntity($filename);

        if (count($adoptionEntity->getAdoptees()) === $adoptionEntity->getQuantity()) {
            unlink($filename);
            throw new \Exception("Les adoptions de cette commande ont déjà été réalisées", 400);
        }

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

        $seeders = Seeder::randomizeSeeder();
        $pictures = AdoptedProduct::getRandomizedProductImages($adoptionEntity->getAdoptedProduct());
        $seedersCount = count($seeders);
        $picturesCount = count($pictures);

        foreach ($names as $index => $name) {
            /** @var Seeder $selectedSeeder */
            $selectedSeeder = $seeders[$index % $seedersCount];
            $selectedPicture = $pictures[$index % $picturesCount];

            $adopteeEntity = new AdopteeEntity(
                name: $name,
                seeder: $selectedSeeder,
                adoption: $adoptionEntity,
                adopteeDatetime: new \DateTime(),
                picture: $selectedPicture
            );

            DoctrineService::getEntityManager()->persist($adopteeEntity);
        }

        DoctrineService::getEntityManager()->flush();
        NamingDone::sendEvent($adoptionEntity);
    }
}
