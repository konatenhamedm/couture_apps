<?php

namespace App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use App\Service\StatistiquesService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Request;
use DateTime;

#[Route('/api')]
class ApiStatistiqueController extends ApiInterface
{
    public function __construct(
        private StatistiquesService $statistiquesService
    ) {}

    /**
     * Retourne les statistiques principales du dashboard
     */
    #[Route('/statistique/dashboard', methods: ['POST'])]
    #[OA\Post(
        description: "Retourne les statistiques principales (commandes, revenus, clients, taux réservation)",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "dateDebut", type: "string", format: "date", example: "2025-10-01"),
                    new OA\Property(property: "dateFin", type: "string", format: "date", example: "2025-10-31")
                ]
            )
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Statistiques du dashboard',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "commandesTotales", type: "object"),
                new OA\Property(property: "revenus", type: "object"),
                new OA\Property(property: "nouveauxClients", type: "object"),
                new OA\Property(property: "tauxReservation", type: "object")
            ]
        )
    )]
    #[OA\Tag(name: 'statistique')]
    public function dashboard(Request $request): Response
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            $dateDebut = new DateTime($data['dateDebut'] ?? 'first day of this month');
            $dateFin = new DateTime($data['dateFin'] ?? 'now');
            
            $stats = $this->statistiquesService->getDashboardStats($dateDebut, $dateFin);
            
            return $this->json([
                'success' => true,
                'data' => $stats
            ]);
            
        } catch (\Exception $exception) {
            return $this->json([
                'success' => false,
                'message' => $exception->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Évolution des revenus (pour graphique)
     */
    #[Route('/statistique/revenus/evolution', methods: ['POST'])]
    #[OA\Post(
        description: "Retourne l'évolution des revenus pour afficher un graphique",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "dateDebut", type: "string", format: "date", example: "2025-10-01"),
                    new OA\Property(property: "dateFin", type: "string", format: "date", example: "2025-10-31"),
                    new OA\Property(property: "groupBy", type: "string", enum: ["jour", "semaine", "mois"], example: "jour")
                ]
            )
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Données pour graphique des revenus',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "labels", type: "array", items: new OA\Items(type: "string")),
                new OA\Property(property: "data", type: "array", items: new OA\Items(type: "number")),
                new OA\Property(property: "total", type: "number"),
                new OA\Property(property: "moyenne", type: "number")
            ]
        )
    )]
    #[OA\Tag(name: 'statistique')]
    public function evolutionRevenus(Request $request): Response
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            $dateDebut = new DateTime($data['dateDebut'] ?? '-30 days');
            $dateFin = new DateTime($data['dateFin'] ?? 'now');
            $groupBy = $data['groupBy'] ?? 'jour';
            
            $evolution = $this->statistiquesService->getEvolutionRevenus($dateDebut, $dateFin, $groupBy);
            
            return $this->json([
                'success' => true,
                'data' => $evolution
            ]);
            
        } catch (\Exception $exception) {
            return $this->json([
                'success' => false,
                'message' => $exception->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Évolution des commandes (pour graphique)
     */
    #[Route('/statistique/commandes/evolution', methods: ['POST'])]
    #[OA\Post(
        description: "Retourne l'évolution des commandes (factures + réservations) pour afficher un graphique"
    )]
    #[OA\Tag(name: 'statistique')]
    public function evolutionCommandes(Request $request): Response
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            $dateDebut = new DateTime($data['dateDebut'] ?? '-30 days');
            $dateFin = new DateTime($data['dateFin'] ?? 'now');
            $groupBy = $data['groupBy'] ?? 'jour';
            
            $evolution = $this->statistiquesService->getEvolutionCommandes($dateDebut, $dateFin, $groupBy);
            
            return $this->json([
                'success' => true,
                'data' => $evolution
            ]);
            
        } catch (\Exception $exception) {
            return $this->json([
                'success' => false,
                'message' => $exception->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Répartition des revenus par type (pour graphique camembert/pie)
     */
    #[Route('/statistique/revenus/par-type', methods: ['POST'])]
    #[OA\Post(
        description: "Retourne la répartition des revenus par type de paiement pour un graphique camembert"
    )]
    #[OA\Response(
        response: 200,
        description: 'Données pour graphique camembert',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "labels", type: "array", items: new OA\Items(type: "string")),
                new OA\Property(property: "data", type: "array", items: new OA\Items(type: "number")),
                new OA\Property(property: "colors", type: "array", items: new OA\Items(type: "string")),
                new OA\Property(property: "total", type: "number")
            ]
        )
    )]
    #[OA\Tag(name: 'statistique')]
    public function revenusParType(Request $request): Response
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            $dateDebut = new DateTime($data['dateDebut'] ?? '-30 days');
            $dateFin = new DateTime($data['dateFin'] ?? 'now');
            
            $repartition = $this->statistiquesService->getRevenusParType($dateDebut, $dateFin);
            
            return $this->json([
                'success' => true,
                'data' => $repartition
            ]);
            
        } catch (\Exception $exception) {
            return $this->json([
                'success' => false,
                'message' => $exception->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Top clients
     */
    #[Route('/statistique/top-clients', methods: ['POST'])]
    #[OA\Post(
        description: "Retourne la liste des meilleurs clients par montant dépensé"
    )]
    #[OA\Tag(name: 'statistique')]
    public function topClients(Request $request): Response
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            $dateDebut = new DateTime($data['dateDebut'] ?? '-30 days');
            $dateFin = new DateTime($data['dateFin'] ?? 'now');
            $limit = $data['limit'] ?? 10;
            
            $topClients = $this->statistiquesService->getTopClients($dateDebut, $dateFin, $limit);
            
            return $this->json([
                'success' => true,
                'data' => $topClients
            ]);
            
        } catch (\Exception $exception) {
            return $this->json([
                'success' => false,
                'message' => $exception->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Comparatif avec période précédente
     */
    #[Route('/statistique/comparatif', methods: ['POST'])]
    #[OA\Post(
        description: "Compare les statistiques avec la période précédente de même durée"
    )]
    #[OA\Tag(name: 'statistique')]
    public function comparatif(Request $request): Response
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            $dateDebut = new DateTime($data['dateDebut'] ?? '-30 days');
            $dateFin = new DateTime($data['dateFin'] ?? 'now');
            
            $comparatif = $this->statistiquesService->getComparatif($dateDebut, $dateFin);
            
            return $this->json([
                'success' => true,
                'data' => $comparatif
            ]);
            
        } catch (\Exception $exception) {
            return $this->json([
                'success' => false,
                'message' => $exception->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Ancien endpoint (compatibilité)
     */
    #[Route('/statistique', methods: ['GET'])]
    #[OA\Get(description: "Endpoint de compatibilité - utiliser /dashboard à la place")]
    #[OA\Tag(name: 'statistique')]
    public function index(): Response
    {
        try {
            // Par défaut, statistiques du mois en cours
            $dateDebut = new DateTime('first day of this month');
            $dateFin = new DateTime('now');
            
            $stats = $this->statistiquesService->getDashboardStats($dateDebut, $dateFin);
            
            return $this->json([
                'success' => true,
                'data' => $stats
            ]);
            
        } catch (\Exception $exception) {
            return $this->json([
                'success' => false,
                'message' => $exception->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }
    }
}