<?php

namespace App\Service\Persistence;

/**
 * Interface pour les opérations de persistance sécurisées
 */
interface SafePersistenceHandlerInterface
{
    /**
     * Persiste une entité de manière sécurisée avec validation complète
     * 
     * @param object $entity L'entité à persister
     * @param bool $flush Effectuer un flush immédiatement
     * @return SafePersistenceResult Le résultat de l'opération
     */
    public function safePersist(object $entity, bool $flush = false): SafePersistenceResult;

    /**
     * Met à jour une entité de manière sécurisée
     * 
     * @param object $entity L'entité à mettre à jour
     * @param bool $flush Effectuer un flush immédiatement
     * @return SafePersistenceResult Le résultat de l'opération
     */
    public function safeUpdate(object $entity, bool $flush = false): SafePersistenceResult;

    /**
     * Supprime une entité de manière sécurisée
     * 
     * @param object $entity L'entité à supprimer
     * @param bool $flush Effectuer un flush immédiatement
     * @return SafePersistenceResult Le résultat de l'opération
     */
    public function safeRemove(object $entity, bool $flush = false): SafePersistenceResult;

    /**
     * Valide une entité avant persistance sans la persister
     * 
     * @param object $entity L'entité à valider
     * @return SafePersistenceResult Le résultat de la validation
     */
    public function validateBeforePersistence(object $entity): SafePersistenceResult;
}