<?php

namespace App\Service\Validation;

/**
 * Interface pour la validation des opérations cascade
 */
interface CascadeOperationValidatorInterface
{
    /**
     * Valide toutes les opérations cascade d'une entité
     * 
     * @param object $entity L'entité à valider
     * @return ValidationResult Le résultat de la validation
     */
    public function validateCascadeOperations(object $entity): ValidationResult;

    /**
     * Valide les états des entités liées
     * 
     * @param object $entity L'entité dont on valide les entités liées
     * @return ValidationResult Le résultat de la validation
     */
    public function validateRelatedEntityStates(object $entity): ValidationResult;

    /**
     * S'assure que toutes les entités sont dans le même contexte de persistance
     * 
     * @param object $entity L'entité principale
     * @return ValidationResult Le résultat de la validation
     */
    public function ensureEntitiesInSamePersistenceContext(object $entity): ValidationResult;
}