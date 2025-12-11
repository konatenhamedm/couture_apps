<?php

namespace App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use App\Entity\PaiementBoutique;
use App\Entity\PaiementFacture;
use App\Entity\PaiementReservation;
use App\Repository\PaiementBoutiqueRepository;
use App\Repository\PaiementFactureRepository;
use App\Repository\PaiementReservationRepository;
use App\Repository\BoutiqueRepository;
use App\Repository\SurccursaleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Attributes as OA;

#[Route('/api')]
class ApiVenteController extends ApiInterface
{
    /**
     * Debug: Voir tous les paiements d'une boutique avec leurs dates
     */
    #[Route('/vente/boutique/{id}/debug', methods: ['GET'])]
    #[OA\Get(
        path: "/api/vente/boutique/{id}/debug",
        summary: "Debug - Voir tous les paiements avec dates",
        description: "Affiche tous les paiements d'une boutique avec leurs dates createdAt pour debug",
        tags: ['Ventes']
    )]
    public function debugPaiementsBoutique(
        int $id,
        PaiementBoutiqueRepository $paiementBoutiqueRepository,
        BoutiqueRepository $boutiqueRepository
    ): Response {
        try {
            $boutique = $boutiqueRepository->find($id);
            if (!$boutique) {
                return $this->json(['success' => false, 'message' => 'Boutique non trouvée'], 404);
            }

            // Récupérer tous les paiements avec leurs dates
            $paiementsDebug = $paiementBoutiqueRepository->findAllByBoutique($boutique);

            // Récupérer aussi les objets complets
            $paiementsComplets = $paiementBoutiqueRepository->findAllByBoutique($boutique);
            return    $this->responseData([
                'success' => true,
                'boutique_id' => $id,
                'total_paiements' => count($paiementsDebug),
                'paiements_avec_dates' => $paiementsDebug,
                'paiements_complets' => $paiementsComplets,
                'note' => 'Vérifiez si createdAt est NULL ou dans quelle période sont les dates'
            ], 'paiement_boutique', ['Content-Type' => 'application/json']);

            /*   return $this->json([
                'success' => true,
                'boutique_id' => $id,
                'total_paiements' => count($paiementsDebug),
                'paiements_avec_dates' => $paiementsDebug,
                'note' => 'Vérifiez si createdAt est NULL ou dans quelle période sont les dates'
            ]); */
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Liste des paiements boutique avec filtre de période
     */
    #[Route('/vente/boutique/{id}', methods: ['POST'])]
    #[OA\Post(
        path: "/api/vente/boutique/{id}",
        summary: "Liste des paiements boutique avec filtre de période",
        description: "Retourne la liste des paiements d'une boutique filtrés par période (aujourd'hui ou 7 derniers jours)",
        tags: ['Ventes']
    )]
    #[OA\Parameter(
        name: "id",
        description: "ID de la boutique",
        in: "path",
        required: true,
        schema: new OA\Schema(type: "integer")
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(
                    property: "periode",
                    type: "string",
                    enum: ["aujourd_hui", "7_derniers_jours", "personnalisee"],
                    example: "aujourd_hui",
                    description: "Période de filtrage: 'aujourd_hui', '7_derniers_jours' ou 'personnalisee'"
                ),
                new OA\Property(
                    property: "date_debut",
                    type: "string",
                    format: "date",
                    example: "2025-01-01",
                    description: "Date de début (format: YYYY-MM-DD). Requis si periode='personnalisee'"
                ),
                new OA\Property(
                    property: "date_fin",
                    type: "string",
                    format: "date",
                    example: "2025-01-31",
                    description: "Date de fin (format: YYYY-MM-DD). Requis si periode='personnalisee'"
                )
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: "Liste des paiements boutique",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "success", type: "boolean", example: true),
                new OA\Property(
                    property: "data",
                    type: "array",
                    items: new OA\Items(type: "object")
                )
            ]
        )
    )]
    public function getVentesByBoutique(
        int $id,
        Request $request,
        PaiementBoutiqueRepository $paiementBoutiqueRepository,
        BoutiqueRepository $boutiqueRepository
    ): Response {
        try {
            $boutique = $boutiqueRepository->find($id);
            if (!$boutique) {
                return $this->json(['success' => false, 'message' => 'Boutique non trouvée'], 404);
            }

            $data = json_decode($request->getContent(), true);
            $periode = $data['periode'] ?? 'aujourd_hui';

            // Définir les dates selon la période
            $now = new \DateTime();
            $startDate = null;
            $endDate = $now;

            if ($periode === 'aujourd_hui') {
                $startDate = (new \DateTime())->setTime(0, 0, 0);
            } elseif ($periode === '7_derniers_jours') {
                $startDate = (new \DateTime())->modify('-7 days')->setTime(0, 0, 0);
            } elseif ($periode === 'personnalisee') {
                // Vérifier que les dates sont fournies
                if (empty($data['date_debut']) || empty($data['date_fin'])) {
                    return $this->json(['success' => false, 'message' => 'Les champs date_debut et date_fin sont requis pour une période personnalisée'], 400);
                }

                try {
                    $startDate = new \DateTime($data['date_debut']);
                    $startDate->setTime(0, 0, 0);
                    $endDate = new \DateTime($data['date_fin']);
                    $endDate->setTime(23, 59, 59);
                } catch (\Exception $e) {
                    return $this->json(['success' => false, 'message' => 'Format de date invalide. Utilisez le format YYYY-MM-DD'], 400);
                }
            } else {
                return $this->json(['success' => false, 'message' => 'Période invalide. Utilisez "aujourd_hui", "7_derniers_jours" ou "personnalisee"'], 400);
            }

            // Utiliser la méthode du repository
            $paiements = $paiementBoutiqueRepository->findByBoutiqueAndPeriod($boutique, $startDate, $endDate);

            // Compter tous les paiements de la boutique pour debug
            $totalPaiements = $paiementBoutiqueRepository->countByBoutique($boutique);
            return    $this->responseData([
                'success' => true,
                'data' => $paiements,
                'count' => count($paiements),
                'total_paiements_boutique' => $totalPaiements,
                'periode' => [
                    'debut' => $startDate->format('Y-m-d H:i:s'),
                    'fin' => $endDate->format('Y-m-d H:i:s')
                ]
            ], 'paiement_boutique', ['Content-Type' => 'application/json']);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Liste des paiements facture avec filtre de période
     */
    #[Route('/vente/facture/{succursaleId}', methods: ['POST'])]
    #[OA\Post(
        path: "/api/vente/facture",
        summary: "Liste des paiements facture avec filtre de période",
        description: "Retourne la liste des paiements facture filtrés par période (aujourd'hui ou 7 derniers jours)",
        tags: ['Ventes']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(
                    property: "periode",
                    type: "string",
                    enum: ["aujourd_hui", "7_derniers_jours", "personnalisee"],
                    example: "aujourd_hui",
                    description: "Période de filtrage: 'aujourd_hui', '7_derniers_jours' ou 'personnalisee'"
                ),
                new OA\Property(
                    property: "date_debut",
                    type: "string",
                    format: "date",
                    example: "2025-01-01",
                    description: "Date de début (format: YYYY-MM-DD). Requis si periode='personnalisee'"
                ),
                new OA\Property(
                    property: "date_fin",
                    type: "string",
                    format: "date",
                    example: "2025-01-31",
                    description: "Date de fin (format: YYYY-MM-DD). Requis si periode='personnalisee'"
                )
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: "Liste des paiements facture",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "success", type: "boolean", example: true),
                new OA\Property(
                    property: "data",
                    type: "array",
                    items: new OA\Items(type: "object")
                )
            ]
        )
    )]
    public function getPaiementsFacture(
        Request $request,
        $succursaleId,
        PaiementFactureRepository $paiementFactureRepository
    ): Response {
        try {
            $data = json_decode($request->getContent(), true);
            $periode = $data['periode'] ?? 'aujourd_hui';

            // Définir les dates selon la période
            $now = new \DateTime();
            $startDate = null;
            $endDate = $now;

            if ($periode === 'aujourd_hui') {
                $startDate = (new \DateTime())->setTime(0, 0, 0);
            } elseif ($periode === '7_derniers_jours') {
                $startDate = (new \DateTime())->modify('-7 days')->setTime(0, 0, 0);
            } elseif ($periode === 'personnalisee') {
                // Vérifier que les dates sont fournies
                if (empty($data['date_debut']) || empty($data['date_fin'])) {
                    return $this->json(['success' => false, 'message' => 'Les champs date_debut et date_fin sont requis pour une période personnalisée'], 400);
                }

                try {
                    $startDate = new \DateTime($data['date_debut']);
                    $startDate->setTime(0, 0, 0);
                    $endDate = new \DateTime($data['date_fin']);
                    $endDate->setTime(23, 59, 59);
                } catch (\Exception $e) {
                    return $this->json(['success' => false, 'message' => 'Format de date invalide. Utilisez le format YYYY-MM-DD'], 400);
                }
            } else {
                return $this->json(['success' => false, 'message' => 'Période invalide. Utilisez "aujourd_hui", "7_derniers_jours" ou "personnalisee"'], 400);
            }

            // Utiliser la méthode du repository
            $paiements = $paiementFactureRepository->findByPeriod($startDate, $endDate, $succursaleId);

            // Compter tous les paiements pour debug
            $totalPaiements = $paiementFactureRepository->countAll();

            return  $this->responseData([
                'success' => true,
                'data' => $paiements,
                'count' => count($paiements),
                'total_paiements' => $totalPaiements,
                'periode' => [
                    'debut' => $startDate->format('Y-m-d H:i:s'),
                    'fin' => $endDate->format('Y-m-d H:i:s')
                ]
            ], 'paiement_boutique', ['Content-Type' => 'application/json']);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Debug: Voir tous les paiements réservation d'une boutique avec leurs dates
     */
    #[Route('/vente/reservation/boutique/{id}/debug', methods: ['GET'])]
    #[OA\Get(
        path: "/api/vente/reservation/boutique/{id}/debug",
        summary: "Debug - Voir tous les paiements réservation avec dates",
        description: "Affiche tous les paiements réservation d'une boutique avec leurs dates createdAt pour debug",
        tags: ['Ventes']
    )]
    public function debugPaiementsReservationBoutique(
        int $id,
        PaiementReservationRepository $paiementReservationRepository,
        BoutiqueRepository $boutiqueRepository
    ): Response {
        try {
            $boutique = $boutiqueRepository->find($id);
            if (!$boutique) {
                return $this->json(['success' => false, 'message' => 'Boutique non trouvée'], 404);
            }

            // Récupérer tous les paiements réservation avec leurs dates
            $paiementsDebug = $paiementReservationRepository->findAllByBoutiqueWithDates($boutique);

            return $this->json([
                'success' => true,
                'boutique_id' => $id,
                'total_paiements_reservation' => count($paiementsDebug),
                'paiements_avec_dates' => $paiementsDebug,
                'note' => 'Vérifiez si createdAt est NULL ou dans quelle période sont les dates'
            ]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Liste des paiements réservation d'une boutique avec filtre de période
     */
    #[Route('/vente/reservation/boutique/{id}', methods: ['POST'])]
    #[OA\Post(
        path: "/api/vente/reservation/boutique/{id}",
        summary: "Liste des paiements réservation d'une boutique avec filtre de période",
        description: "Retourne la liste des paiements réservation d'une boutique filtrés par période (aujourd'hui, 7 derniers jours ou personnalisée)",
        tags: ['Ventes']
    )]
    #[OA\Parameter(
        name: "id",
        description: "ID de la boutique",
        in: "path",
        required: true,
        schema: new OA\Schema(type: "integer")
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(
                    property: "periode",
                    type: "string",
                    enum: ["aujourd_hui", "7_derniers_jours", "personnalisee"],
                    example: "aujourd_hui",
                    description: "Période de filtrage: 'aujourd_hui', '7_derniers_jours' ou 'personnalisee'"
                ),
                new OA\Property(
                    property: "date_debut",
                    type: "string",
                    format: "date",
                    example: "2025-01-01",
                    description: "Date de début (format: YYYY-MM-DD). Requis si periode='personnalisee'"
                ),
                new OA\Property(
                    property: "date_fin",
                    type: "string",
                    format: "date",
                    example: "2025-01-31",
                    description: "Date de fin (format: YYYY-MM-DD). Requis si periode='personnalisee'"
                )
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: "Liste des paiements réservation de la boutique",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "success", type: "boolean", example: true),
                new OA\Property(
                    property: "data",
                    type: "array",
                    items: new OA\Items(type: "object")
                )
            ]
        )
    )]
    public function getPaiementsReservationByBoutique(
        int $id,
        Request $request,
        PaiementReservationRepository $paiementReservationRepository,
        BoutiqueRepository $boutiqueRepository
    ): Response {
        try {
            $boutique = $boutiqueRepository->find($id);
            if (!$boutique) {
                return $this->json(['success' => false, 'message' => 'Boutique non trouvée'], 404);
            }

            $data = json_decode($request->getContent(), true);
            $periode = $data['periode'] ?? 'aujourd_hui';

            // Définir les dates selon la période
            $now = new \DateTime();
            $startDate = null;
            $endDate = $now;

            if ($periode === 'aujourd_hui') {
                $startDate = (new \DateTime())->setTime(0, 0, 0);
            } elseif ($periode === '7_derniers_jours') {
                $startDate = (new \DateTime())->modify('-7 days')->setTime(0, 0, 0);
            } elseif ($periode === 'personnalisee') {
                // Vérifier que les dates sont fournies
                if (empty($data['date_debut']) || empty($data['date_fin'])) {
                    return $this->json(['success' => false, 'message' => 'Les champs date_debut et date_fin sont requis pour une période personnalisée'], 400);
                }

                try {
                    $startDate = new \DateTime($data['date_debut']);
                    $startDate->setTime(0, 0, 0);
                    $endDate = new \DateTime($data['date_fin']);
                    $endDate->setTime(23, 59, 59);
                } catch (\Exception $e) {
                    return $this->json(['success' => false, 'message' => 'Format de date invalide. Utilisez le format YYYY-MM-DD'], 400);
                }
            } else {
                return $this->json(['success' => false, 'message' => 'Période invalide. Utilisez "aujourd_hui", "7_derniers_jours" ou "personnalisee"'], 400);
            }

            // Utiliser la méthode du repository
            $paiements = $paiementReservationRepository->findByBoutiqueAndPeriod($boutique, $startDate, $endDate);

            dd($paiements, $startDate, $endDate);

            // Compter tous les paiements réservation de la boutique pour debug
            $totalPaiements = $paiementReservationRepository->countByBoutique($boutique);

            return $this->responseData([
                'success' => true,
                'data' => $paiements,
                'count' => count($paiements),
                'total_paiements_reservation_boutique' => $totalPaiements,
                'periode' => [
                    'debut' => $startDate->format('Y-m-d H:i:s'),
                    'fin' => $endDate->format('Y-m-d H:i:s')
                ]
            ], 'paiement_boutique', ['Content-Type' => 'application/json']);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Liste des paiements réservation avec filtre de période (tous)
     */
    #[Route('/vente/reservation', methods: ['POST'])]
    #[OA\Post(
        path: "/api/vente/reservation",
        summary: "Liste des paiements réservation avec filtre de période",
        description: "Retourne la liste de tous les paiements réservation filtrés par période (aujourd'hui ou 7 derniers jours)",
        tags: ['Ventes']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(
                    property: "periode",
                    type: "string",
                    enum: ["aujourd_hui", "7_derniers_jours", "personnalisee"],
                    example: "aujourd_hui",
                    description: "Période de filtrage: 'aujourd_hui', '7_derniers_jours' ou 'personnalisee'"
                ),
                new OA\Property(
                    property: "date_debut",
                    type: "string",
                    format: "date",
                    example: "2025-01-01",
                    description: "Date de début (format: YYYY-MM-DD). Requis si periode='personnalisee'"
                ),
                new OA\Property(
                    property: "date_fin",
                    type: "string",
                    format: "date",
                    example: "2025-01-31",
                    description: "Date de fin (format: YYYY-MM-DD). Requis si periode='personnalisee'"
                )
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: "Liste des paiements réservation",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "success", type: "boolean", example: true),
                new OA\Property(
                    property: "data",
                    type: "array",
                    items: new OA\Items(type: "object")
                )
            ]
        )
    )]
    public function getPaiementsReservation(
        Request $request,
        PaiementReservationRepository $paiementReservationRepository
    ): Response {
        try {
            $data = json_decode($request->getContent(), true);
            $periode = $data['periode'] ?? 'aujourd_hui';

            // Définir les dates selon la période
            $now = new \DateTime();
            $startDate = null;
            $endDate = $now;

            if ($periode === 'aujourd_hui') {
                $startDate = (new \DateTime())->setTime(0, 0, 0);
            } elseif ($periode === '7_derniers_jours') {
                $startDate = (new \DateTime())->modify('-7 days')->setTime(0, 0, 0);
            } elseif ($periode === 'personnalisee') {
                // Vérifier que les dates sont fournies
                if (empty($data['date_debut']) || empty($data['date_fin'])) {
                    return $this->json(['success' => false, 'message' => 'Les champs date_debut et date_fin sont requis pour une période personnalisée'], 400);
                }

                try {
                    $startDate = new \DateTime($data['date_debut']);
                    $startDate->setTime(0, 0, 0);
                    $endDate = new \DateTime($data['date_fin']);
                    $endDate->setTime(23, 59, 59);
                } catch (\Exception $e) {
                    return $this->json(['success' => false, 'message' => 'Format de date invalide. Utilisez le format YYYY-MM-DD'], 400);
                }
            } else {
                return $this->json(['success' => false, 'message' => 'Période invalide. Utilisez "aujourd_hui", "7_derniers_jours" ou "personnalisee"'], 400);
            }

            // Utiliser la méthode du repository
            $paiements = $paiementReservationRepository->findByPeriod($startDate, $endDate);

            // Compter tous les paiements pour debug
            $totalPaiements = $paiementReservationRepository->countAll();

            return $this->responseData([
                'success' => true,
                'data' => $paiements,
                'count' => count($paiements),
                'total_paiements' => $totalPaiements,
                'periode' => [
                    'debut' => $startDate->format('Y-m-d H:i:s'),
                    'fin' => $endDate->format('Y-m-d H:i:s')
                ]
            ], 'paiement_boutique', ['Content-Type' => 'application/json']);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Créer une nouvelle vente
     */
    #[Route('/vente', methods: ['POST'])]
    #[OA\Post(
        path: "/api/vente",
        summary: "Créer une vente",
        description: "Crée une nouvelle vente avec ses lignes",
        tags: ['Ventes']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "boutiqueId", type: "integer", example: 1),
                new OA\Property(property: "clientId", type: "integer", example: 1),
                new OA\Property(property: "modePaiement", type: "string", example: "Espèces"),
                new OA\Property(
                    property: "lignes",
                    type: "array",
                    items: new OA\Items(
                        type: "object",
                        properties: [
                            new OA\Property(property: "produit", type: "string", example: "Tissu Wax"),
                            new OA\Property(property: "quantite", type: "integer", example: 2),
                            new OA\Property(property: "prixUnitaire", type: "number", example: 15000)
                        ]
                    )
                )
            ]
        )
    )]
    public function createVente(
        Request $request,
        EntityManagerInterface $em,
        BoutiqueRepository $boutiqueRepository
    ): Response {
        try {
            $data = json_decode($request->getContent(), true);

            $paiement = new PaiementBoutique();
            $paiement->setMontant($data['montant'] ?? 0);
            $paiement->setReference('VTE-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT));
            $paiement->setType($data['modePaiement'] ?? 'Espèces');

            if (isset($data['boutiqueId'])) {
                $boutique = $boutiqueRepository->find($data['boutiqueId']);
                $paiement->setBoutique($boutique);
            }

            $em->persist($paiement);
            $em->flush();

            return $this->json(['success' => true, 'data' => ['id' => $paiement->getId()]]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Détails d'une vente
     */
    #[Route('/vente/{id}', methods: ['GET'])]
    #[OA\Get(
        path: "/api/vente/{id}",
        summary: "Détails d'une vente",
        description: "Retourne les détails d'une vente avec ses lignes",
        tags: ['Ventes']
    )]
    public function getVente(int $id, PaiementBoutiqueRepository $paiementBoutiqueRepository): Response
    {
        try {
            // Simulation de détails de vente
            $data = [
                'id' => $id,
                'numero' => 'VTE-' . str_pad($id, 6, '0', STR_PAD_LEFT),
                'date' => date('Y-m-d H:i:s', strtotime('-' . rand(0, 30) . ' days')),
                'montant' => rand(25000, 75000),
                'modePaiement' => ['Espèces', 'Mobile Money', 'Carte bancaire'][rand(0, 2)],
                'client' => [
                    'id' => 1,
                    'nom' => 'Diallo',
                    'prenom' => 'Aminata'
                ],
                'boutique' => [
                    'id' => 1,
                    'libelle' => 'Boutique Centre-ville'
                ],
                'ligneVentes' => [
                    [
                        'id' => 1,
                        'produit' => 'Tissu Wax',
                        'quantite' => 2,
                        'prixUnitaire' => 15000,
                        'total' => 30000
                    ]
                ]
            ];

            return $this->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Statistiques des ventes
     */
    #[Route('/vente/stats/boutique/{id}', methods: ['GET'])]
    #[OA\Get(
        path: "/api/vente/stats/boutique/{id}",
        summary: "Statistiques des ventes",
        description: "Retourne les statistiques des ventes d'une boutique",
        tags: ['Ventes']
    )]
    public function getVentesStats(
        int $id,
        PaiementBoutiqueRepository $paiementBoutiqueRepository,
        BoutiqueRepository $boutiqueRepository
    ): Response {
        try {
            $boutique = $boutiqueRepository->find($id);
            if (!$boutique) {
                return $this->json(['success' => false, 'message' => 'Boutique non trouvée'], 404);
            }

            $today = new \DateTime();
            $thisMonth = new \DateTime('first day of this month');

            // Statistiques simulées
            $stats = [
                'aujourd_hui' => [
                    'nombre' => rand(3, 8),
                    'montant' => rand(75000, 150000)
                ],
                'ce_mois' => [
                    'nombre' => rand(45, 85),
                    'montant' => rand(1200000, 2500000)
                ]
            ];

            return $this->json(['success' => true, 'data' => $stats]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }
}
