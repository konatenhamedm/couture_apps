<?php

namespace App\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class DatabaseSwitchListener implements EventSubscriberInterface
{
    private ManagerRegistry $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 256], // Très haute priorité
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        
        // Récupérer le paramètre 'env' de la requête (query string ou header)
        $env = $request->query->get('env') ?? $request->headers->get('X-Database-Env');
        
        // Stocker dans les attributs de la requête pour utilisation ultérieure
        if ($env === 'dev' || $env === 'prod') {
            $request->attributes->set('_database_env', $env);
            
            // Stocker aussi en session pour persistance
            if ($request->hasSession()) {
                $request->getSession()->set('database_env', $env);
            }
        } else {
            // Utiliser la session si disponible, sinon prod par défaut
            if ($request->hasSession() && $request->getSession()->has('database_env')) {
                $storedEnv = $request->getSession()->get('database_env');
                $request->attributes->set('_database_env', $storedEnv);
            } else {
                $request->attributes->set('_database_env', 'prod');
            }
        }
        
        // Récupérer l'environnement final
        $finalEnv = $request->attributes->get('_database_env', 'prod');
        
        // Obtenir la connexion appropriée
        $connectionName = $finalEnv === 'dev' ? 'dev' : 'prod';
        
        try {
            // Pour l'instant, on stocke juste l'environnement dans les attributs de la requête
            // L'EntityManager sera fourni par le service DatabaseEnvironmentService
            
            // Log pour debug
            error_log("DatabaseSwitchListener: Environment set to " . $finalEnv);
            
        } catch (\Exception $e) {
            // En cas d'erreur, logger et continuer avec la connexion par défaut
            error_log("Erreur lors du changement de base de données: " . $e->getMessage());
        }
    }
}