<?php

namespace App\Service\Validation;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use ReflectionProperty;

/**
 * Service de validation d'entités avant persistance
 */
class EntityValidationService implements EntityValidationServiceInterface
{
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;

    // Entités connues avec des champs 'libelle' requis
    private const ENTITIES_WITH_LIBELLE = [
        'App\Entity\Boutique',
        'App\Entity\Surccursale', 
        'App\Entity\Entreprise',
        'App\Entity\Pays',
        'App\Entity\TypeUser',
        'App\Entity\Notification',
        'App\Entity\LigneModule',
        'App\Entity\TypeMesure',
        'App\Entity\CategorieMesure'
    ];

    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    public function validateForPersistence(object $entity): ValidationResult
    {
        $result = new ValidationResult();

        // Validation des champs requis
        $requiredFieldsResult = $this->validateRequiredFields($entity);
        $result->merge($requiredFieldsResult);

        // Validation spécifique des champs libelle
        $libelleResult = $this->validateLibelleFields($entity);
        $result->merge($libelleResult);

        // Validation des entités liées
        $relatedEntitiesResult = $this->validateRelatedEntities($entity);
        $result->merge($relatedEntitiesResult);

        // Validation des opérations cascade
        $cascadeResult = $this->validateCascadeOperations($entity);
        $result->merge($cascadeResult);

        if (!$result->isValid()) {
            $this->logger->warning('Entity validation failed', [
                'entity_class' => get_class($entity),
                'entity_id' => method_exists($entity, 'getId') ? $entity->getId() : 'new',
                'errors' => $result->getErrors()
            ]);
        }

        return $result;
    }

    public function validateRequiredFields(object $entity): ValidationResult
    {
        $result = new ValidationResult();
        
        try {
            $metadata = $this->entityManager->getClassMetadata(get_class($entity));
            $reflection = new ReflectionClass($entity);

            foreach ($metadata->getFieldNames() as $fieldName) {
                $fieldMapping = $metadata->getFieldMapping($fieldName);
                
                // Ignorer les champs auto-générés (comme l'ID)
                if ($fieldMapping->generated || $fieldMapping->id ?? false) {
                    continue;
                }
                
                // Vérifier si le champ est requis (non nullable) - utiliser la propriété directement
                $isNullable = $fieldMapping->nullable ?? true;
                $hasDefault = isset($fieldMapping->options['default']);
                
                if (!$isNullable && !$hasDefault) {
                    $value = $this->getPropertyValue($entity, $fieldName, $reflection);
                    
                    if ($value === null || $value === '') {
                        $result->addError("Le champ requis '{$fieldName}' ne peut pas être null ou vide");
                    }
                }
            }
        } catch (\Exception $e) {
            $result->addError("Erreur lors de la validation des champs requis: " . $e->getMessage());
            $this->logger->error('Required fields validation error', [
                'entity_class' => get_class($entity),
                'error' => $e->getMessage()
            ]);
        }

        return $result;
    }

    public function validateLibelleFields(object $entity): ValidationResult
    {
        $result = new ValidationResult();
        $entityClass = get_class($entity);

        // Vérifier si cette entité a un champ libelle requis
        if (in_array($entityClass, self::ENTITIES_WITH_LIBELLE)) {
            if (method_exists($entity, 'getLibelle')) {
                $libelle = $entity->getLibelle();
                
                // Vérifier si le libelle est vide (null, chaîne vide, ou seulement des espaces)
                if ($libelle === null || trim($libelle) === '') {
                    $result->addError("Le champ 'libelle' est requis pour l'entité " . basename($entityClass));
                }
            } else {
                $result->addWarning("L'entité " . basename($entityClass) . " devrait avoir une méthode getLibelle()");
            }
        }

        return $result;
    }

    public function validateRelatedEntities(object $entity): ValidationResult
    {
        $result = new ValidationResult();
        
        try {
            $metadata = $this->entityManager->getClassMetadata(get_class($entity));
            $reflection = new ReflectionClass($entity);

            // Valider les associations
            foreach ($metadata->getAssociationNames() as $associationName) {
                $associationMapping = $metadata->getAssociationMapping($associationName);
                
                // Vérifier si l'association est nullable en utilisant la propriété directement
                $isNullable = true;
                if (isset($associationMapping['joinColumns']) && !empty($associationMapping['joinColumns'])) {
                    $joinColumn = $associationMapping['joinColumns'][0];
                    // Utiliser la propriété nullable directement au lieu d'ArrayAccess
                    $isNullable = $joinColumn->nullable ?? true;
                }
                
                // Ignorer les associations nullable
                if ($isNullable) {
                    continue;
                }

                $relatedEntity = $this->getPropertyValue($entity, $associationName, $reflection);
                
                if ($relatedEntity === null) {
                    $result->addError("L'association requise '{$associationName}' ne peut pas être null");
                    continue;
                }

                // Valider l'entité liée seulement si elle n'est pas déjà persistée
                // Les entités déjà en base sont considérées comme valides
                if (!$this->isEntityAlreadyValidated($relatedEntity) && !$this->isEntityPersisted($relatedEntity)) {
                    $relatedResult = $this->validateLibelleFields($relatedEntity);
                    if (!$relatedResult->isValid()) {
                        $result->addError("Entité liée '{$associationName}' invalide: " . $relatedResult->getFormattedErrors());
                    }
                }
            }
        } catch (\Exception $e) {
            $result->addError("Erreur lors de la validation des entités liées: " . $e->getMessage());
            $this->logger->error('Related entities validation error', [
                'entity_class' => get_class($entity),
                'error' => $e->getMessage()
            ]);
        }

        return $result;
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
                    $relatedEntity = $this->getPropertyValue($entity, $associationName, $reflection);
                    
                    if ($relatedEntity !== null) {
                        // Vérifier que l'entité liée est dans un état valide pour la persistance
                        // Mais seulement si elle n'est pas déjà persistée
                        if (!$this->entityManager->contains($relatedEntity) && !$this->isEntityPersisted($relatedEntity)) {
                            // L'entité n'est pas gérée et n'est pas persistée, valider qu'elle peut être persistée
                            $cascadeResult = $this->validateForPersistence($relatedEntity);
                            if (!$cascadeResult->isValid()) {
                                $result->addError("Cascade persist échouera pour '{$associationName}': " . $cascadeResult->getFormattedErrors());
                            }
                        }
                    }
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

    /**
     * Obtient la valeur d'une propriété d'entité via réflexion
     */
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

    /**
     * Évite la récursion infinie lors de la validation
     */
    private array $validatedEntities = [];

    private function isEntityAlreadyValidated(object $entity): bool
    {
        $entityHash = spl_object_hash($entity);
        
        if (in_array($entityHash, $this->validatedEntities)) {
            return true;
        }

        $this->validatedEntities[] = $entityHash;
        return false;
    }

    /**
     * Vérifie si une entité est déjà persistée en base de données
     */
    private function isEntityPersisted(object $entity): bool
    {
        try {
            // Une entité est considérée comme persistée si elle a un ID et est gérée par l'EntityManager
            if (method_exists($entity, 'getId') && $entity->getId() !== null) {
                return true;
            }
            
            // Vérifier si l'entité est dans l'UnitOfWork
            return $this->entityManager->contains($entity);
        } catch (\Exception $e) {
            // En cas d'erreur, considérer l'entité comme non persistée pour être sûr
            return false;
        }
    }
}