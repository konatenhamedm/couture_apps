<?php

namespace App\Repository;

use App\Service\DynamicDatabaseService;
use Doctrine\ORM\EntityManagerInterface;

trait DynamicDatabaseTrait
{
    private ?DynamicDatabaseService $dynamicDb = null;

    public function setDynamicDatabaseService(DynamicDatabaseService $dynamicDb): void
    {
        $this->dynamicDb = $dynamicDb;
    }

    protected function getEntityManager(): EntityManagerInterface
    {
        if ($this->dynamicDb) {
            return $this->dynamicDb->getEntityManager();
        }
        
        // Fallback sur l'entity manager par défaut
        return parent::getEntityManager();
    }
}