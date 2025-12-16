<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\RequestStack;

class DatabaseEnvironmentService
{
    private ManagerRegistry $doctrine;
    private RequestStack $requestStack;

    public function __construct(ManagerRegistry $doctrine, RequestStack $requestStack)
    {
        $this->doctrine = $doctrine;
        $this->requestStack = $requestStack;
    }

    /**
     * Retourne l'environnement de base de données actuel (dev ou prod)
     */
    public function getCurrentEnvironment(): string
    {
        $request = $this->requestStack->getCurrentRequest();
        
        if ($request) {
            return $request->attributes->get('_database_env', 'prod');
        }
        
        return 'prod';
    }

    /**
     * Retourne l'EntityManager pour l'environnement actuel
     */
    public function getEntityManager(): EntityManagerInterface
    {
        $env = $this->getCurrentEnvironment();
        $connectionName = $env === 'dev' ? 'dev' : 'prod';
        
        // Vider le cache de tous les EntityManagers pour éviter les conflits
        foreach (['default', 'dev', 'prod'] as $managerName) {
            try {
                $manager = $this->doctrine->getManager($managerName);
                $manager->clear();
            } catch (\Exception $e) {
                // Ignorer si le manager n'existe pas
            }
        }
        
        // Obtenir l'EntityManager pour l'environnement spécifique
        $entityManager = $this->doctrine->getManager($connectionName);
        
        return $entityManager;
    }

    /**
     * Retourne la connexion pour l'environnement actuel
     */
    public function getConnection()
    {
        $env = $this->getCurrentEnvironment();
        $connectionName = $env === 'dev' ? 'dev' : 'prod';
        
        return $this->doctrine->getConnection($connectionName);
    }

    /**
     * Vérifie si on est en environnement dev
     */
    public function isDev(): bool
    {
        return $this->getCurrentEnvironment() === 'dev';
    }

    /**
     * Vérifie si on est en environnement prod
     */
    public function isProd(): bool
    {
        return $this->getCurrentEnvironment() === 'prod';
    }
}