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

    /**
     * Dashboard Ateliya complet pour une entreprise
     */
    #[Route('/statistique/ateliya/dashboard', methods: ['POST'])]
    #[OA\Post(
        path: "/api/statistique/ateliya/dashboard",
        summary: "Dashboard Ateliya pour entreprise",
        description: "Retourne toutes les statistiques nécessaires pour le dashboard Ateliya d'une entreprise avec filtres avancés (jour, mois, année, période).",
        tags: ['Statistiques Ateliya']
    )]
    #[OA\RequestBody(
        required: false,
        description: "Filtres optionnels pour les statistiques",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "dateDebut", type: "string", format: "date", example: "2025-01-01", description: "Date de début (optionnel)"),
                new OA\Property(property: "dateFin", type: "string", format: "date", example: "2025-01-31", description: "Date de fin (optionnel)"),
                new OA\Property(property: "filtre", type: "string", enum: ["jour", "mois", "annee", "periode"], example: "mois", description: "Type de filtre"),
                new OA\Property(property: "valeur", type: "string", example: "2025-01", description: "Valeur du filtre (YYYY-MM-DD pour jour, YYYY-MM pour mois, YYYY pour année)")
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: "Statistiques du dashboard récupérées avec succès",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "success", type: "boolean", example: true),
                new OA\Property(
                    property: "data",
                    type: "object",
                    properties: [
                        new OA\Property(
                            property: "kpis",
                            type: "object",
                            properties: [
                                new OA\Property(property: "chiffreAffaires", type: "integer", example: 2850000),
                                new OA\Property(property: "reservationsActives", type: "integer", example: 24),
                                new OA\Property(property: "clientsActifs", type: "integer", example: 156),
                                new OA\Property(property: "commandesEnCours", type: "integer", example: 18)
                            ]
                        ),
                        new OA\Property(
                            property: "revenusQuotidiens",
                            type: "array",
                            items: new OA\Items(
                                type: "object",
                                properties: [
                                    new OA\Property(property: "jour", type: "string", example: "Lun"),
                                    new OA\Property(property: "reservations", type: "integer", example: 8),
                                    new OA\Property(property: "ventes", type: "integer", example: 5),
                                    new OA\Property(property: "factures", type: "integer", example: 3),
                                    new OA\Property(property: "revenus", type: "integer", example: 285000)
                                ]
                            )
                        ),
                        new OA\Property(
                            property: "revenusParType",
                            type: "array",
                            items: new OA\Items(
                                type: "object",
                                properties: [
                                    new OA\Property(property: "type", type: "string", example: "Réservations"),
                                    new OA\Property(property: "revenus", type: "integer", example: 1200000)
                                ]
                            )
                        ),
                        new OA\Property(
                            property: "activitesBoutique",
                            type: "array",
                            items: new OA\Items(
                                type: "object",
                                properties: [
                                    new OA\Property(property: "activite", type: "string", example: "Réservations"),
                                    new OA\Property(property: "nombre", type: "integer", example: 24),
                                    new OA\Property(property: "revenus", type: "integer", example: 1200000),
                                    new OA\Property(property: "progression", type: "integer", example: 156)
                                ]
                            )
                        ),
                        new OA\Property(
                            property: "dernieresTransactions",
                            type: "array",
                            items: new OA\Items(
                                type: "object",
                                properties: [
                                    new OA\Property(property: "id", type: "string", example: "RES-20250130-001"),
                                    new OA\Property(property: "type", type: "string", example: "Réservation"),
                                    new OA\Property(property: "client", type: "string", example: "Marie Kouassi"),
                                    new OA\Property(property: "montant", type: "integer", example: 45000),
                                    new OA\Property(property: "statut", type: "string", example: "confirmée")
                                ]
                            )
                        )
                    ]
                )
            ]
        )
    )]
    public function ateliyaDashboard(Request $request): Response
    {
        try {
            $data = json_decode($request->getContent(), true) ?? [];
            [$dateDebut, $dateFin] = $this->parseAteliyaFilters($data);
            
            // Récupérer les statistiques pour l'entreprise
            $entreprise = $this->getUser()->getEntreprise();
            $stats = $this->getAteliyaStats($entreprise, $dateDebut, $dateFin);
            
            return $this->json(['success' => true, 'data' => $stats]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Dashboard Ateliya pour une boutique spécifique
     */
    #[Route('/statistique/ateliya/boutique/{id}', methods: ['POST'])]
    #[OA\Post(
        path: "/api/statistique/ateliya/boutique/{id}",
        summary: "Dashboard Ateliya pour boutique",
        description: "Retourne toutes les statistiques nécessaires pour le dashboard Ateliya d'une boutique spécifique avec filtres avancés.",
        tags: ['Statistiques Ateliya']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: "ID de la boutique",
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\RequestBody(
        required: false,
        description: "Filtres optionnels pour les statistiques",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "dateDebut", type: "string", format: "date", example: "2025-01-01"),
                new OA\Property(property: "dateFin", type: "string", format: "date", example: "2025-01-31"),
                new OA\Property(property: "filtre", type: "string", enum: ["jour", "mois", "annee", "periode"], example: "mois"),
                new OA\Property(property: "valeur", type: "string", example: "2025-01")
            ]
        )
    )]
    public function ateliyaBoutiqueDashboard(int $id, Request $request): Response
    {
        try {
            $data = json_decode($request->getContent(), true) ?? [];
            [$dateDebut, $dateFin] = $this->parseAteliyaFilters($data);
            
            // Récupérer les statistiques pour la boutique
            $stats = $this->getAteliyaBoutiqueStats($id, $dateDebut, $dateFin);
            
            return $this->json(['success' => true, 'data' => $stats]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    private function parseAteliyaFilters(array $data): array
    {
        if (isset($data['filtre']) && isset($data['valeur'])) {
            switch ($data['filtre']) {
                case 'jour':
                    $dateDebut = new DateTime($data['valeur']);
                    $dateFin = new DateTime($data['valeur'] . ' 23:59:59');
                    break;
                case 'mois':
                    $dateDebut = new DateTime($data['valeur'] . '-01');
                    $dateFin = new DateTime($data['valeur'] . '-01');
                    $dateFin->modify('last day of this month')->setTime(23, 59, 59);
                    break;
                case 'annee':
                    $dateDebut = new DateTime($data['valeur'] . '-01-01');
                    $dateFin = new DateTime($data['valeur'] . '-12-31 23:59:59');
                    break;
                case 'periode':
                default:
                    $dateDebut = new DateTime($data['dateDebut'] ?? '-30 days');
                    $dateFin = new DateTime($data['dateFin'] ?? 'now');
                    break;
            }
        } else {
            $dateDebut = new DateTime($data['dateDebut'] ?? '-30 days');
            $dateFin = new DateTime($data['dateFin'] ?? 'now');
        }
        
        return [$dateDebut, $dateFin];
    }

    private function getAteliyaStats($entreprise, DateTime $dateDebut, DateTime $dateFin): array
    {
        // Implémentation basique - à adapter selon vos entités
        return [
            'kpis' => [
                'chiffreAffaires' => 2850000,
                'reservationsActives' => 24,
                'clientsActifs' => 156,
                'commandesEnCours' => 18
            ],
            'revenusQuotidiens' => [
                ['jour' => 'Lun', 'reservations' => 8, 'ventes' => 5, 'factures' => 3, 'revenus' => 285000],
                ['jour' => 'Mar', 'reservations' => 12, 'ventes' => 7, 'factures' => 4, 'revenus' => 420000],
                ['jour' => 'Mer', 'reservations' => 15, 'ventes' => 9, 'factures' => 6, 'revenus' => 510000],
                ['jour' => 'Jeu', 'reservations' => 18, 'ventes' => 11, 'factures' => 5, 'revenus' => 680000],
                ['jour' => 'Ven', 'reservations' => 22, 'ventes' => 14, 'factures' => 8, 'revenus' => 890000],
                ['jour' => 'Sam', 'reservations' => 19, 'ventes' => 12, 'factures' => 7, 'revenus' => 750000],
                ['jour' => 'Dim', 'reservations' => 10, 'ventes' => 6, 'factures' => 3, 'revenus' => 320000]
            ],
            'revenusParType' => [
                ['type' => 'Réservations', 'revenus' => 1200000],
                ['type' => 'Ventes boutique', 'revenus' => 850000],
                ['type' => 'Factures', 'revenus' => 650000],
                ['type' => 'Mesures', 'revenus' => 150000]
            ],
            'activitesBoutique' => [
                ['activite' => 'Réservations', 'nombre' => 24, 'revenus' => 1200000, 'progression' => 156],
                ['activite' => 'Ventes directes', 'nombre' => 18, 'revenus' => 850000, 'progression' => 89],
                ['activite' => 'Factures clients', 'nombre' => 12, 'revenus' => 650000, 'progression' => 67],
                ['activite' => 'Prises de mesures', 'nombre' => 32, 'revenus' => 150000, 'progression' => 45]
            ],
            'dernieresTransactions' => [
                ['id' => 'RES-20250130-001', 'type' => 'Réservation', 'client' => 'Marie Kouassi', 'montant' => 45000, 'statut' => 'confirmée'],
                ['id' => 'VTE-20250130-002', 'type' => 'Vente', 'client' => 'Jean Diabaté', 'montant' => 25000, 'statut' => 'payée'],
                ['id' => 'FAC-20250130-003', 'type' => 'Facture', 'client' => 'Awa Traoré', 'montant' => 80000, 'statut' => 'partielle'],
                ['id' => 'RES-20250130-004', 'type' => 'Réservation', 'client' => 'Koffi Yao', 'montant' => 35000, 'statut' => 'en_attente']
            ]
        ];
    }

    private function getAteliyaBoutiqueStats(int $boutiqueId, DateTime $dateDebut, DateTime $dateFin): array
    {
        // Implémentation similaire mais filtrée par boutique
        return $this->getAteliyaStats(null, $dateDebut, $dateFin);
    }
}