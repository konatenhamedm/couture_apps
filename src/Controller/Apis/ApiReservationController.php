<?php

namespace App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use App\DTO\ReservationDTO;
use App\Entity\Boutique;
use App\Enum\ReservationStatus;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Entity\Reservation;
use App\Entity\Caisse;
use App\Entity\CaisseBoutique;
use App\Entity\CaisseReservation;
use App\Entity\Client;
use App\Entity\LigneReservation;
use App\Entity\Paiement;
use App\Entity\PaiementReservation;
use App\Repository\BoutiqueRepository;
use App\Repository\CaisseBoutiqueRepository;
use App\Repository\CaisseRepository;
use App\Repository\ReservationRepository;
use App\Repository\CaisseReservationRepository;
use App\Repository\ClientRepository;
use App\Repository\ModeleBoutiqueRepository;
use App\Repository\ModeleRepository;
use App\Repository\PaiementReservationRepository;
use App\Repository\TypeUserRepository;
use App\Repository\UserRepository;
use App\Service\Utils;
use App\Service\ReservationWorkflowService;
use App\Service\StockDeficit;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Attribute\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

/**
 * Contrôleur pour la gestion des réservations de vêtements
 * Permet aux clients de réserver des articles avec acompte et retrait ultérieur
 */
#[Route('/api/reservation', name: 'api_reservation')]
#[OA\Tag(name: 'reservation', description: 'Gestion des réservations de vêtements avec acomptes et retraits programmés')]
class ApiReservationController extends ApiInterface
{
    /**
     * Liste toutes les réservations du système
     */
    #[Route('/', methods: ['GET'])]
    #[OA\Get(
        path: "/api/reservation/",
        summary: "Lister toutes les réservations",
        description: "Retourne la liste paginée de toutes les réservations du système, incluant les détails des clients, montants, acomptes et dates de retrait. Supporte le filtrage par statut.",
        tags: ['reservation']
    )]
    #[OA\Parameter(
        name: 'status',
        in: 'query',
        required: false,
        description: "Filtrer par statut de réservation. Valeurs possibles: en_attente, confirmee, annulee. Peut être une valeur unique ou plusieurs valeurs séparées par des virgules.",
        schema: new OA\Schema(type: 'string', example: 'en_attente'),
        examples: [
            new OA\Examples(example: 'single', summary: 'Un seul statut', value: 'en_attente'),
            new OA\Examples(example: 'multiple', summary: 'Plusieurs statuts', value: 'en_attente,confirmee')
        ]
    )]
    #[OA\Response(
        response: 200,
        description: "Liste des réservations récupérée avec succès",
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(
                type: "object",
                properties: [
                    new OA\Property(property: "id", type: "integer", example: 1, description: "Identifiant unique de la réservation"),
                    new OA\Property(property: "status", type: "string", example: "en_attente", description: "Statut de la réservation"),
                    new OA\Property(property: "montant", type: "number", format: "float", example: 50000, description: "Montant total de la réservation en FCFA"),
                    new OA\Property(property: "avance", type: "number", format: "float", example: 20000, description: "Acompte versé en FCFA"),
                    new OA\Property(property: "reste", type: "number", format: "float", example: 30000, description: "Reste à payer en FCFA"),
                    new OA\Property(property: "dateRetrait", type: "string", format: "date-time", example: "2025-02-15T10:00:00+00:00", description: "Date prévue de retrait"),
                    new OA\Property(
                        property: "client",
                        type: "object",
                        description: "Client ayant effectué la réservation",
                        properties: [
                            new OA\Property(property: "id", type: "integer", example: 5),
                            new OA\Property(property: "nom", type: "string", example: "Kouassi"),
                            new OA\Property(property: "prenoms", type: "string", example: "Jean"),
                            new OA\Property(property: "telephone", type: "string", example: "+225 07 12 34 56 78")
                        ]
                    ),
                    new OA\Property(property: "boutique", type: "object", description: "Boutique où récupérer la réservation"),
                    new OA\Property(
                        property: "ligneReservations",
                        type: "array",
                        description: "Liste des articles réservés",
                        items: new OA\Items(
                            type: "object",
                            properties: [
                                new OA\Property(property: "id", type: "integer", example: 1),
                                new OA\Property(property: "quantite", type: "integer", example: 2),
                                new OA\Property(property: "modele", type: "object", description: "Modèle réservé")
                            ]
                        )
                    ),
                    new OA\Property(property: "createdAt", type: "string", format: "date-time", example: "2025-01-30T14:30:00+00:00")
                ]
            )
        )
    )]
    #[OA\Response(
        response: 400,
        description: "Valeur de statut invalide",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "status", type: "string", example: "ERROR"),
                new OA\Property(property: "message", type: "string", example: "Statut invalide: 'invalid'. Valeurs autorisées: en_attente, confirmee, annulee")
            ]
        )
    )]
    #[OA\Response(response: 500, description: "Erreur serveur lors de la récupération")]
    public function index(Request $request, ReservationRepository $reservationRepository): Response
    {
        try {
            // Récupérer le paramètre de filtrage par statut
            $statusFilter = $request->query->get('status');

            // Construire les critères de recherche
            $criteria = [];

            // Si un filtre de statut est fourni, valider et l'ajouter aux critères
            if ($statusFilter !== null && $statusFilter !== '') {
                $validStatuses = [
                    ReservationStatus::EN_ATTENTE->value,
                    ReservationStatus::CONFIRMEE->value,
                    ReservationStatus::ANNULEE->value
                ];

                // Support de plusieurs statuts séparés par des virgules
                $requestedStatuses = array_map('trim', explode(',', $statusFilter));

                // Valider chaque statut
                foreach ($requestedStatuses as $status) {
                    if (!in_array($status, $validStatuses)) {
                        return $this->json([
                            'status' => 'ERROR',
                            'message' => "Statut invalide: '{$status}'. Valeurs autorisées: " . implode(', ', $validStatuses)
                        ], 400);
                    }
                }

                // Si un seul statut, utiliser une égalité simple
                if (count($requestedStatuses) === 1) {
                    $criteria['status'] = $requestedStatuses[0];
                    $reservations = $this->paginationService->paginate($reservationRepository->findBy($criteria));
                } else {
                    // Si plusieurs statuts, utiliser une requête IN
                    $reservations = $this->paginationService->paginate(
                        $reservationRepository->findByMultipleStatuses($requestedStatuses)
                    );
                }
            } else {
                // Pas de filtre, retourner toutes les réservations
                $reservations = $this->paginationService->paginate($reservationRepository->findAll());
            }

            $response = $this->responseData($reservations, 'group1', ['Content-Type' => 'application/json']);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            $this->setMessage("Erreur lors de la récupération des réservations");
            $response = $this->response([]);
        }

        return $response;
    }

    /**
     * Liste les réservations selon les droits de l'utilisateur (entreprise ou boutique)
     */
    #[Route('/entreprise', methods: ['GET'])]
    #[OA\Get(
        path: "/api/reservation/entreprise",
        summary: "Lister les réservations selon les droits utilisateur",
        description: "Retourne la liste des réservations filtrée selon le type d'utilisateur : Super-admin voit toutes les réservations de l'entreprise, autres utilisateurs voient uniquement les réservations de leur boutique. Supporte le filtrage par statut.",
        tags: ['reservation']
    )]
    #[OA\Parameter(
        name: 'status',
        in: 'query',
        required: false,
        description: "Filtrer par statut de réservation. Valeurs possibles: en_attente, confirmee, annulee. Peut être une valeur unique ou plusieurs valeurs séparées par des virgules.",
        schema: new OA\Schema(type: 'string', example: 'en_attente'),
        examples: [
            new OA\Examples(example: 'single', summary: 'Un seul statut', value: 'en_attente'),
            new OA\Examples(example: 'multiple', summary: 'Plusieurs statuts', value: 'en_attente,confirmee')
        ]
    )]
    #[OA\Response(
        response: 200,
        description: "Liste des réservations récupérée avec succès",
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(
                type: "object",
                properties: [
                    new OA\Property(property: "id", type: "integer", example: 1),
                    new OA\Property(property: "status", type: "string", example: "en_attente", description: "Statut de la réservation"),
                    new OA\Property(property: "montant", type: "number", example: 50000),
                    new OA\Property(property: "avance", type: "number", example: 20000),
                    new OA\Property(property: "reste", type: "number", example: 30000),
                    new OA\Property(property: "dateRetrait", type: "string", format: "date-time"),
                    new OA\Property(property: "client", type: "object"),
                    new OA\Property(property: "boutique", type: "object"),
                    new OA\Property(property: "entreprise", type: "object")
                ]
            )
        )
    )]
    #[OA\Response(
        response: 400,
        description: "Valeur de statut invalide",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "status", type: "string", example: "ERROR"),
                new OA\Property(property: "message", type: "string", example: "Statut invalide: 'invalid'. Valeurs autorisées: en_attente, confirmee, annulee")
            ]
        )
    )]
    #[OA\Response(response: 401, description: "Non authentifié")]
    #[OA\Response(response: 500, description: "Erreur lors de la récupération")]
    public function indexAll(Request $request, ReservationRepository $reservationRepository, TypeUserRepository $typeUserRepository): Response
    {
        try {
            // Récupérer le paramètre de filtrage par statut
            $statusFilter = $request->query->get('status');

            // Construire les critères de base selon les droits utilisateur
            $baseCriteria = [];
            if ($this->getUser()->getType() == $typeUserRepository->findOneBy(['code' => 'SADM'])) {
                $baseCriteria['entreprise'] = $this->getUser()->getEntreprise();
            } else {
                $baseCriteria['boutique'] = $this->getUser()->getBoutique();
            }

            // Si un filtre de statut est fourni, valider et l'ajouter aux critères
            if ($statusFilter !== null && $statusFilter !== '') {
                $validStatuses = [
                    ReservationStatus::EN_ATTENTE->value,
                    ReservationStatus::CONFIRMEE->value,
                    ReservationStatus::ANNULEE->value
                ];

                // Support de plusieurs statuts séparés par des virgules
                $requestedStatuses = array_map('trim', explode(',', $statusFilter));

                // Valider chaque statut
                foreach ($requestedStatuses as $status) {
                    if (!in_array($status, $validStatuses)) {
                        return $this->json([
                            'status' => 'ERROR',
                            'message' => "Statut invalide: '{$status}'. Valeurs autorisées: " . implode(', ', $validStatuses)
                        ], 400);
                    }
                }

                // Si un seul statut, utiliser une égalité simple
                if (count($requestedStatuses) === 1) {
                    $baseCriteria['status'] = $requestedStatuses[0];
                    $reservations = $this->paginationService->paginate($reservationRepository->findBy(
                        $baseCriteria,
                        ['id' => 'DESC']
                    ));
                } else {
                    // Si plusieurs statuts, utiliser une requête personnalisée
                    if ($this->getUser()->getType() == $typeUserRepository->findOneBy(['code' => 'SADM'])) {
                        $reservations = $this->paginationService->paginate(
                            $reservationRepository->findByEntrepriseAndStatuses(
                                $this->getUser()->getEntreprise(),
                                $requestedStatuses
                            )
                        );
                    } else {
                        $reservations = $this->paginationService->paginate(
                            $reservationRepository->findByBoutiqueAndStatuses(
                                $this->getUser()->getBoutique(),
                                $requestedStatuses
                            )
                        );
                    }
                }
            } else {
                // Pas de filtre de statut, utiliser les critères de base
                $reservations = $this->paginationService->paginate($reservationRepository->findBy(
                    $baseCriteria,
                    ['id' => 'DESC']
                ));
            }

            $response = $this->responseData($reservations, 'group_reservation', ['Content-Type' => 'application/json']);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            $this->setMessage("Erreur lors de la récupération des réservations");
            $response = $this->response([]);
        }

        return $response;
    }
    /**
     * Liste les réservations d'une boutique spécifique (GET - version simple)
     */
    #[Route('/entreprise/by/boutique/{id}', methods: ['GET'])]
    #[OA\Get(
        path: "/api/reservation/entreprise/by/boutique/{id}",
        summary: "Lister les réservations d'une boutique (version simple)",
        description: "Retourne la liste des réservations d'une boutique spécifique sans filtres avancés.",
        tags: ['reservation']
    )]
    #[OA\Response(
        response: 200,
        description: "Liste des réservations récupérée avec succès",
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(
                type: "object",
                properties: [
                    new OA\Property(property: "id", type: "integer", example: 1),
                    new OA\Property(property: "montant", type: "number", example: 50000),
                    new OA\Property(property: "avance", type: "number", example: 20000),
                    new OA\Property(property: "reste", type: "number", example: 30000),
                    new OA\Property(property: "dateRetrait", type: "string", format: "date-time"),
                    new OA\Property(property: "client", type: "object"),
                    new OA\Property(property: "boutique", type: "object"),
                    new OA\Property(property: "entreprise", type: "object")
                ]
            )
        )
    )]
    #[OA\Response(response: 401, description: "Non authentifié")]
    #[OA\Response(response: 500, description: "Erreur lors de la récupération")]
    public function indexAllByBoutique(ReservationRepository $reservationRepository, $id, TypeUserRepository $typeUserRepository): Response
    {
        try {

            $reservations = $this->paginationService->paginate($reservationRepository->findBy(
                ['boutique' => $id],
                ['id' => 'DESC']
            ));

            $response = $this->responseData($reservations, 'group_reservation', ['Content-Type' => 'application/json']);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            $this->setMessage("Erreur lors de la récupération des réservations");
            $response = $this->response([]);
        }

        return $response;
    }

    /**
     * Liste les réservations d'une boutique avec filtres avancés (POST)
     */
    #[Route('/entreprise/by/boutique/{id}/advanced', methods: ['POST'])]
    #[OA\Post(
        path: "/api/reservation/entreprise/by/boutique/{id}/advanced",
        summary: "Lister les réservations d'une boutique avec filtres avancés",
        description: "Retourne la liste des réservations d'une boutique spécifique avec des filtres avancés de date et de statut, similaires aux statistiques du dashboard.",
        tags: ['reservation']
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
        description: "Filtres pour les réservations",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "dateDebut", type: "string", format: "date", example: "2025-01-01", description: "Date de début (optionnel si filtre est utilisé)"),
                new OA\Property(property: "dateFin", type: "string", format: "date", example: "2025-01-31", description: "Date de fin (optionnel si filtre est utilisé)"),
                new OA\Property(property: "filtre", type: "string", enum: ["jour", "mois", "annee", "periode"], example: "mois", description: "Type de filtre de date"),
                new OA\Property(property: "valeur", type: "string", example: "2025-01", description: "Valeur du filtre (YYYY-MM-DD pour jour, YYYY-MM pour mois, YYYY pour année)"),
                new OA\Property(property: "status", type: "string", example: "en_attente,confirmee", description: "Filtrer par statut (valeurs séparées par virgules)")
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: "Liste des réservations récupérée avec succès",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "success", type: "boolean", example: true),
                new OA\Property(
                    property: "data",
                    type: "object",
                    properties: [
                        new OA\Property(property: "boutique_id", type: "integer", example: 1),
                        new OA\Property(property: "boutique_nom", type: "string", example: "Boutique Centre-ville"),
                        new OA\Property(
                            property: "periode",
                            type: "object",
                            properties: [
                                new OA\Property(property: "debut", type: "string", example: "2025-01-01"),
                                new OA\Property(property: "fin", type: "string", example: "2025-01-31"),
                                new OA\Property(property: "nbJours", type: "integer", example: 31)
                            ]
                        ),
                        new OA\Property(
                            property: "filtres_appliques",
                            type: "object",
                            properties: [
                                new OA\Property(property: "status", type: "array", items: new OA\Items(type: "string"))
                            ]
                        ),
                        new OA\Property(
                            property: "statistiques",
                            type: "object",
                            properties: [
                                new OA\Property(property: "total_reservations", type: "integer", example: 24),
                                new OA\Property(property: "montant_total", type: "number", example: 1200000),
                                new OA\Property(property: "montant_avances", type: "number", example: 480000),
                                new OA\Property(property: "montant_reste", type: "number", example: 720000)
                            ]
                        ),
                        new OA\Property(
                            property: "pagination",
                            type: "object",
                            properties: [
                                new OA\Property(property: "currentPage", type: "integer", example: 1, description: "Page actuelle"),
                                new OA\Property(property: "totalItems", type: "integer", example: 24, description: "Nombre total d'éléments"),
                                new OA\Property(property: "itemsPerPage", type: "integer", example: 10, description: "Nombre d'éléments par page"),
                                new OA\Property(property: "totalPages", type: "integer", example: 3, description: "Nombre total de pages")
                            ]
                        ),
                        new OA\Property(
                            property: "reservations",
                            type: "array",
                            items: new OA\Items(
                                type: "object",
                                properties: [
                                    new OA\Property(property: "id", type: "integer", example: 1),
                                    new OA\Property(property: "status", type: "string", example: "en_attente"),
                                    new OA\Property(property: "montant", type: "number", example: 50000),
                                    new OA\Property(property: "avance", type: "number", example: 20000),
                                    new OA\Property(property: "reste", type: "number", example: 30000),
                                    new OA\Property(property: "dateRetrait", type: "string", format: "date-time"),
                                    new OA\Property(property: "client", type: "object"),
                                    new OA\Property(property: "createdAt", type: "string", format: "date-time")
                                ]
                            )
                        )
                    ]
                )
            ]
        )
    )]
    #[OA\Response(
        response: 400,
        description: "Paramètres invalides",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "success", type: "boolean", example: false),
                new OA\Property(property: "message", type: "string", example: "Boutique non trouvée ou paramètres invalides")
            ]
        )
    )]
    #[OA\Response(response: 401, description: "Non authentifié")]
    #[OA\Response(response: 500, description: "Erreur serveur")]
    public function indexAllByBoutiqueAdvanced(
        int $id,
        Request $request,
        ReservationRepository $reservationRepository,
        BoutiqueRepository $boutiqueRepository
    ): Response {
        try {
            // Vérifier que la boutique existe
            $boutique = $boutiqueRepository->find($id);
            if (!$boutique) {
                return $this->json([
                    'success' => false,
                    'message' => 'Boutique non trouvée'
                ], 404);
            }

            // Décoder les données de la requête
            $data = json_decode($request->getContent(), true) ?? [];

            // Parser les filtres de date (utilise la même logique que les statistiques)
            [$dateDebut, $dateFin] = $this->parseAdvancedFilters($data);

            // Construire les critères de recherche
            $criteria = ['boutique' => $id];
            $additionalFilters = [];

            // Filtre par statut
            $statusFilters = [];
            if (!empty($data['status'])) {
                $validStatuses = [
                    ReservationStatus::EN_ATTENTE->value,
                    ReservationStatus::CONFIRMEE->value,
                    ReservationStatus::ANNULEE->value
                ];

                $requestedStatuses = array_map('trim', explode(',', $data['status']));

                foreach ($requestedStatuses as $status) {
                    if (!in_array($status, $validStatuses)) {
                        return $this->json([
                            'success' => false,
                            'message' => "Statut invalide: '{$status}'. Valeurs autorisées: " . implode(', ', $validStatuses)
                        ], 400);
                    }
                }

                $statusFilters = $requestedStatuses;
            }

            // Récupérer les réservations avec les filtres simplifiés
            $reservations = $reservationRepository->findByBoutiqueWithSimpleFilters(
                $id,
                $dateDebut,
                $dateFin,
                $statusFilters
            );

            /*     dd($reservations); */

            // Calculer les statistiques
            $stats = $this->calculateReservationStats($reservations);

            // Paginer les résultats
            $paginatedReservations = $this->paginationService->paginate($reservations);


            // Préparer la réponse
            $response = [
                'success' => true,
                'data' => [
                    'boutique_id' => $id,
                    'boutique_nom' => $boutique->getLibelle(),
                    'periode' => [
                        'debut' => $dateDebut->format('Y-m-d'),
                        'fin' => $dateFin->format('Y-m-d'),
                        'nbJours' => $dateDebut->diff($dateFin)->days + 1
                    ],
                    'filtres_appliques' => [
                        'status' => $statusFilters
                    ],
                    'statistiques' => $stats
                ]
            ];
            
            // Utiliser responseData avec pagination pour obtenir les métadonnées
            $paginatedResponse = json_decode(
                $this->responseData($paginatedReservations, 'group_reservation', ['Content-Type' => 'application/json'], true)->getContent(),
                true
            );
            
            // Ajouter les réservations et les métadonnées de pagination
            $response['data']['reservations'] = $paginatedResponse['data'];
            $response['data']['pagination'] = $paginatedResponse['pagination'];
            
            return $this->json($response);
            
        } catch (\Exception $exception) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des réservations: ' . $exception->getMessage()
            ], 500);
        }
    }

    /**
     * Récupère les détails d'une réservation spécifique
     */
    #[Route('/get/one/{id}', methods: ['GET'])]
    #[OA\Get(
        path: "/api/reservation/get/one/{id}",
        summary: "Détails d'une réservation",
        description: "Affiche les informations détaillées d'une réservation spécifique, incluant tous les articles réservés, les montants (total, acompte, reste), la date de retrait et les informations du client.",
        tags: ['reservation']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: "Identifiant unique de la réservation",
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: "Réservation trouvée avec succès",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "id", type: "integer", example: 1),
                new OA\Property(property: "montant", type: "number", format: "float", example: 50000, description: "Montant total"),
                new OA\Property(property: "avance", type: "number", format: "float", example: 20000, description: "Acompte versé"),
                new OA\Property(property: "reste", type: "number", format: "float", example: 30000, description: "Reste à payer lors du retrait"),
                new OA\Property(property: "dateRetrait", type: "string", format: "date-time", example: "2025-02-15T10:00:00+00:00"),
                new OA\Property(property: "client", type: "object", description: "Informations complètes du client"),
                new OA\Property(property: "boutique", type: "object", description: "Boutique de retrait"),
                new OA\Property(property: "entreprise", type: "object"),
                new OA\Property(
                    property: "ligneReservations",
                    type: "array",
                    description: "Détail de tous les articles réservés",
                    items: new OA\Items(type: "object")
                ),
                new OA\Property(property: "paiements", type: "array", description: "Liste des paiements effectués", items: new OA\Items(type: "object")),
                new OA\Property(property: "createdAt", type: "string", format: "date-time"),
                new OA\Property(property: "updatedAt", type: "string", format: "date-time")
            ]
        )
    )]
    #[OA\Response(response: 404, description: "Réservation non trouvée")]
    public function getOne(?Reservation $reservation): Response
    {
        try {
            if ($reservation) {
                $response = $this->response($reservation);
            } else {
                $this->setMessage('Cette ressource est inexistante');
                $this->setStatusCode(404);
                $response = $this->response(null);
            }
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            $this->setMessage($exception->getMessage());
            $response = $this->response([]);
        }

        return $response;
    }

    /**
     * Crée une nouvelle réservation avec acompte
     */
    #[Route('/create', methods: ['POST'])]
    #[OA\Post(
        path: "/api/reservation/create",
        summary: "Créer une réservation",
        description: "Permet de créer une nouvelle réservation de vêtements avec un acompte. Enregistre automatiquement le paiement de l'acompte, met à jour la caisse de la boutique, et programme la date de retrait. Nécessite un abonnement actif.",
        tags: ['reservation']
    )]
    #[OA\RequestBody(
        required: true,
        description: "Données de la réservation à créer",
        content: new OA\JsonContent(
            type: "object",
            required: ["montant", "avance", "reste", "dateRetrait", "client", "boutique", "ligne"],
            properties: [
                new OA\Property(
                    property: "montant",
                    type: "number",
                    format: "float",
                    example: 50000,
                    description: "Montant total de la réservation en FCFA (obligatoire)"
                ),
                new OA\Property(
                    property: "avance",
                    type: "number",
                    format: "float",
                    example: 20000,
                    description: "Montant de l'acompte versé en FCFA (obligatoire, généralement 30-50% du total)"
                ),
                new OA\Property(
                    property: "reste",
                    type: "number",
                    format: "float",
                    example: 30000,
                    description: "Reste à payer lors du retrait en FCFA (obligatoire, = montant - avance)"
                ),
                new OA\Property(
                    property: "dateRetrait",
                    type: "string",
                    format: "date-time",
                    example: "2025-02-15T10:00:00",
                    description: "Date prévue de retrait des articles (obligatoire)"
                ),
                new OA\Property(
                    property: "client",
                    type: "integer",
                    example: 5,
                    description: "ID du client effectuant la réservation (obligatoire)"
                ),
                new OA\Property(
                    property: "boutique",
                    type: "integer",
                    example: 1,
                    description: "ID de la boutique où retirer les articles (obligatoire)"
                ),
                new OA\Property(
                    property: "ligne",
                    type: "array",
                    description: "Liste des articles à réserver (obligatoire, minimum 1 article)",
                    items: new OA\Items(
                        type: "object",
                        required: ["modele", "quantite"],
                        properties: [
                            new OA\Property(
                                property: "modele",
                                type: "integer",
                                example: 3,
                                description: "ID du modèle à réserver (obligatoire)"
                            ),
                            new OA\Property(
                                property: "avanceModele",
                                type: "number",
                                example: 3,
                                description: "ID du modèle de l'acompte (obligatoire)"
                            ),
                            new OA\Property(
                                property: "quantite",
                                type: "integer",
                                example: 2,
                                description: "Quantité à réserver (obligatoire)"
                            )
                        ]
                    ),
                    minItems: 1,
                    example: [
                        ["modele" => 3, "quantite" => 2],
                        ["modele" => 5, "quantite" => 1]
                    ]
                )
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: "Réservation créée avec succès",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "id", type: "integer", example: 25),
                new OA\Property(property: "montant", type: "number", example: 50000),
                new OA\Property(property: "avance", type: "number", example: 20000),
                new OA\Property(property: "reste", type: "number", example: 30000),
                new OA\Property(property: "dateRetrait", type: "string", format: "date-time"),
                new OA\Property(property: "client", type: "object"),
                new OA\Property(property: "boutique", type: "object"),
                new OA\Property(property: "ligneReservations", type: "array", description: "Articles réservés", items: new OA\Items(type: "object")),
                new OA\Property(property: "paiements", type: "array", description: "Paiement de l'acompte enregistré", items: new OA\Items(type: "object")),
                new OA\Property(property: "createdAt", type: "string", format: "date-time")
            ]
        )
    )]
    #[OA\Response(response: 400, description: "Données invalides")]
    #[OA\Response(response: 401, description: "Non authentifié")]
    #[OA\Response(response: 403, description: "Abonnement requis pour cette fonctionnalité")]
    #[OA\Response(response: 404, description: "Client, boutique ou modèle non trouvé")]
    public function create(
        Request $request,
        ModeleBoutiqueRepository $modeleBoutiqueRepository,
        CaisseBoutiqueRepository $caisseBoutiqueRepository,
        PaiementReservationRepository $paiementReservationRepository,
        ModeleRepository $modeleRepository,
        ClientRepository $clientRepository,
        BoutiqueRepository $boutiqueRepository,
        Utils $utils,
        ReservationRepository $reservationRepository,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        $data = json_decode($request->getContent(), true);
        $lignes = $data['ligne'] ?? [];

        // ✅ Validation préalable des données
        if (empty($lignes) || !is_array($lignes)) {
            return $this->json([
                'status' => 'ERROR',
                'message' => 'Aucune ligne de réservation à traiter'
            ], 400);
        }

        // Validation des champs requis
        $requiredFields = ['avance', 'dateRetrait', 'client', 'boutique', 'montant', 'reste'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                return $this->json([
                    'status' => 'ERROR',
                    'message' => "Le champ '{$field}' est requis"
                ], 400);
            }
        }

        $avance = (int)$data['avance'];
        $montant = (int)$data['montant'];
        $reste = (int)$data['reste'];

        // Validation des montants
        if ($montant <= 0) {
            return $this->json([
                'status' => 'ERROR',
                'message' => 'Le montant doit être supérieur à 0'
            ], 400);
        }

        if ($avance < 0) {
            return $this->json([
                'status' => 'ERROR',
                'message' => 'L\'avance ne peut pas être négative'
            ], 400);
        }

        if ($reste < 0) {
            return $this->json([
                'status' => 'ERROR',
                'message' => 'Le reste ne peut pas être négatif'
            ], 400);
        }

        if ($avance + $reste !== $montant) {
            return $this->json([
                'status' => 'ERROR',
                'message' => 'Incohérence : avance + reste doit être égal au montant total'
            ], 400);
        }

        // Validation de la date de retrait
        try {
            $dateRetrait = new \DateTime($data['dateRetrait']);
            $now = new \DateTime();
            $now->setTime(0, 0, 0); // Réinitialiser à minuit pour comparer uniquement les dates
            $dateRetrait->setTime(0, 0, 0);

            if ($dateRetrait < $now) {
                return $this->json([
                    'status' => 'ERROR',
                    'message' => 'La date de retrait ne peut pas être dans le passé'
                ], 400);
            }
        } catch (\Exception $e) {
            return $this->json([
                'status' => 'ERROR',
                'message' => 'Format de date invalide pour dateRetrait'
            ], 400);
        }

        // Récupérer le client
        $client = $clientRepository->find($data['client']);
        if (!$client) {
            return $this->json([
                'status' => 'ERROR',
                'message' => 'Client non trouvé'
            ], 404);
        }

        // Récupérer la boutique
        $boutique = $boutiqueRepository->find($data['boutique']);
        if (!$boutique) {
            return $this->json([
                'status' => 'ERROR',
                'message' => 'Boutique non trouvée'
            ], 404);
        }

        // Récupérer tous les ModeleBoutique en une seule requête
        $modeleBoutiqueIds = array_column($lignes, 'modele');
        $modeleBoutiques = $modeleBoutiqueRepository->findBy(['id' => $modeleBoutiqueIds]);

        // Indexer par ID pour un accès rapide
        $modeleBoutiquesMap = [];
        foreach ($modeleBoutiques as $mb) {
            $modeleBoutiquesMap[$mb->getId()] = $mb;
        }

        // ✅ Validation des lignes et détection des stocks insuffisants SANS bloquer
        $totalQuantiteReservee = 0;
        $stockDeficits = []; // Collecter les déficits de stock
        $hasStockIssues = false;

        foreach ($lignes as $index => $ligneData) {
            $modeleId = $ligneData['modele'] ?? null;
            $quantite = $ligneData['quantite'] ?? null;

            if ($modeleId === null) {
                return $this->json([
                    'status' => 'ERROR',
                    'message' => "modele manquant à la ligne " . ($index + 1)
                ], 400);
            }



            // Vérifier que le ModeleBoutique existe
            if (!isset($modeleBoutiquesMap[$modeleId])) {
                return $this->json([
                    'status' => 'ERROR',
                    'message' => "Modèle de boutique non trouvé avec ID: {$modeleId}"
                ], 404);
            }

            $modeleBoutique = $modeleBoutiquesMap[$modeleId];


            // ✅ NOUVELLE LOGIQUE : Détecter les ruptures de stock SANS bloquer la création
            $stockDisponible = $modeleBoutique->getQuantite();
            $modele = $modeleBoutique->getModele();

            // Vérifier le stock local de la boutique
            if ($stockDisponible < $quantite) {
                $deficit = new StockDeficit(
                    $modele->getNom(),
                    $quantite,
                    $stockDisponible,
                    (string)$boutique->getId()
                );
                $stockDeficits[] = $deficit;
                $hasStockIssues = true;
            }

            // Vérifier aussi la quantité globale (information supplémentaire)
            $stockGlobal = $modele->getQuantiteGlobale();
            if ($stockGlobal < $quantite && $stockDisponible >= $quantite) {
                // Stock local suffisant mais stock global insuffisant
                // Créer un déficit spécial pour le stock global
                $globalDeficit = new StockDeficit(
                    $modele->getNom() . ' (Stock Global)',
                    $quantite,
                    $stockGlobal,
                    'global'
                );
                $stockDeficits[] = $globalDeficit;
                $hasStockIssues = true;
            }

            $totalQuantiteReservee += $quantite;
        }

        // Récupérer l'admin pour les notifications
        $admin = $userRepository->getUserByCodeType($this->getUser()->getEntreprise());

        // Créer la réservation
        $reservation = new Reservation();
        $reservation->setAvance($avance);
        $reservation->setDateRetrait($dateRetrait);
        $reservation->setClient($client);
        $reservation->setBoutique($boutique);
        $reservation->setEntreprise($this->getUser()->getEntreprise());
        $reservation->setMontant($montant);
        $reservation->setReste($reste);

        // ✅ NOUVELLE LOGIQUE : Assigner le statut selon la disponibilité du stock
        if ($hasStockIssues) {
            $reservation->setStatus(ReservationStatus::EN_ATTENTE_STOCK->value);
        } else {
            $reservation->setStatus(ReservationStatus::EN_ATTENTE->value);
        }

        $reservation->setCreatedAtValue(new \DateTime());
        $reservation->setUpdatedAt(new \DateTime());
        $reservation->setCreatedBy($this->getUser());
        $reservation->setUpdatedBy($this->getUser());

        $errorResponse = $this->errorResponse($reservation);
        if ($errorResponse !== null) {
            return $errorResponse;
        }

        // 🔒 Transaction pour garantir la cohérence atomique
        $entityManager->beginTransaction();

        try {
            // ✅ Persister la réservation d'abord (parent)
            $entityManager->persist($reservation);

            // Ajouter les lignes de réservation SANS déduire le stock
            foreach ($lignes as $ligneData) {
                $modeleBoutique = $modeleBoutiquesMap[$ligneData['modele']];
                $quantite = (int)$ligneData['quantite'];

                // Créer la ligne de réservation
                $ligne = new LigneReservation();
                $ligne->setQuantite($quantite);
                $ligne->setModele($modeleBoutique);
                $ligne->setAvanceModele($ligneData['avanceModele']);
                $ligne->setCreatedAtValue(new \DateTime());
                $ligne->setUpdatedAt(new \DateTime());
                $ligne->setCreatedBy($this->getUser());
                $ligne->setUpdatedBy($this->getUser());

                $reservation->addLigneReservation($ligne);
                $entityManager->persist($ligne);

                // ✅ MODIFICATION CRITIQUE : NE PLUS déduire le stock lors de la création
                // Le stock sera déduit uniquement lors de la confirmation de la réservation
                // Cette approche permet d'éviter les blocages inutiles en cas d'annulation

                // ❌ ANCIEN CODE (supprimé) :
                // $modeleBoutique->setQuantite($modeleBoutique->getQuantite() - $quantite);
                // if ($modele && $modele->getQuantiteGlobale() >= $quantite) {
                //     $modele->setQuantiteGlobale($modele->getQuantiteGlobale() - $quantite);
                // }
            }

            // Créer un paiement seulement si l'avance est supérieure à zéro
            if ($avance > 0) {
                $paiementReservation = new PaiementReservation();
                $paiementReservation->setReservation($reservation);
                $paiementReservation->setType(Paiement::TYPE["paiementReservation"]);
                $paiementReservation->setMontant($avance);
                $paiementReservation->setReference($utils->generateReference('PMT'));
                $paiementReservation->setCreatedAtValue(new \DateTime());
                $paiementReservation->setUpdatedAt(new \DateTime());
                $paiementReservation->setCreatedBy($this->getUser());
                $paiementReservation->setUpdatedBy($this->getUser());

                $entityManager->persist($paiementReservation);

                // Mise à jour de la caisse boutique
                $caisseBoutique = $caisseBoutiqueRepository->findOneBy(['boutique' => $boutique->getId()]);
                if ($caisseBoutique) {
                    $caisseBoutique->setMontant($caisseBoutique->getMontant() + $avance);
                    $caisseBoutique->setUpdatedBy($this->getUser());
                    $caisseBoutique->setUpdatedAt(new \DateTime());
                } else {
                    $entityManager->rollback();
                    return $this->json([
                        'status' => 'ERROR',
                        'message' => 'Caisse de boutique introuvable'
                    ], 404);
                }
            }

            // ✅ Un seul flush pour tout
            $entityManager->flush();
            $entityManager->commit();

            // Envoi des notifications (après la transaction réussie)
            if ($admin) {
                try {
                    // ✅ NOUVELLE FONCTIONNALITÉ : Envoyer les alertes de stock si nécessaire
                    if ($hasStockIssues && !empty($stockDeficits)) {
                        // Préparer les informations de la réservation pour les notifications
                        $reservationInfo = [
                            'reservation_id' => $reservation->getId(),
                            'client_name' => $client->getNom() . ' ' . $client->getPrenom(),
                            'client_phone' => $client->getTelephone(),
                            'total_amount' => $montant,
                            'advance_amount' => $avance,
                            'remaining_amount' => $reste,
                            'withdrawal_date' => $dateRetrait->format('d/m/Y'),
                            'created_by' => $this->getUser()->getNom() && $this->getUser()->getPrenoms()
                                ? $this->getUser()->getNom() . " " . $this->getUser()->getPrenoms()
                                : $this->getUser()->getLogin(),
                            'created_at' => (new \DateTime())->format('d/m/Y H:i')
                        ];

                        // Envoyer l'email d'alerte de stock avec gestion d'erreur robuste
                        try {
                            $this->sendMailService->sendStockAlertEmail(
                                $this->sendMail,
                                $admin,
                                $this->getUser()->getEntreprise(),
                                $boutique->getLibelle(),
                                $stockDeficits,
                                $reservationInfo
                            );
                        } catch (\Exception $emailError) {
                            // Logger l'erreur mais ne pas bloquer le processus
                            error_log("❌ Erreur envoi email alerte stock: " . $emailError->getMessage());
                        }

                        // Envoyer la notification push avec système de fallback
                        try {
                            if ($this->notificationService) {
                                $this->notificationService->sendStockAlertNotification(
                                    $admin,
                                    $this->getUser()->getEntreprise(),
                                    $boutique->getLibelle(),
                                    $stockDeficits,
                                    $reservationInfo
                                );
                            } else {
                                error_log("⚠️ NotificationService non disponible - notification push ignorée");
                            }
                        } catch (\Exception $notifError) {
                            // Logger l'erreur mais ne pas bloquer le processus
                            error_log("❌ Erreur envoi notification alerte stock: " . $notifError->getMessage());
                        }
                    }

                    // Notifications standard de réservation (existantes)
                    $this->sendMailService->sendNotification([
                        'entreprise' => $this->getUser()->getEntreprise(),
                        "user" => $admin,
                        "libelle" => sprintf(
                            "Bonjour %s,\n\n" .
                                "Nous vous informons qu'une nouvelle réservation vient d'être enregistrée dans la boutique **%s**.\n\n" .
                                "- Client : %s\n" .
                                "- Montant total : %s FCFA\n" .
                                "- Avance versée : %s FCFA\n" .
                                "- Reste à payer : %s FCFA\n" .
                                "- Quantité totale : %d article(s)\n" .
                                "- Date de retrait prévue : %s\n" .
                                "- Effectué par : %s\n" .
                                "- Date de réservation : %s\n\n" .
                                ($hasStockIssues ? "⚠️ ATTENTION : Cette réservation contient des articles en rupture de stock. Consultez l'email d'alerte pour plus de détails.\n\n" : "") .
                                "Cordialement,\nVotre application de gestion.",
                            $admin->getLogin(),
                            $boutique->getLibelle(),
                            $client->getNom() . ' ' . $client->getPrenom(),
                            number_format($montant, 0, ',', ' '),
                            number_format($avance, 0, ',', ' '),
                            number_format($reste, 0, ',', ' '),
                            $totalQuantiteReservee,
                            $dateRetrait->format('d/m/Y'),
                            $this->getUser()->getNom() && $this->getUser()->getPrenoms()
                                ? $this->getUser()->getNom() . " " . $this->getUser()->getPrenoms()
                                : $this->getUser()->getLogin(),
                            (new \DateTime())->format('d/m/Y H:i')
                        ),
                        "titre" => "Réservation - " . $boutique->getLibelle() . ($hasStockIssues ? " (⚠️ Stock insuffisant)" : ""),
                    ]);

                    $this->sendMailService->send(
                        $this->sendMail,
                        $this->superAdmin,
                        "Réservation - " . $this->getUser()->getEntreprise()->getLibelle(),
                        "reservation_email",
                        [
                            "boutique_libelle" => $this->getUser()->getEntreprise()->getLibelle(),
                            "client" => $client->getNom() . ' ' . $client->getPrenom(),
                            "montant_total" => number_format($montant, 0, ',', ' ') . " FCFA",
                            "avance" => number_format($avance, 0, ',', ' ') . " FCFA",
                            "reste" => number_format($reste, 0, ',', ' ') . " FCFA",
                            "quantite" => $totalQuantiteReservee,
                            "date_retrait" => $dateRetrait->format('d/m/Y'),
                            "date" => (new \DateTime())->format('d/m/Y H:i'),
                        ]
                    );
                } catch (\Exception $e) {
                    // Ne pas bloquer la réservation si l'envoi d'email échoue
                    // Vous pouvez logger l'erreur ici si vous avez un logger
                }
            }

            return $this->responseData($reservation, 'group1', ['Content-Type' => 'application/json']);
        } catch (\Exception $e) {
            $entityManager->rollback();
            return $this->json([
                'status' => 'ERROR',
                'message' => 'Erreur lors de la création de la réservation: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Confirme une réservation et déduit le stock
     */
    #[Route('/confirm/{id}', methods: ['POST'])]
    #[OA\Post(
        path: "/api/reservation/confirm/{id}",
        summary: "Confirmer une réservation",
        description: "Confirme une réservation en attente et déduit automatiquement le stock des articles réservés. Cette action est irréversible et change le statut de la réservation à 'confirmée'. Nécessite un abonnement actif.",
        tags: ['reservation']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: "Identifiant unique de la réservation à confirmer",
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\RequestBody(
        required: false,
        description: "Notes optionnelles sur la confirmation",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(
                    property: "notes",
                    type: "string",
                    example: "Confirmation après vérification des articles",
                    description: "Notes optionnelles sur la confirmation"
                )
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: "Réservation confirmée avec succès",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "success", type: "boolean", example: true),
                new OA\Property(property: "message", type: "string", example: "Réservation confirmée avec succès"),
                new OA\Property(
                    property: "reservation",
                    type: "object",
                    properties: [
                        new OA\Property(property: "id", type: "integer", example: 1),
                        new OA\Property(property: "status", type: "string", example: "confirmee"),
                        new OA\Property(property: "confirmedAt", type: "string", format: "date-time"),
                        new OA\Property(property: "confirmedBy", type: "object", description: "Utilisateur ayant confirmé")
                    ]
                ),
                new OA\Property(
                    property: "stock_deductions",
                    type: "array",
                    description: "Détail des déductions de stock effectuées",
                    items: new OA\Items(type: "object")
                )
            ]
        )
    )]
    #[OA\Response(
        response: 400,
        description: "Réservation ne peut pas être confirmée ou stock insuffisant",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "success", type: "boolean", example: false),
                new OA\Property(property: "message", type: "string", example: "Stock insuffisant pour certains articles"),
                new OA\Property(property: "insufficient_items", type: "array", items: new OA\Items(type: "object"))
            ]
        )
    )]
    #[OA\Response(response: 401, description: "Non authentifié")]
    #[OA\Response(response: 403, description: "Abonnement requis pour cette fonctionnalité")]
    #[OA\Response(response: 404, description: "Réservation non trouvée")]
    #[OA\Response(response: 500, description: "Erreur serveur lors de la confirmation")]
    public function confirm(
        int $id,
        Request $request,
        ReservationWorkflowService $workflowService
    ): Response {
        /* if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        } */

        try {
            $data = json_decode($request->getContent(), true);
            $notes = $data['notes'] ?? null;

            $result = $workflowService->confirmReservation($id, $this->getUser(), $notes);

            if ($result['success']) {
                // ✅ Utiliser les méthodes d'ApiInterface pour la réponse de succès
                $this->setStatusCode(200);
                $this->setMessage($result['message']);

                return $this->responseData([
                    'reservation' => $result['reservation'],
                    'stock_deductions' => $result['stock_deductions']
                ], 'group1', ['Content-Type' => 'application/json']);
            } else {
                // ✅ Utiliser les méthodes d'ApiInterface pour la réponse d'erreur
                $this->setStatusCode(400);
                $this->setMessage($result['message']);

                return $this->response([
                    'insufficient_items' => $result['insufficient_items'] ?? []
                ]);
            }
        } catch (\InvalidArgumentException $e) {
            // ✅ Utiliser les méthodes d'ApiInterface pour les erreurs de validation
            $this->setStatusCode(400);
            $this->setMessage($e->getMessage());
            return $this->response([]);
        } catch (\RuntimeException $e) {
            // ✅ Utiliser les méthodes d'ApiInterface pour les erreurs de logique métier
            $this->setStatusCode(400);
            $this->setMessage($e->getMessage());
            return $this->response([]);
        } catch (\Exception $e) {
            // ✅ Utiliser les méthodes d'ApiInterface pour les erreurs serveur
            $this->setStatusCode(500);
            $this->setMessage('Erreur lors de la confirmation de la réservation: ' . $e->getMessage());
            return $this->response([]);
        }
    }

    /**
     * Annule une réservation
     */
    #[Route('/cancel/{id}', methods: ['POST'])]
    #[OA\Post(
        path: "/api/reservation/cancel/{id}",
        summary: "Annuler une réservation",
        description: "Annule une réservation en attente. Cette action change le statut de la réservation à 'annulée' sans affecter le stock (puisque le stock n'a pas encore été déduit). Les paiements d'acompte restent enregistrés pour la comptabilité. Nécessite un abonnement actif.",
        tags: ['reservation']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: "Identifiant unique de la réservation à annuler",
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\RequestBody(
        required: false,
        description: "Raison de l'annulation",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(
                    property: "reason",
                    type: "string",
                    example: "Client ne souhaite plus récupérer les articles",
                    description: "Raison de l'annulation (optionnel mais recommandé)"
                )
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: "Réservation annulée avec succès",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "success", type: "boolean", example: true),
                new OA\Property(property: "message", type: "string", example: "Réservation annulée avec succès"),
                new OA\Property(
                    property: "reservation",
                    type: "object",
                    properties: [
                        new OA\Property(property: "id", type: "integer", example: 1),
                        new OA\Property(property: "status", type: "string", example: "annulee"),
                        new OA\Property(property: "cancelledAt", type: "string", format: "date-time"),
                        new OA\Property(property: "cancelledBy", type: "object", description: "Utilisateur ayant annulé"),
                        new OA\Property(property: "cancellationReason", type: "string", example: "Client ne souhaite plus récupérer les articles")
                    ]
                ),
                new OA\Property(property: "reason", type: "string", example: "Client ne souhaite plus récupérer les articles")
            ]
        )
    )]
    #[OA\Response(
        response: 400,
        description: "Réservation ne peut pas être annulée",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "success", type: "boolean", example: false),
                new OA\Property(property: "message", type: "string", example: "La réservation ne peut pas être annulée. Statut actuel: confirmee")
            ]
        )
    )]
    #[OA\Response(response: 401, description: "Non authentifié")]
    #[OA\Response(response: 403, description: "Abonnement requis pour cette fonctionnalité")]
    #[OA\Response(response: 404, description: "Réservation non trouvée")]
    #[OA\Response(response: 500, description: "Erreur serveur lors de l'annulation")]
    public function cancel(
        int $id,
        Request $request,
        ReservationWorkflowService $workflowService
    ): Response {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        try {
            $data = json_decode($request->getContent(), true);
            $reason = $data['reason'] ?? null;

            $result = $workflowService->cancelReservation($id, $this->getUser(), $reason);

            if ($result['success']) {
                // ✅ Utiliser les méthodes d'ApiInterface pour la réponse de succès
                $this->setStatusCode(200);
                $this->setMessage($result['message']);

                return $this->responseData([
                    'reservation' => $result['reservation'],
                    'reason' => $result['reason']
                ], 'group1', ['Content-Type' => 'application/json']);
            } else {
                // ✅ Utiliser les méthodes d'ApiInterface pour la réponse d'erreur
                $this->setStatusCode(400);
                $this->setMessage($result['message']);
                return $this->response([]);
            }
        } catch (\InvalidArgumentException $e) {
            // ✅ Utiliser les méthodes d'ApiInterface pour les erreurs de validation
            $this->setStatusCode(400);
            $this->setMessage($e->getMessage());
            return $this->response([]);
        } catch (\Exception $e) {
            // ✅ Utiliser les méthodes d'ApiInterface pour les erreurs serveur
            $this->setStatusCode(500);
            $this->setMessage('Erreur lors de l\'annulation de la réservation: ' . $e->getMessage());
            return $this->response([]);
        }
    }

    /**
     * Met à jour une réservation existante
     */
    #[Route('/update/{id}', methods: ['PUT', 'POST'])]
    #[OA\Put(
        path: "/api/reservation/update/{id}",
        summary: "Mettre à jour une réservation",
        description: "Permet de modifier les informations d'une réservation existante, incluant les montants, la date de retrait et les articles réservés. Met à jour la caisse en conséquence. Nécessite un abonnement actif.",
        tags: ['reservation']
    )]
    #[OA\Parameter(
        name: "id",
        in: "path",
        required: true,
        description: "Identifiant unique de la réservation à mettre à jour",
        schema: new OA\Schema(type: "integer", example: 1)
    )]
    #[OA\RequestBody(
        required: true,
        description: "Nouvelles données de la réservation",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "montant", type: "number", example: 55000),
                new OA\Property(property: "avance", type: "number", example: 25000),
                new OA\Property(property: "reste", type: "number", example: 30000),
                new OA\Property(property: "dateRetrait", type: "string", format: "date-time", example: "2025-02-20T14:00:00"),
                new OA\Property(property: "client", type: "integer", example: 5),
                new OA\Property(property: "boutique", type: "integer", example: 1),
                new OA\Property(
                    property: "ligne",
                    type: "array",
                    description: "Nouvelle liste complète des articles (remplace l'ancienne)",
                    items: new OA\Items(
                        type: "object",
                        properties: [
                            new OA\Property(property: "modele", type: "integer", example: 3),
                            new OA\Property(property: "quantite", type: "integer", example: 3)
                        ]
                    )
                )
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: "Réservation mise à jour avec succès",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "id", type: "integer", example: 1),
                new OA\Property(property: "montant", type: "number", example: 55000),
                new OA\Property(property: "updatedAt", type: "string", format: "date-time")
            ]
        )
    )]
    #[OA\Response(response: 400, description: "Données invalides")]
    #[OA\Response(response: 401, description: "Non authentifié")]
    #[OA\Response(response: 403, description: "Abonnement requis pour cette fonctionnalité")]
    #[OA\Response(response: 404, description: "Réservation non trouvée")]
    public function update(
        Request $request,
        Reservation $reservation,
        ReservationRepository $reservationRepository,
        ClientRepository $clientRepository,
        BoutiqueRepository $boutiqueRepository,
        CaisseBoutiqueRepository $caisseBoutiqueRepository,
        ModeleRepository $modeleRepository,
        ModeleBoutiqueRepository $modeleBoutiqueRepository,
        PaiementReservationRepository $paiementReservationRepository,
        Utils $utils
    ): Response {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        try {
            $data = json_decode($request->getContent(), true);

            if ($reservation) {
                if (isset($data['avance'])) {
                    $reservation->setAvance($data['avance']);
                }
                if (isset($data['reste'])) {
                    $reservation->setReste($data['reste']);
                }
                if (isset($data['dateRetrait'])) {
                    $reservation->setDateRetrait(new \DateTime($data['dateRetrait']));
                }
                if (isset($data['client'])) {
                    $client = $clientRepository->find($data['client']);
                    if ($client) {
                        $reservation->setClient($client);
                    }
                }
                if (isset($data['boutique'])) {
                    $boutique = $boutiqueRepository->find($data['boutique']);
                    if ($boutique) {
                        $reservation->setBoutique($boutique);
                    }
                }
                if (isset($data['montant'])) {
                    $reservation->setMontant($data['montant']);
                }

                $reservation->setUpdatedBy($this->getUser());
                $reservation->setUpdatedAt(new \DateTime());

                // Mise à jour des lignes de réservation si fournies
                if (isset($data['ligne']) && is_array($data['ligne'])) {
                    // Supprimer les anciennes lignes
                    foreach ($reservation->getLigneReservations() as $ligne) {
                        $reservation->removeLigneReservation($ligne);
                    }

                    // Ajouter les nouvelles lignes
                    foreach ($data['ligne'] as $value) {
                        $modeleBoutique = $modeleBoutiqueRepository->find($value['modele']);
                        if ($modeleBoutique) {
                            $ligne = new LigneReservation();
                            $ligne->setQuantite($value['quantite']);
                            $ligne->setModele($modeleBoutique);
                            $ligne->setIsActive(true);
                            $ligne->setCreatedAtValue(new \DateTime());
                            $ligne->setUpdatedAt(new \DateTime());
                            $ligne->setCreatedBy($this->getUser());
                            $ligne->setUpdatedBy($this->getUser());
                            $reservation->addLigneReservation($ligne);
                        }
                    }
                }

                $errorResponse = $this->errorResponse($reservation);
                if ($errorResponse !== null) {
                    return $errorResponse;
                }

                $reservationRepository->add($reservation, true);

                $response = $this->responseData($reservation, 'group1', ['Content-Type' => 'application/json']);
            } else {
                $this->setMessage("Cette ressource est inexistante");
                $this->setStatusCode(404);
                $response = $this->response([]);
            }
        } catch (\Exception $e) {
            $this->setStatusCode(500);
            $this->setMessage("Erreur lors de la mise à jour de la réservation");
            $response = $this->response([]);
        }

        return $response;
    }

    /**
     * Effectuer un paiement sur une réservation
     */
    #[Route('/paiement/{id}', methods: ['POST'])]
    #[OA\Post(
        path: "/api/reservation/paiement/{id}",
        summary: "Effectuer un paiement sur une réservation",
        description: "Permet d'enregistrer un paiement (acompte ou solde) sur une réservation existante. Met automatiquement à jour la caisse de la boutique et recalcule le reste à payer. Nécessite un abonnement actif.",
        tags: ['reservation']
    )]
    #[OA\Parameter(
        name: "id",
        in: "path",
        required: true,
        description: "Identifiant unique de la réservation",
        schema: new OA\Schema(type: "integer", example: 1)
    )]
    #[OA\RequestBody(
        required: true,
        description: "Données du paiement à effectuer",
        content: new OA\JsonContent(
            type: "object",
            required: ["montant"],
            properties: [
                new OA\Property(
                    property: "montant",
                    type: "number",
                    example: 15000,
                    description: "Montant du paiement (obligatoire, doit être > 0)"
                ),
                new OA\Property(
                    property: "notes",
                    type: "string",
                    example: "Paiement par carte bancaire",
                    description: "Notes sur le paiement (optionnel)"
                )
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: "Paiement enregistré avec succès",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "id", type: "integer", example: 15, description: "ID du paiement créé"),
                new OA\Property(property: "reference", type: "string", example: "PMT250115143025001", description: "Référence unique du paiement"),
                new OA\Property(property: "montant", type: "number", example: 15000),
                new OA\Property(property: "type", type: "string", example: "paiementReservation"),
                new OA\Property(
                    property: "reservation",
                    type: "object",
                    description: "Réservation mise à jour",
                    properties: [
                        new OA\Property(property: "id", type: "integer", example: 1),
                        new OA\Property(property: "montant", type: "number", example: 50000),
                        new OA\Property(property: "avance", type: "number", example: 35000, description: "Total des acomptes versés"),
                        new OA\Property(property: "reste", type: "number", example: 15000, description: "Reste à payer")
                    ]
                ),
                new OA\Property(property: "createdAt", type: "string", format: "date-time")
            ]
        )
    )]
    #[OA\Response(
        response: 400,
        description: "Données invalides ou montant supérieur au reste à payer",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "status", type: "string", example: "ERROR"),
                new OA\Property(property: "message", type: "string", example: "Le montant du paiement (20000) dépasse le reste à payer (15000)")
            ]
        )
    )]
    #[OA\Response(response: 401, description: "Non authentifié")]
    #[OA\Response(response: 403, description: "Abonnement requis pour cette fonctionnalité")]
    #[OA\Response(response: 404, description: "Réservation non trouvée")]
    public function paiement(
        int $id,
        Request $request,
        ReservationRepository $reservationRepository,
        PaiementReservationRepository $paiementReservationRepository,
        CaisseBoutiqueRepository $caisseBoutiqueRepository,
        Utils $utils,
        UserRepository $userRepository
    ): Response {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        $admin = $userRepository->getUserByCodeType($this->getUser()->getEntreprise());


        $reservation = $reservationRepository->find($id);
        if (!$reservation) {
            $this->setMessage("Réservation non trouvée");
            return $this->response('[]', 404);
        }

        $data = json_decode($request->getContent(), true);
        $montantPaiement = $data['montant'] ?? 0;

        if ($montantPaiement <= 0) {
            $this->setMessage("Le montant du paiement doit être supérieur à zéro");
            return $this->response('[]', 400);
        }

        if ($montantPaiement > $reservation->getReste()) {
            $this->setMessage("Le montant du paiement ({$montantPaiement}) dépasse le reste à payer ({$reservation->getReste()})");
            return $this->response('[]', 400);
        }

        // Créer le paiement
        $paiementReservation = new PaiementReservation();
        $paiementReservation->setReservation($reservation);
        $paiementReservation->setType(Paiement::TYPE["paiementReservation"]);
        $paiementReservation->setMontant($montantPaiement);
        $paiementReservation->setReference($utils->generateReference('PMT'));
        $paiementReservation->setCreatedAtValue(new \DateTime());
        $paiementReservation->setUpdatedAt(new \DateTime());
        $paiementReservation->setCreatedBy($this->getUser());
        $paiementReservation->setUpdatedBy($this->getUser());

        $paiementReservationRepository->add($paiementReservation, true);

        // Mettre à jour la réservation
        $nouvelleAvance = $reservation->getAvance() + $montantPaiement;
        $nouveauReste = $reservation->getMontant() - $nouvelleAvance;

        $reservation->setAvance($nouvelleAvance);
        $reservation->setReste($nouveauReste);
        $reservation->setUpdatedAt(new \DateTime());
        $reservation->setUpdatedBy($this->getUser());
        $reservationRepository->add($reservation, true);

        // Mettre à jour la caisse boutique
        $caisseBoutique = $caisseBoutiqueRepository->findOneBy(['boutique' => $reservation->getBoutique()->getId()]);
        if ($caisseBoutique) {
            $caisseBoutique->setMontant((int)$caisseBoutique->getMontant() + (int)$montantPaiement);
            $caisseBoutique->setUpdatedBy($this->getUser());
            $caisseBoutique->setUpdatedAt(new \DateTime());
            $caisseBoutiqueRepository->add($caisseBoutique, true);
        }


        $this->sendMailService->sendNotification([
            'entreprise' => $this->getUser()->getEntreprise(),
            "user" => $admin,
            "libelle" => sprintf(
                "Bonjour %s,\n\n" .
                    "Nous vous informons qu'un nouveau paiement vient d'être enregistré dans la succursale **%s**.\n\n" .
                    "- Montant : %s FCFA\n" .
                    "- Effectué par : %s\n" .
                    "- Date : %s\n\n" .
                    "Cordialement,\nVotre application de gestion.",
                $admin->getLogin(),
                $this->getUser()->getSurccursale() ? $this->getUser()->getSurccursale()->getLibelle() : "N/A",
                number_format($data['montant'], 0, ',', ' '),
                $this->getUser()->getNom() && $this->getUser()->getPrenoms()
                    ? $this->getUser()->getNom() . " " . $this->getUser()->getPrenoms()
                    : $this->getUser()->getLogin(),
                (new \DateTime())->format('d/m/Y H:i')
            ),
            "titre" => "Paiement facture - " . ($this->getUser()->getSurccursale() ? $this->getUser()->getSurccursale()->getLibelle() : ""),
        ]);


        $this->sendMailService->send(
            $this->sendMail,
            $this->superAdmin,
            "Paiement facture - " . $this->getUser()->getEntreprise()->getLibelle(),
            "paiement_email",
            [
                "boutique_libelle" => $this->getUser()->getEntreprise()->getLibelle(),
                "montant" => number_format($request->get('avance'), 0, ',', ' ') . " FCFA",
                "date" => (new \DateTime())->format('d/m/Y H:i'),
            ]
        );

        return $this->responseData($paiementReservation, 'group1', ['Content-Type' => 'application/json']);
    }

    /**
     * Supprime une réservation
     */
    #[Route('/delete/{id}', methods: ['DELETE'])]
    #[OA\Delete(
        path: "/api/reservation/delete/{id}",
        summary: "Supprimer une réservation",
        description: "Permet de supprimer définitivement une réservation par son identifiant. Attention : cette action supprime également toutes les lignes de réservation et les paiements associés. Nécessite un abonnement actif.",
        tags: ['reservation']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: "Identifiant unique de la réservation à supprimer",
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: "Réservation supprimée avec succès",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "message", type: "string", example: "Operation effectuées avec succès"),
                new OA\Property(property: "deleted", type: "boolean", example: true)
            ]
        )
    )]
    #[OA\Response(response: 401, description: "Non authentifié")]
    #[OA\Response(response: 403, description: "Abonnement requis pour cette fonctionnalité")]
    #[OA\Response(response: 404, description: "Réservation non trouvée")]
    #[OA\Response(response: 500, description: "Erreur lors de la suppression")]
    public function delete(Request $request, Reservation $reservation, ReservationRepository $villeRepository): Response
    {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        try {
            if ($reservation != null) {
                $villeRepository->remove($reservation, true);
                $this->setMessage("Operation effectuées avec succès");
                $response = $this->response($reservation);
            } else {
                $this->setMessage("Cette ressource est inexistante");
                $this->setStatusCode(404);
                $response = $this->response([]);
            }
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            $this->setMessage("Erreur lors de la suppression de la réservation");
            $response = $this->response([]);
        }
        return $response;
    }

    /**
     * Supprime plusieurs réservations en masse
     */
    #[Route('/delete/all/items', methods: ['DELETE'])]
    #[OA\Delete(
        path: "/api/reservation/delete/all/items",
        summary: "Supprimer plusieurs réservations",
        description: "Permet de supprimer plusieurs réservations en une seule opération en fournissant un tableau d'identifiants. Toutes les lignes de réservation et paiements associés seront également supprimés. Nécessite un abonnement actif.",
        tags: ['reservation']
    )]
    #[OA\RequestBody(
        required: true,
        description: "Tableau des identifiants des réservations à supprimer",
        content: new OA\JsonContent(
            type: "object",
            required: ["ids"],
            properties: [
                new OA\Property(
                    property: 'ids',
                    type: 'array',
                    description: "Liste des identifiants des réservations à supprimer",
                    items: new OA\Items(type: 'integer', example: 1),
                    example: [1, 2, 3, 5, 8]
                )
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: "Réservations supprimées avec succès",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "message", type: "string", example: "Operation effectuées avec succès"),
                new OA\Property(property: "deletedCount", type: "integer", example: 5, description: "Nombre de réservations supprimées")
            ]
        )
    )]
    #[OA\Response(response: 400, description: "Données invalides")]
    #[OA\Response(response: 401, description: "Non authentifié")]
    #[OA\Response(response: 403, description: "Abonnement requis pour cette fonctionnalité")]
    #[OA\Response(response: 500, description: "Erreur lors de la suppression")]
    public function deleteAll(Request $request, ReservationRepository $villeRepository): Response
    {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        try {
            $data = json_decode($request->getContent(), true);

            $count = 0;
            foreach ($data['ids'] as $id) {
                $reservation = $villeRepository->find($id);

                if ($reservation != null) {
                    $villeRepository->remove($reservation);
                    $count++;
                }
            }
            $this->setMessage("Operation effectuées avec succès");
            $response = $this->json(['message' => 'Operation effectuées avec succès', 'deletedCount' => $count]);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            $this->setMessage("Erreur lors de la suppression des réservations");
            $response = $this->response([]);
        }
        return $response;
    }

    /**
     * Parse les filtres avancés de date (similaire aux statistiques)
     */
    private function parseAdvancedFilters(array $data): array
    {
        if (isset($data['filtre'])) {
            switch ($data['filtre']) {
                case 'jour':
                    // Utiliser la valeur fournie ou la date du système
                    $dateValue = $data['valeur'] ?? (new \DateTime())->format('Y-m-d');
                    $dateDebut = new \DateTime($dateValue);
                    $dateFin = new \DateTime($dateValue . ' 23:59:59');
                    break;
                case 'mois':
                    $dateDebut = new \DateTime(($data['valeur'] ?? (new \DateTime())->format('Y-m')) . '-01');
                    $dateFin = new \DateTime(($data['valeur'] ?? (new \DateTime())->format('Y-m')) . '-01');
                    $dateFin->modify('last day of this month')->setTime(23, 59, 59);
                    break;
                case 'annee':
                    $dateDebut = new \DateTime(($data['valeur'] ?? (new \DateTime())->format('Y')) . '-01-01');
                    $dateFin = new \DateTime(($data['valeur'] ?? (new \DateTime())->format('Y')) . '-12-31 23:59:59');
                    break;
                case 'periode':
                default:
                    $dateDebut = new \DateTime($data['dateDebut'] ?? '-30 days');
                    $dateFin = new \DateTime($data['dateFin'] ?? 'now');
                    break;
            }
        } else {
            $dateDebut = new \DateTime($data['dateDebut'] ?? '-30 days');
            $dateFin = new \DateTime($data['dateFin'] ?? 'now');
        }

        return [$dateDebut, $dateFin];
    }

    /**
     * Calcule les statistiques des réservations
     */
    private function calculateReservationStats(array $reservations): array
    {
        $totalReservations = count($reservations);
        $montantTotal = 0;
        $montantAvances = 0;
        $montantReste = 0;

        foreach ($reservations as $reservation) {
            $montantTotal += (float)$reservation->getMontant();
            $montantAvances += (float)$reservation->getAvance();
            $montantReste += (float)$reservation->getReste();
        }

        return [
            'total_reservations' => $totalReservations,
            'montant_total' => $montantTotal,
            'montant_avances' => $montantAvances,
            'montant_reste' => $montantReste
        ];
    }
}
