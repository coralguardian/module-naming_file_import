<?php

namespace D4rk0snet\NamingFileImport\Service;

use D4rk0snet\Adoption\Entity\AdoptionEntity;
use D4rk0snet\Coralguardian\Enums\Language;
use Hyperion\Doctrine\Service\DoctrineService;

class RecipientFileService
{
    public static function fillNamingFileAccordingToAdoption(string $adoptionUuid)
    {
        /** @var AdoptionEntity | null $adoptionEntity */
        $adoptionEntity = DoctrineService::getEntityManager()->getRepository(AdoptionEntity::class)->find($adoptionUuid);
        if ($adoptionEntity === null) {
            throw new \Exception("Adoption non trouvé");
        }

        $filename = $adoptionEntity->getLang() === Language::FR ? 'FR-coralguardian-coral-sheet-name.xlsx' : 'EN-coralguardian-coral-sheet-name.xlsx';


    }
}