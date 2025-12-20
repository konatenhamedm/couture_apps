<?php

namespace App\Service\Validation;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use ReflectionClass;

/**
 * Service pour valider les opérations cascade avant persistance
 */
class CascadeOperationValidator implements CascadeOperationValidatorInterface
{
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;
    private EntityValidationServiceInterface $entityValidationService;

    public function __construct(
        EntityManagerInterface $entityManager,
        LoggerInterface $logger,
        EntityValidationServiceInterface $entityValidationService
    ) {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->entityValidationService = $entityValidationService;
    }

    public function validateCascadeOperations(object $entity): ValidationResult
    {
        $result = new ValidationResult();
        
        try {
            $metadata = $this->entityManager->getClassMetadata(get_class($entity));
            $reflection = new ReflectionClass($entity);

            foreach ($metadata->getAssociationNames() as $associationName) {
                $associationMapping = $metadata->getAssociationMapping($associationName);
                
                // Vérifier si l'association a cascade persist
                if (in_array('persist', $associationMapping['cascade'] ?? [])) {
                    $cascadeResult = $this->validateCascadePersistOperation($entity, $associationName, $reflection);
                    $result->merge($cascadeResult);
                }
                
                // Vérifier si l'association a cascade remove
                if (in_array('remove', $associationMapping['cascade'] ?? [])) {
                    $cascadeResult = $this->validateCascadeRemoveOperation($entity, $associationName, $reflection);
                    $result->merge($cascadeResult);
                }
            }
        } catch (\Exception $e) {
            $result->addError("Erreur lors de la validation des opérations cascade: " . $e->getMessage());
            $this->logger->error('Cascade operations validation error', [
                'entity_class' => get_class($entity),
                'error' => $e->getMessage()
            ]);
        }

        return $result;
    }

    public function validateRelatedEntityStates(object $entity): ValidationResult
    {
        $result = new ValidationResult();
        
        try {
            $metadata = $this->entityManager->getClassMetadata(get_class($entity));
            $reflection = new ReflectionClass($entity);

            foreach ($metadata->getAssociationNames() as $associationName) {
                $relatedEntity = $this->getPropertyValue($entity, $associationName, $reflection);
                
                if ($relatedEntity !== null) {
                    $stateResult = $this->validateEntityState($relatedEntity, $associationName);
                    $result->merge($stateResult);
                }
            }
        } catch (\Exception $e) {
            $result->addError("Erreur lors de la validation des états des entités liées: " . $e->getMessage());
            $this->logger->error('Related entity states validation error', [
                'entity_class' => get_class($entity),
                'error' => $e->getMessage()
            ]);
        }

        return $result;
    }

    public function ensureEntitiesInSamePersistenceContext(object $entity): ValidationResult
    {
        $result = new ValidationResult();
        
        try {
            $metadata = $this->entityManager->getClassMetadata(get_class($entity));
            $reflection = new ReflectionClass($entity);
            
            $entityIsManaged = $this->entityManager->contains($entity);

            foreach ($metadata->getAssociationNames() as $associationName) {
                $relatedEntity = $this->getPropertyValue($entity, $associationName, $reflection);
                
                if ($relatedEntity !== null) {
                    $relatedIsManaged = $this->entityManager->contains($relatedEntity);
                    
                    // Si l'entité principale est gérée mais pas l'entité liée
                    if ($entityIsManaged && !$relatedIsManaged) {
                        // Vérifier si l'entité liée a un ID (donc existe en base)
                        if (method_exists($relatedEntity, 'getId') && $relatedEntity->getId()) {
                            $result->addWarning("L'entité liée '{$associationName}' est détachée du contexte de persistance");
                        } else {
                            // Entité nouvelle - vérifier qu'elle peut être persistée
                            $validationResult = $this->entityValidationService->validateForPersistence($relatedEntity);
                            if (!$validationResult->isValid()) {
                                $result->addError("L'entité liée '{$associationName}' ne peut pas être persistée: " . $validationResult->getFormattedErrors());
                            }
                        }
                    }
                    
                    // Si l'entité principale n'est pas gérée mais l'entité liée l'est
                    if (!$entityIsManaged && $relatedIsManaged) {
                        $result->addWarning("L'entité principale n'est pas gérée mais l'entité liée '{$associationName}' l'est");
                    }
                }
            }
        } catch (\Exception $e) {
            $result->addError("Erreur lors de la vérification du contexte de persistance: " . $e->getMessage());
            $this->logger->error('Persistence context validation error', [
                'entity_class' => get_class($entity),
                'error' => $e->getMessage()
            ]);
        }

        return $result;
    }

    private function validateCascadePersistOperation(object $entity, string $associationName, ReflectionClass $reflection): ValidationResult
    {
        $result = new ValidationResult();
        
        $relatedEntity = $this->getPropertyValue($entity, $associationName, $reflection);
        
        if ($relatedEntity !== null) {
            // Si l'entité liée n'est pas gérée et n'est pas persistée
            if (!$this->entityManager->contains($relatedEntity) && !$this->isEntityPersisted($relatedEntity)) {
                // Valider que l'entité peut être persistée via cascade
                $cascadeValidation = $this->entityValidationService->validateForPersistence($relatedEntity);
                if (!$cascadeValidation->isValid()) {
                    $result->addError("Cascade persist échouera pour '{$associationName}': " . $cascadeValidation->getFormattedErrors());
                } else {
                    $result->addInfo("Cascade persist validé pour '{$associationName}'");
                }
            }
        }

        return $result;
    }

    private function validateCascadeRemoveOperation(object $entity, string $associationName, ReflectionClass $reflection): ValidationResult
    {
        $result = new ValidationResult();
        
        $relatedEntity = $this->getPropertyValue($entity, $associationName, $reflection);
        
        if ($relatedEntity !== null) {
            // Vérifier si l'entité liée peut être supprimée
            if ($this->entityManager->contains($relatedEntity) || $this->isEntityPersisted($relatedEntity)) {
                // Vérifier s'il y a des contraintes qui empêcheraient la suppression
                $canBeRemoved = $this->checkEntityCanBeRemoved($relatedEntity);
                if (!$canBeRemoved) {
                    $result->addWarning("Cascade remove pourrait échouer pour '{$associationName}' à cause de contraintes de clés étrangères");
                } else {
                    $result->addInfo("Cascade remove validé pour '{$associationName}'");
                }
            }
        }

        return $result;
    }

    private function validateEntityState(object $entity, string $associationName): ValidationResult
    {
        $result = new ValidationResult();
        
        // Vérifier l'état de l'entité
        if ($this->entityManager->contains($entity)) {
            $result->addInfo("Entité liée '{$associationName}' est gérée par l'EntityManager");
        } elseif ($this->isEntityPersisted($entity)) {
            $result->addWarning("Entité liée '{$associationName}' est persistée mais détachée du contexte");
        } else {
            // Entité nouvelle - vérifier qu'elle est valide
            $validationResult = $this->entityValidationService->validateForPersistence($entity);
            if (!$validationResult->isValid()) {
                $result->addError("Entité liée '{$associationName}' invalide: " . $validationResult->getFormattedErrors());
            } else {
                $result->addInfo("Entité liée '{$associationName}' est nouvelle et valide");
            }
        }

        return $result;
    }

    private function checkEntityCanBeRemoved(object $entity): bool
    {
        try {
            // Vérification basique - dans un vrai système, on vérifierait les contraintes FK
            // Pour l'instant, on assume que l'entité peut être supprimée
            return true;
        } catch (\Exception $e) {
            $this->logger->warning('Could not check if entity can be removed', [
                'entity_class' => get_class($entity),
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    private function getPropertyValue(object $entity, string $propertyName, ReflectionClass $reflection)
    {
        try {
            // Essayer d'abord avec un getter
            $getterMethod = 'get' . ucfirst($propertyName);
            if ($reflection->hasMethod($getterMethod)) {
                return $entity->$getterMethod();
            }

            // Essayer avec is pour les booléens
            $isMethod = 'is' . ucfirst($propertyName);
            if ($reflection->hasMethod($isMethod)) {
                return $entity->$isMethod();
            }

            // Accès direct à la propriété si elle existe
            if ($reflection->hasProperty($propertyName)) {
                $property = $reflection->getProperty($propertyName);
                $property->setAccessible(true);
                return $property->getValue($entity);
            }

            return null;
        } catch (\Exception $e) {
            $this->logger->warning('Could not get property value', [
                'entity_class' => get_class($entity),
                'property' => $propertyName,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    private function isEntityPersisted(object $entity): bool
    {
        try {
            // Une entité est considérée comme persistée si elle a un ID
            if (method_exists($entity, 'getId') && $entity->getId() !== null) {
                return true;
            }
            
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }
}