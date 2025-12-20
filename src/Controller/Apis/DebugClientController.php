<?php

namespace App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use App\Entity\Client;
use App\Repository\BoutiqueRepository;
use App\Repository\ClientRepository;
use App\Repository\SurccursaleRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Contrôleur de debug temporaire pour diagnostiquer les problèmes de création de client
 */
#[Route('/api/debug/client')]
class DebugClientController extends ApiInterface
{
    /**
     * Endpoint de debug pour tester la création de client
     */
    #[Route('/test-create', methods: ['POST'])]
    public function debugCreate(Request $request, ClientRepository $clientRepository, SurccursaleRepository $surccursaleRepository, BoutiqueRepository $boutiqueRepository): Response
    {
        try {
            $debugInfo = [
                'request_data' => [
                    'nom' => $request->get('nom'),
                    'prenoms' => $request->get('prenoms'),
                    'numero' => $request->get('numero'),
                    'boutique' => $request->get('boutique'),
                    'succursale' => $request->get('succursale'),
                    'has_photo' => $request->files->has('photo')
                ],
                'step' => 'init'
            ];

            // Étape 1: Vérifier l'abonnement
            $debugInfo['step'] = 'checking_subscription';
            $subscription = $this->subscriptionChecker->getActiveSubscription($this->getManagedEntreprise());
            $debugInfo['subscription'] = $subscription ? 'active' : 'inactive';
            
            if ($subscription == null) {
                $debugInfo['error'] = 'No active subscription';
                return new JsonResponse($debugInfo, 403);
            }

            // Étape 2: Validation des données
            $debugInfo['step'] = 'validating_data';
            $nom = trim($request->get('nom') ?? '');
            $prenoms = trim($request->get('prenoms') ?? '');
            $numero = trim($request->get('numero') ?? '');
            
            if (empty($nom) || empty($prenoms) || empty($numero)) {
                $debugInfo['error'] = 'Missing required fields';
                $debugInfo['validation'] = [
                    'nom_empty' => empty($nom),
                    'prenoms_empty' => empty($prenoms),
                    'numero_empty' => empty($numero)
                ];
                return new JsonResponse($debugInfo, 400);
            }

            // Étape 3: Vérifier les entités liées
            $debugInfo['step'] = 'checking_entities';
            
            $succursale = null;
            $boutique = null;
            
            if ($request->get('succursale')) {
                $succursale = $surccursaleRepository->findInEnvironment($request->get('succursale'));
                $debugInfo['succursale_found'] = $succursale ? true : false;
                if ($succursale && $succursale->getBoutique()) {
                    $boutique = $succursale->getBoutique();
                    $debugInfo['boutique_from_succursale'] = true;
                }
            }
            
            if ($request->get('boutique')) {
                $boutique = $boutiqueRepository->findInEnvironment($request->get('boutique'));
                $debugInfo['boutique_found'] = $boutique ? true : false;
            }

            // Étape 4: Créer l'entité Client
            $debugInfo['step'] = 'creating_client';
            $client = new Client();
            
            try {
                $this->setManagedEntreprise($client);
                $debugInfo['managed_entreprise_set'] = true;
            } catch (\Exception $e) {
                $debugInfo['managed_entreprise_error'] = $e->getMessage();
                return new JsonResponse($debugInfo, 500);
            }
            
            $client->setPrenom($prenoms);
            $client->setNom($nom);
            $client->setNumero($numero);
            
            if ($succursale) {
                try {
                    $client->setSurccursale($this->getManagedEntityFromEnvironment($succursale));
                    $debugInfo['succursale_set'] = true;
                } catch (\Exception $e) {
                    $debugInfo['succursale_error'] = $e->getMessage();
                }
            }
            
            if ($boutique) {
                try {
                    $client->setBoutique($this->getManagedEntityFromEnvironment($boutique));
                    $debugInfo['boutique_set'] = true;
                } catch (\Exception $e) {
                    $debugInfo['boutique_error'] = $e->getMessage();
                }
            }

            // Étape 5: Configuration du trait
            $debugInfo['step'] = 'configuring_trait';
            try {
                $this->configureTraitEntity($client);
                $debugInfo['trait_configured'] = true;
            } catch (\Exception $e) {
                $debugInfo['trait_error'] = $e->getMessage();
                return new JsonResponse($debugInfo, 500);
            }

            // Étape 6: Validation
            $debugInfo['step'] = 'validating_entity';
            try {
                $errorResponse = $this->errorResponse($client);
                if ($errorResponse !== null) {
                    $debugInfo['validation_errors'] = json_decode($errorResponse->getContent(), true);
                    return new JsonResponse($debugInfo, 400);
                }
                $debugInfo['validation_passed'] = true;
            } catch (\Exception $e) {
                $debugInfo['validation_error'] = $e->getMessage();
                return new JsonResponse($debugInfo, 500);
            }

            // Étape 7: Sauvegarde (simulation)
            $debugInfo['step'] = 'saving';
            $debugInfo['ready_to_save'] = true;
            $debugInfo['client_data'] = [
                'nom' => $client->getNom(),
                'prenom' => $client->getPrenom(),
                'numero' => $client->getNumero(),
                'has_boutique' => $client->getBoutique() ? true : false,
                'has_succursale' => $client->getSurccursale() ? true : false,
                'has_entreprise' => $client->getEntreprise() ? true : false
            ];

            return new JsonResponse([
                'success' => true,
                'debug_info' => $debugInfo,
                'message' => 'Client creation would succeed'
            ], 200);

        } catch (\Exception $exception) {
            return new JsonResponse([
                'success' => false,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
                'debug_info' => $debugInfo ?? []
            ], 500);
        }
    }

    /**
     * Endpoint pour vérifier les entités disponibles
     */
    #[Route('/check-entities', methods: ['GET'])]
    public function checkEntities(SurccursaleRepository $surccursaleRepository, BoutiqueRepository $boutiqueRepository): Response
    {
        try {
            $succursales = $surccursaleRepository->findAll();
            $boutiques = $boutiqueRepository->findAll();

            return new JsonResponse([
                'succursales' => array_map(function($s) {
                    return [
                        'id' => $s->getId(),
                        'nom' => $s->getNom(),
                        'boutique_id' => $s->getBoutique() ? $s->getBoutique()->getId() : null
                    ];
                }, $succursales),
                'boutiques' => array_map(function($b) {
                    return [
                        'id' => $b->getId(),
                        'nom' => $b->getNom()
                    ];
                }, $boutiques)
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }
}