<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Service qui fournit automatiquement le bon EntityManager selon l'environnement
 */
class EntityManagerProvider
{
    private ManagerRegistry $doctrine;
    private RequestStack $requestStack;
    private ?string $currentEnv = null;
    private array $entityManagers = [];

    public function __construct(ManagerRegistry $doctrine, RequestStack $requestStack)
    {
        $this->doctrine = $doctrine;
        $this->requestStack = $requestStack;
    }

    /**
     * Retourne l'environnement de base de données actuel
     */
    public function getCurrentEnvironment(): string
    {
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
        $connectionName = $env === 'dev' ? 'dev' : 'prod';
        
        // Cache l'EntityManager pour éviter de le recréer
        if (!isset($this->entityManagers[$connectionName])) {
            // Vider le cache de tous les EntityManagers pour éviter les conflits
            foreach (['default', 'dev', 'prod'] as $managerName) {
                try {
                    $manager = $this->doctrine->getManager($managerName);
                    $manager->clear();
                } catch (\Exception $e) {
                    // Ignorer si le manager n'existe pas
                }
            }
            
            $this->entityManagers[$connectionName] = $this->doctrine->getManager($connectionName);
        }
        
        return $this->entityManagers[$connectionName];
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
     * Réinitialise le cache d'environnement (utile pour les tests)
     */
    public function resetEnvironment(): void
    {
        $this->currentEnv = null;
        $this->entityManagers = [];
    }

    /**
     * Démarre une transaction sur l'EntityManager actuel
     */
    public function beginTransaction(): void
    {
        $this->getEntityManager()->beginTransaction();
    }

    /**
     * Valide (commit) la transaction en cours
     */
    public function commit(): void
    {
        $this->getEntityManager()->commit();
    }

    /**
     * Annule (rollback) la transaction en cours
     */
    public function rollback(): void
    {
        $this->getEntityManager()->rollback();
    }

    /**
     * Persiste une entité dans l'EntityManager actuel
     */
    public function persist(object $entity): void
    {
        $this->getEntityManager()->persist($entity);
    }

    /**
     * Flush les changements vers la base de données
     */
    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }

    /**
     * Exécute une opération dans une transaction avec gestion automatique des erreurs
     * 
     * @param callable $operation La fonction à exécuter dans la transaction
     * @return mixed Le résultat de l'opération
     * @throws \Exception Si l'opération échoue
     */
    public function executeInTransaction(callable $operation)
    {
        $this->beginTransaction();
        
        try {
            $result = $operation($this->getEntityManager());
            $this->commit();
            return $result;
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    /**
     * Vérifie si une transaction est active
     */
    public function isTransactionActive(): bool
    {
        return $this->getEntityManager()->getConnection()->isTransactionActive();
    }
}