<?php

namespace App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use App\Service\StatistiquesService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Request;
use DateTime;

#[Route('/api')]
class ApiStatistiqueController extends ApiInterface
{
  

    #[Route('/statistique/dashboard', methods: ['POST'])]
    #[OA\Post(
        description: "Statistiques principales du dashboard avec métriques avancées",
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "dateDebut", type: "string", format: "date", example: "2025-01-01"),
                    new OA\Property(property: "dateFin", type: "string", format: "date", example: "2025-01-31"),
                    new OA\Property(property: "periode", type: "string", enum: ["7j", "30j", "3m"], example: "30j")
                ]
            )
        )
    )]
    #[OA\Tag(name: 'Statistiques')]
    public function dashboard(Request $request): Response
    {
        try {
            $data = json_decode($request->getContent(), true) ?? [];
            [$dateDebut, $dateFin] = $this->parseDateRange($data);
            
            $stats = $this->statistiquesService->getDashboardStats($dateDebut, $dateFin);
            
            return $this->json(['success' => true, 'data' => $stats]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    #[Route('/statistique/revenus/evolution', methods: ['POST'])]
    #[OA\Post(
        description: "Évolution des revenus (graphique linéaire)",
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "dateDebut", type: "string", format: "date"),
                    new OA\Property(property: "dateFin", type: "string", format: "date"),
                    new OA\Property(property: "periode", type: "string", enum: ["7j", "30j", "3m"]),
                    new OA\Property(property: "groupBy", type: "string", enum: ["jour", "semaine", "mois"])
                ]
            )
        )
    )]
    #[OA\Tag(name: 'Statistiques')]
    public function evolutionRevenus(Request $request): Response
    {
        try {
            $data = json_decode($request->getContent(), true) ?? [];
            [$dateDebut, $dateFin] = $this->parseDateRange($data);
            $groupBy = $data['groupBy'] ?? 'jour';
            
            $stats = $this->statistiquesService->getEvolutionRevenus($dateDebut, $dateFin, $groupBy);
            
            return $this->json(['success' => true, 'data' => $stats]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    #[Route('/statistique/commandes/evolution', methods: ['POST'])]
    #[OA\Post(
        description: "Évolution des commandes (graphique linéaire)",
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "dateDebut", type: "string", format: "date"),
                    new OA\Property(property: "dateFin", type: "string", format: "date"),
                    new OA\Property(property: "periode", type: "string", enum: ["7j", "30j", "3m"]),
                    new OA\Property(property: "groupBy", type: "string", enum: ["jour", "semaine", "mois"])
                ]
            )
        )
    )]
    #[OA\Tag(name: 'Statistiques')]
    public function evolutionCommandes(Request $request): Response
    {
        try {
            $data = json_decode($request->getContent(), true) ?? [];
            [$dateDebut, $dateFin] = $this->parseDateRange($data);
            $groupBy = $data['groupBy'] ?? 'jour';
            
            $stats = $this->statistiquesService->getEvolutionCommandes($dateDebut, $dateFin, $groupBy);
            
            return $this->json(['success' => true, 'data' => $stats]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    #[Route('/statistique/revenus/par-type', methods: ['POST'])]
    #[OA\Post(
        description: "Répartition des revenus par type (graphique camembert)",
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "dateDebut", type: "string", format: "date"),
                    new OA\Property(property: "dateFin", type: "string", format: "date"),
                    new OA\Property(property: "periode", type: "string", enum: ["7j", "30j", "3m"])
                ]
            )
        )
    )]
    #[OA\Tag(name: 'Statistiques')]
    public function revenusParType(Request $request): Response
    {
        try {
            $data = json_decode($request->getContent(), true) ?? [];
            [$dateDebut, $dateFin] = $this->parseDateRange($data);
            
            $stats = $this->statistiquesService->getRevenusParType($dateDebut, $dateFin);
            
            return $this->json(['success' => true, 'data' => $stats]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    #[Route('/statistique/top-clients', methods: ['POST'])]
    #[OA\Post(
        description: "Top clients par montant dépensé",
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "dateDebut", type: "string", format: "date"),
                    new OA\Property(property: "dateFin", type: "string", format: "date"),
                    new OA\Property(property: "periode", type: "string", enum: ["7j", "30j", "3m"]),
                    new OA\Property(property: "limit", type: "integer", example: 10)
                ]
            )
        )
    )]
    #[OA\Tag(name: 'Statistiques')]
    public function topClients(Request $request): Response
    {
        try {
            $data = json_decode($request->getContent(), true) ?? [];
            [$dateDebut, $dateFin] = $this->parseDateRange($data);
            $limit = $data['limit'] ?? 10;
            
            $stats = $this->statistiquesService->getTopClients($dateDebut, $dateFin, $limit);
            
            return $this->json(['success' => true, 'data' => $stats]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    #[Route('/statistique/comparatif', methods: ['POST'])]
    #[OA\Post(
        description: "Comparaison avec période précédente",
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "dateDebut", type: "string", format: "date"),
                    new OA\Property(property: "dateFin", type: "string", format: "date"),
                    new OA\Property(property: "periode", type: "string", enum: ["7j", "30j", "3m"])
                ]
            )
        )
    )]
    #[OA\Tag(name: 'Statistiques')]
    public function comparatif(Request $request): Response
    {
        try {
            $data = json_decode($request->getContent(), true) ?? [];
            [$dateDebut, $dateFin] = $this->parseDateRange($data);
            
            $stats = $this->statistiquesService->getComparatif($dateDebut, $dateFin);
            
            return $this->json(['success' => true, 'data' => $stats]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    private function parseDateRange(array $data): array
    {
        if (isset($data['periode'])) {
            $dateFin = new DateTime('now');
            $dateDebut = match ($data['periode']) {
                '7j' => new DateTime('-7 days'),
                '30j' => new DateTime('-30 days'),
                '3m' => new DateTime('-3 months'),
                default => new DateTime('-30 days')
            };
        } else {
            $dateDebut = new DateTime($data['dateDebut'] ?? '-30 days');
            $dateFin = new DateTime($data['dateFin'] ?? 'now');
        }
        
        return [$dateDebut, $dateFin];
    }
}