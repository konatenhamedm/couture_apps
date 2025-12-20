<?php

namespace App\Service\Validation;

/**
 * Interface pour le service de validation d'entités avant persistance
 */
interface EntityValidationServiceInterface
{
    /**
     * Valide une entité avant persistance
     * 
     * @param object $entity L'entité à valider
     * @return ValidationResult Le résultat de la validation
     */
    public function validateForPersistence(object $entity): ValidationResult;

    /**
     * Valide les entités liées d'une entité
     * 
     * @param object $entity L'entité dont on veut valider les relations
     * @return ValidationResult Le résultat de la validation
     */
    public function validateRelatedEntities(object $entity): ValidationResult;

    /**
     * Valide les opérations de cascade persist
     * 
     * @param object $entity L'entité avec des relations cascade
     * @return ValidationResult Le résultat de la validation
     */
    public function validateCascadeOperations(object $entity): ValidationResult;

    /**
     * Valide qu'une entité a tous ses champs requis
     * 
     * @param object $entity L'entité à valider
     * @return ValidationResult Le résultat de la validation
     */
    public function validateRequiredFields(object $entity): ValidationResult;

    /**
     * Valide spécifiquement les entités avec des champs 'libelle'
     * 
     * @param object $entity L'entité à valider
     * @return ValidationResult Le résultat de la validation
     */
    public function validateLibelleFields(object $entity): ValidationResult;
}