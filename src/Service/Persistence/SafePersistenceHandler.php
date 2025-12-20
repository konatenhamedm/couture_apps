<?php

namespace App\Service\Persistence;

use App\Service\Validation\EntityValidationServiceInterface;
use App\Service\Validation\CascadeOperationValidatorInterface;
use App\Service\Environment\EnvironmentEntityManagerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service pour gérer les opérations de persistance de manière sécurisée
 */
class SafePersistenceHandler implements SafePersistenceHandlerInterface
{
    private EntityManagerInterface $entityManager;
    private EntityValidationServiceInterface $entityValidationService;
    private CascadeOperationValidatorInterface $cascadeValidator;
    private EnvironmentEntityManagerInterface $environmentEntityManager;
    private LoggerInterface $logger;

    public function __construct(
        EntityManagerInterface $entityManager,
        EntityValidationServiceInterface $entityValidationService,
        CascadeOperationValidatorInterface $cascadeValidator,
        EnvironmentEntityManagerInterface $environmentEntityManager,
        LoggerInterface $logger
    ) {
        $this->entityManager = $entityManager;
        $this->entityValidationService = $entityValidationService;
        $this->cascadeValidator = $cascadeValidator;
        $this->environmentEntityManager = $environmentEntityManager;
        $this->logger = $logger;
    }

    public function safePersist(object $entity, bool $flush = false): SafePersistenceResult
    {
        $result = new SafePersistenceResult();
        
        try {
            // Étape 1: Validation de base de l'entité
            $validationResult = $this->entityValidationService->validateForPersistence($entity);
            if (!$validationResult->isValid()) {
                $result->setSuccess(false);
                $result->addErrors($validationResult->getErrors());
                $result->setMessage("Validation de l'entité échouée");
                return $result;
            }

            // Étape 2: S'assurer que l'entité est dans le bon contexte
            $managedEntity = $this->environmentEntityManager->ensureEntityIsManaged($entity);
            if (!$managedEntity) {
                $result->setSuccess(false);
                $result->addError("Impossible de gérer l'entité dans le contexte actuel");
                return $result;
            }

            // Étape 3: Validation des opérations cascade
            $cascadeResult = $this->cascadeValidator->validateCascadeOperations($managedEntity);
            if (!$cascadeResult->isValid()) {
                $result->setSuccess(false);
                $result->addErrors($cascadeResult->getErrors());
                $result->setMessage("Validation des opérations cascade échouée");
                return $result;
            }

            // Étape 4: Validation du contexte de persistance
            $contextResult = $this->cascadeValidator->ensureEntitiesInSamePersistenceContext($managedEntity);
            if (!$contextResult->isValid()) {
                $result->setSuccess(false);
                $result->addErrors($contextResult->getErrors());
                $result->setMessage("Les entités ne sont pas dans le même contexte de persistance");
                return $result;
            }

            // Étape 5: Persistance sécurisée
            $this->entityManager->persist($managedEntity);
            
            if ($flush) {
                $this->entityManager->flush();
            }

            $result->setSuccess(true);
            $result->setEntity($managedEntity);
            $result->setMessage("Entité persistée avec succès");
            
            // Ajouter les avertissements s'il y en a
            if ($validationResult->hasWarnings()) {
                $result->addWarnings($validationResult->getWarnings());
            }
            if ($cascadeResult->hasWarnings()) {
                $result->addWarnings($cascadeResult->getWarnings());
            }
            if ($contextResult->hasWarnings()) {
                $result->addWarnings($contextResult->getWarnings());
            }

            $this->logger->info('Entity persisted safely', [
                'entity_class' => get_class($managedEntity),
                'entity_id' => method_exists($managedEntity, 'getId') ? $managedEntity->getId() : 'new'
            ]);

        } catch (\Exception $e) {
            $result->setSuccess(false);
            $result->addError("Erreur lors de la persistance: " . $e->getMessage());
            $result->setMessage("Erreur inattendue lors de la persistance");
            
            $this->logger->error('Safe persist failed', [
                'entity_class' => get_class($entity),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        return $result;
    }

    public function safeUpdate(object $entity, bool $flush = false): SafePersistenceResult
    {
        $result = new SafePersistenceResult();
        
        try {
            // Vérifier que l'entité existe déjà
            if (!method_exists($entity, 'getId') || !$entity->getId()) {
                $result->setSuccess(false);
                $result->addError("L'entité doit avoir un ID pour être mise à jour");
                return $result;
            }

            // Étape 1: S'assurer que l'entité est gérée
            $managedEntity = $this->environmentEntityManager->ensureEntityIsManaged($entity);
            if (!$managedEntity) {
                $result->setSuccess(false);
                $result->addError("Impossible de gérer l'entité pour la mise à jour");
                return $result;
            }

            // Étape 2: Validation de l'entité mise à jour
            $validationResult = $this->entityValidationService->validateForPersistence($managedEntity);
            if (!$validationResult->isValid()) {
                $result->setSuccess(false);
                $result->addErrors($validationResult->getErrors());
                $result->setMessage("Validation de l'entité mise à jour échouée");
                return $result;
            }

            // Étape 3: Validation des entités liées
            $relatedStatesResult = $this->cascadeValidator->validateRelatedEntityStates($managedEntity);
            if (!$relatedStatesResult->isValid()) {
                $result->setSuccess(false);
                $result->addErrors($relatedStatesResult->getErrors());
                $result->setMessage("Validation des entités liées échouée");
                return $result;
            }

            // Étape 4: Flush si demandé
            if ($flush) {
                $this->entityManager->flush();
            }

            $result->setSuccess(true);
            $result->setEntity($managedEntity);
            $result->setMessage("Entité mise à jour avec succès");
            
            // Ajouter les avertissements
            if ($validationResult->hasWarnings()) {
                $result->addWarnings($validationResult->getWarnings());
            }
            if ($relatedStatesResult->hasWarnings()) {
                $result->addWarnings($relatedStatesResult->getWarnings());
            }

            $this->logger->info('Entity updated safely', [
                'entity_class' => get_class($managedEntity),
                'entity_id' => $managedEntity->getId()
            ]);

        } catch (\Exception $e) {
            $result->setSuccess(false);
            $result->addError("Erreur lors de la mise à jour: " . $e->getMessage());
            $result->setMessage("Erreur inattendue lors de la mise à jour");
            
            $this->logger->error('Safe update failed', [
                'entity_class' => get_class($entity),
                'entity_id' => method_exists($entity, 'getId') ? $entity->getId() : 'N/A',
                'error' => $e->getMessage()
            ]);
        }

        return $result;
    }

    public function safeRemove(object $entity, bool $flush = false): SafePersistenceResult
    {
        $result = new SafePersistenceResult();
        
        try {
            // Vérifier que l'entité existe
            if (!method_exists($entity, 'getId') || !$entity->getId()) {
                $result->setSuccess(false);
                $result->addError("L'entité doit avoir un ID pour être supprimée");
                return $result;
            }

            // S'assurer que l'entité est gérée
            $managedEntity = $this->environmentEntityManager->ensureEntityIsManaged($entity);
            if (!$managedEntity) {
                $result->setSuccess(false);
                $result->addError("Impossible de gérer l'entité pour la suppression");
                return $result;
            }

            // Validation des opérations cascade (pour cascade remove)
            $cascadeResult = $this->cascadeValidator->validateCascadeOperations($managedEntity);
            if (!$cascadeResult->isValid()) {
                $result->setSuccess(false);
                $result->addErrors($cascadeResult->getErrors());
                $result->setMessage("Validation des opérations cascade pour suppression échouée");
                return $result;
            }

            // Suppression sécurisée
            $this->entityManager->remove($managedEntity);
            
            if ($flush) {
                $this->entityManager->flush();
            }

            $result->setSuccess(true);
            $result->setEntity($managedEntity);
            $result->setMessage("Entité supprimée avec succès");
            
            if ($cascadeResult->hasWarnings()) {
                $result->addWarnings($cascadeResult->getWarnings());
            }

            $this->logger->info('Entity removed safely', [
                'entity_class' => get_class($managedEntity),
                'entity_id' => $managedEntity->getId()
            ]);

        } catch (\Exception $e) {
            $result->setSuccess(false);
            $result->addError("Erreur lors de la suppression: " . $e->getMessage());
            $result->setMessage("Erreur inattendue lors de la suppression");
            
            $this->logger->error('Safe remove failed', [
                'entity_class' => get_class($entity),
                'entity_id' => method_exists($entity, 'getId') ? $entity->getId() : 'N/A',
                'error' => $e->getMessage()
            ]);
        }

        return $result;
    }

    public function validateBeforePersistence(object $entity): SafePersistenceResult
    {
        $result = new SafePersistenceResult();
        
        try {
            // Validation complète sans persistance
            $validationResult = $this->entityValidationService->validateForPersistence($entity);
            $cascadeResult = $this->cascadeValidator->validateCascadeOperations($entity);
            $contextResult = $this->cascadeValidator->ensureEntitiesInSamePersistenceContext($entity);
            
            $allValid = $validationResult->isValid() && $cascadeResult->isValid() && $contextResult->isValid();
            
            $result->setSuccess($allValid);
            
            if (!$allValid) {
                $result->addErrors($validationResult->getErrors());
                $result->addErrors($cascadeResult->getErrors());
                $result->addErrors($contextResult->getErrors());
                $result->setMessage("Validation échouée");
            } else {
                $result->setMessage("Validation réussie");
            }
            
            // Ajouter tous les avertissements
            $result->addWarnings($validationResult->getWarnings());
            $result->addWarnings($cascadeResult->getWarnings());
            $result->addWarnings($contextResult->getWarnings());
            
        } catch (\Exception $e) {
            $result->setSuccess(false);
            $result->addError("Erreur lors de la validation: " . $e->getMessage());
            $result->setMessage("Erreur inattendue lors de la validation");
        }

        return $result;
    }
}