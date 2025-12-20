<?php

namespace App\Service\Environment;

/**
 * Interface pour la gestion des entités dans le système à deux environnements
 */
interface EnvironmentEntityManagerInterface
{
    /**
     * S'assure qu'une entité est gérée par l'EntityManager de l'environnement actuel
     * 
     * @param object|null $entity L'entité à gérer
     * @return object|null L'entité gérée
     */
    public function ensureEntityIsManaged(?object $entity): ?object;

    /**
     * Rafraîchit une entité dans le contexte de l'environnement actuel
     * 
     * @param object|null $entity L'entité à rafraîchir
     * @return object|null L'entité rafraîchie
     */
    public function refreshEntityInCurrentContext(?object $entity): ?object;

    /**
     * Valide qu'une entité est dans le bon contexte EntityManager
     * 
     * @param object|null $entity L'entité à valider
     * @return bool True si l'entité est dans le bon contexte
     */
    public function validateEntityContext(?object $entity): bool;

    /**
     * Résout les entités proxy en entités gérées
     * 
     * @param object|null $entity L'entité proxy à résoudre
     * @return object|null L'entité gérée résolue
     */
    public function resolveProxyEntity(?object $entity): ?object;

    /**
     * Détache une entité du contexte de persistance
     * 
     * @param object|null $entity L'entité à détacher
     * @return void
     */
    public function detachEntity(?object $entity): void;

    /**
     * Merge une entité détachée dans le contexte actuel
     * 
     * @param object|null $entity L'entité détachée à merger
     * @return object|null L'entité mergée
     */
    public function mergeDetachedEntity(?object $entity): ?object;

    /**
     * Vérifie si une entité est détachée
     * 
     * @param object|null $entity L'entité à vérifier
     * @return bool True si l'entité est détachée
     */
    public function isEntityDetached(?object $entity): bool;

    /**
     * Nettoie le cache des entités pour éviter les conflits entre environnements
     * 
     * @return void
     */
    public function clearEntityCache(): void;
}