<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\RequestStack;

class DynamicDatabaseService
{
    private ManagerRegistry $doctrine;
    private RequestStack $requestStack;
    private ?string $currentEnv = null;

    public function __construct(ManagerRegistry $doctrine, RequestStack $requestStack)
    {
        $this->doctrine = $doctrine;
        $this->requestStack = $requestStack;
    }

    /**
     * Retourne l'environnement de base de données actuel basé sur le paramètre ?env=
     */
    public function getCurrentEnvironment(): string
    {
        // Si déjà déterminé, retourner le cache
        if ($this->currentEnv !== null) {
            return $this->currentEnv;
        }

        $request = $this->requestStack->getCurrentRequest();
        
        if (!$request) {
            $this->currentEnv = 'prod';
            return $this->currentEnv;
        }

        // Vérifier le paramètre GET ?env=
        $env = $request->query->get('env');
        
        // Vérifier aussi le header X-Database-Env
        if (!$env) {
            $env = $request->headers->get('X-Database-Env');
        }
        
        // Valider l'environnement
        if ($env === 'dev' || $env === 'prod') {
            $this->currentEnv = $env;
            
            // Stocker en session pour les requêtes suivantes
            if ($request->hasSession()) {
                $request->getSession()->set('database_env', $env);
            }
        } else {
            // Essayer de récupérer depuis la session
            if ($request->hasSession() && $request->getSession()->has('database_env')) {
                $this->currentEnv = $request->getSession()->get('database_env');
            } else {
                $this->currentEnv = 'prod'; // Par défaut
            }
        }
        
        return $this->currentEnv;
    }

    /**
     * Retourne l'EntityManager pour l'environnement actuel
     */
    public function getEntityManager(): EntityManagerInterface
    {
        $env = $this->getCurrentEnvironment();
        return $this->doctrine->getManager($env);
    }

    /**
     * Retourne la connexion pour l'environnement actuel
     */
    public function getConnection()
    {
        $env = $this->getCurrentEnvironment();
        return $this->doctrine->getConnection($env);
    }

    /**
     * Réinitialise le cache d'environnement (utile pour les tests)
     */
    public function resetEnvironment(): void
    {
        $this->currentEnv = null;
    }
}