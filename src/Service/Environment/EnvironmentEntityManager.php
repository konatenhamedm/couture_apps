<?php

namespace App\Service\Environment;

use App\Service\EntityManagerProvider;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service pour gérer les entités dans le système à deux environnements
 */
class EnvironmentEntityManager implements EnvironmentEntityManagerInterface
{
    private EntityManagerProvider $entityManagerProvider;
    private LoggerInterface $logger;

    public function __construct(
        EntityManagerProvider $entityManagerProvider,
        LoggerInterface $logger
    ) {
        $this->entityManagerProvider = $entityManagerProvider;
        $this->logger = $logger;
    }

    public function ensureEntityIsManaged(?object $entity): ?object
    {
        if (!$entity) {
            return $entity;
        }

        try {
            $currentEM = $this->entityManagerProvider->getEntityManager();

            // Si l'entité est déjà gérée par l'EM actuel, la retourner
            if ($currentEM->contains($entity)) {
                return $entity;
            }

            // Si l'entité a un ID, la récupérer depuis la base
            if (method_exists($entity, 'getId') && $entity->getId()) {
                $entityClass = $this->getEntityClass($entity);
                $managedEntity = $currentEM->find($entityClass, $entity->getId());
                
                if ($managedEntity) {
                    $this->logger->debug('Entity reattached to current context', [
                        'entity_class' => $entityClass,
                        'entity_id' => $entity->getId()
                    ]);
                    return $managedEntity;
                }
            }

            // Si l'entité n'a pas d'ID, elle est nouvelle - la retourner telle quelle
            return $entity;
        } catch (\Exception $e) {
            $this->logger->error('Error ensuring entity is managed', [
                'entity_class' => get_class($entity),
                'error' => $e->getMessage()
            ]);
            return $entity;
        }
    }

    public function refreshEntityInCurrentContext(?object $entity): ?object
    {
        if (!$entity) {
            return $entity;
        }

        try {
            $currentEM = $this->entityManagerProvider->getEntityManager();

            // Si l'entité n'a pas d'ID, on ne peut pas la rafraîchir
            if (!method_exists($entity, 'getId') || !$entity->getId()) {
                return $entity;
            }

            // Si l'entité est gérée, la rafraîchir
            if ($currentEM->contains($entity)) {
                $currentEM->refresh($entity);
                $this->logger->debug('Entity refreshed in current context', [
                    'entity_class' => get_class($entity),
                    'entity_id' => $entity->getId()
                ]);
                return $entity;
            }

            // Sinon, la récupérer à nouveau
            return $this->ensureEntityIsManaged($entity);
        } catch (\Exception $e) {
            $this->logger->error('Error refreshing entity', [
                'entity_class' => get_class($entity),
                'error' => $e->getMessage()
            ]);
            return $entity;
        }
    }

    public function validateEntityContext(?object $entity): bool
    {
        if (!$entity) {
            return false;
        }

        try {
            $currentEM = $this->entityManagerProvider->getEntityManager();
            return $currentEM->contains($entity);
        } catch (\Exception $e) {
            $this->logger->error('Error validating entity context', [
                'entity_class' => get_class($entity),
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function resolveProxyEntity(?object $entity): ?object
    {
        if (!$entity) {
            return $entity;
        }

        try {
            $entityClass = $this->getEntityClass($entity);
            
            // Si c'est un proxy, le résoudre
            if ($this->isProxy($entity)) {
                if (method_exists($entity, 'getId') && $entity->getId()) {
                    $currentEM = $this->entityManagerProvider->getEntityManager();
                    $resolvedEntity = $currentEM->find($entityClass, $entity->getId());
                    
                    if ($resolvedEntity) {
                        $this->logger->debug('Proxy entity resolved', [
                            'entity_class' => $entityClass,
                            'entity_id' => $entity->getId()
                        ]);
                        return $resolvedEntity;
                    }
                }
            }

            return $entity;
        } catch (\Exception $e) {
            $this->logger->error('Error resolving proxy entity', [
                'entity_class' => get_class($entity),
                'error' => $e->getMessage()
            ]);
            return $entity;
        }
    }

    public function detachEntity(?object $entity): void
    {
        if (!$entity) {
            return;
        }

        try {
            $currentEM = $this->entityManagerProvider->getEntityManager();
            
            if ($currentEM->contains($entity)) {
                $currentEM->detach($entity);
                $this->logger->debug('Entity detached from context', [
                    'entity_class' => get_class($entity),
                    'entity_id' => method_exists($entity, 'getId') ? $entity->getId() : 'N/A'
                ]);
            }
        } catch (\Exception $e) {
            $this->logger->error('Error detaching entity', [
                'entity_class' => get_class($entity),
                'error' => $e->getMessage()
            ]);
        }
    }

    public function mergeDetachedEntity(?object $entity): ?object
    {
        if (!$entity) {
            return $entity;
        }

        try {
            $currentEM = $this->entityManagerProvider->getEntityManager();
            
            // Si l'entité est déjà gérée, la retourner
            if ($currentEM->contains($entity)) {
                return $entity;
            }

            // Pour les entités détachées, on ne peut pas utiliser merge() en Doctrine ORM 3.x
            // À la place, on récupère l'entité depuis la base si elle a un ID
            if (method_exists($entity, 'getId') && $entity->getId()) {
                $entityClass = $this->getEntityClass($entity);
                $managedEntity = $currentEM->find($entityClass, $entity->getId());
                
                if ($managedEntity) {
                    $this->logger->debug('Detached entity reattached via find', [
                        'entity_class' => $entityClass,
                        'entity_id' => $entity->getId()
                    ]);
                    return $managedEntity;
                }
            }

            // Si l'entité n'a pas d'ID, elle est nouvelle - la persister
            $currentEM->persist($entity);
            
            $this->logger->debug('New entity persisted', [
                'entity_class' => get_class($entity)
            ]);

            return $entity;
        } catch (\Exception $e) {
            $this->logger->error('Error merging detached entity', [
                'entity_class' => get_class($entity),
                'error' => $e->getMessage()
            ]);
            return $entity;
        }
    }

    public function isEntityDetached(?object $entity): bool
    {
        if (!$entity) {
            return false;
        }

        try {
            $currentEM = $this->entityManagerProvider->getEntityManager();
            
            // Une entité est détachée si elle a un ID mais n'est pas gérée
            if (method_exists($entity, 'getId') && $entity->getId()) {
                return !$currentEM->contains($entity);
            }

            // Une entité sans ID est considérée comme nouvelle, pas détachée
            return false;
        } catch (\Exception $e) {
            $this->logger->error('Error checking if entity is detached', [
                'entity_class' => get_class($entity),
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function clearEntityCache(): void
    {
        try {
            $currentEM = $this->entityManagerProvider->getEntityManager();
            $currentEM->clear();
            
            $this->logger->info('Entity cache cleared for current environment');
        } catch (\Exception $e) {
            $this->logger->error('Error clearing entity cache', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Obtient le nom de classe réel d'une entité (sans le préfixe Proxy)
     */
    private function getEntityClass(object $entity): string
    {
        $entityClass = get_class($entity);
        
        // Enlever le préfixe Proxy si présent
        if (strpos($entityClass, 'Proxies\\__CG__\\') === 0) {
            $entityClass = substr($entityClass, strlen('Proxies\\__CG__\\'));
        }

        return $entityClass;
    }

    /**
     * Vérifie si une entité est un proxy Doctrine
     */
    private function isProxy(object $entity): bool
    {
        $entityClass = get_class($entity);
        return strpos($entityClass, 'Proxies\\__CG__\\') === 0;
    }
}