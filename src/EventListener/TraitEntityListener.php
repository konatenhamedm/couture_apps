<?php

namespace App\EventListener;

use App\Entity\TraitEntity;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;

/**
 * Listener global pour s'assurer que toutes les entités avec TraitEntity
 * ont leurs valeurs par défaut correctement définies
 */
#[AsEntityListener(event: Events::prePersist)]
#[AsEntityListener(event: Events::preUpdate)]
class TraitEntityListener
{
    public function prePersist(PrePersistEventArgs $args): void
    {
        $entity = $args->getObject();
        
        // Vérifier si l'entité utilise TraitEntity
        if ($this->usesTraitEntity($entity)) {
            $this->ensureDefaults($entity);
        }
    }
    
    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $entity = $args->getObject();
        
        // Vérifier si l'entité utilise TraitEntity
        if ($this->usesTraitEntity($entity)) {
            $this->ensureDefaults($entity);
        }
    }
    
    private function usesTraitEntity($entity): bool
    {
        $traits = class_uses_recursive(get_class($entity));
        return in_array(TraitEntity::class, $traits);
    }
    
    private function ensureDefaults($entity): void
    {
        // S'assurer que isActive est défini
        if (method_exists($entity, 'isActive') && method_exists($entity, 'setIsActive')) {
            try {
                // Forcer isActive à true si pas défini ou null
                $entity->setIsActive(true);
            } catch (\Exception $e) {
                // Ignorer les erreurs - l'entité pourrait avoir sa propre logique
            }
        }
        
        // S'assurer que createdAt est défini
        if (method_exists($entity, 'getCreatedAt') && method_exists($entity, 'setCreatedAtValue')) {
            if ($entity->getCreatedAt() === null) {
                try {
                    $entity->setCreatedAtValue();
                } catch (\Exception $e) {
                    // Ignorer les erreurs
                }
            }
        }
    }
}