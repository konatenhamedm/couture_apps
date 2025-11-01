<?php

namespace App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Entity\Abonnement;
use App\Entity\ModuleAbonnement;
use App\Repository\AbonnementRepository;
use App\Repository\FactureRepository;
use App\Repository\PaiementFactureRepository;
use App\Repository\UserRepository;
use App\Service\PaiementService;
use App\Service\Utils;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Annotation\Model as Model;
use Nelmio\ApiDocBundle\Attribute\Security;
use Nelmio\ApiDocBundle\Attribute\Model as AttributeModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

/**
 * Contrôleur pour la gestion des abonnements
 * Permet de créer, lister et gérer les abonnements des entreprises
 */
#[Route('/api/abonnement')]
#[OA\Tag(name: 'abonnement', description: 'Gestion des abonnements d\'entreprise')]
class ApiAbonnementController extends ApiInterface
{
    /**
     * Liste tous les abonnements disponibles dans le système
     */
    #[Route('/', methods: ['GET'])]
    #[OA\Get(
        path: "/api/abonnement/",
        summary: "Lister tous les abonnements",
        description: "Retourne la liste paginée de tous les abonnements disponibles dans le système",
        tags: ['abonnement']
    )]
    #[OA\Response(
        response: 200,
        description: "Liste des abonnements récupérée avec succès",
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(
                type: "object",
                properties: [
                    new OA\Property(property: "id", type: "integer", example: 1, description: "Identifiant unique de l'abonnement"),
                    new OA\Property(property: "libelle", type: "string", example: "Abonnement Premium", description: "Nom de l'abonnement"),
                    new OA\Property(property: "prix", type: "number", format: "float", example: 99.99, description: "Prix de l'abonnement"),
                    new OA\Property(property: "duree", type: "integer", example: 12, description: "Durée en mois"),
                    new OA\Property(property: "description", type: "string", nullable: true, example: "Abonnement avec toutes les fonctionnalités", description: "Description détaillée"),
                    new OA\Property(property: "etat", type: "string", example: "actif", description: "État de l'abonnement (actif, inactif, suspendu)"),
                    new OA\Property(property: "dateDebut", type: "string", format: "date-time", example: "2025-01-01T00:00:00+00:00", description: "Date de début"),
                    new OA\Property(property: "dateFin", type: "string", format: "date-time", nullable: true, example: "2026-01-01T00:00:00+00:00", description: "Date de fin")
                ]
            )
        )
    )]
    #[OA\Response(response: 500, description: "Erreur serveur lors de la récupération")]
    public function index(AbonnementRepository $moduleRepository): Response
    {
        try {
            $categories = $this->paginationService->paginate($moduleRepository->findAll());
            $response = $this->responseData($categories, 'group1', ['Content-Type' => 'application/json']);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            $this->setMessage("Erreur lors de la récupération des abonnements");
            $response = $this->response('[]');
        }

        return $response;
    }

    /**
     * Crée un nouvel abonnement pour une entreprise avec paiement
     */
    #[Route('/abonnement/{id}', methods: ['POST'])]
    #[OA\Post(
        path: "/api/abonnement/abonnement/{id}",
        summary: "Créer un nouvel abonnement",
        description: "Permet de créer un nouvel abonnement pour une entreprise en traitant le paiement associé. Gère les informations utilisateur, boutique et succursale.",
        tags: ['abonnement']
    )]
    #[OA\Parameter(
        name: "id",
        in: "path",
        required: true,
        description: "Identifiant du module d'abonnement",
        schema: new OA\Schema(type: "integer", example: 1)
    )]
    #[OA\RequestBody(
        required: true,
        description: "Données nécessaires pour créer l'abonnement et traiter le paiement",
        content: new OA\JsonContent(
            type: "object",
            required: ["dataUser", "email", "entrepriseId", "numero", "operateur"],
            properties: [
                new OA\Property(
                    property: "dataUser",
                    type: "array",
                    description: "Tableau des identifiants des utilisateurs à associer à l'abonnement",
                    items: new OA\Items(type: "integer", example: 1)
                ),
                new OA\Property(
                    property: "dataBoutique",
                    type: "array",
                    description: "Tableau des identifiants des boutiques à associer (optionnel)",
                    items: new OA\Items(type: "integer", example: 2),
                    nullable: true
                ),
                new OA\Property(
                    property: "dataSuccursale",
                    type: "array",
                    description: "Tableau des identifiants des succursales à associer (optionnel)",
                    items: new OA\Items(type: "integer", example: 3),
                    nullable: true
                ),
                new OA\Property(
                    property: "email",
                    type: "string",
                    format: "email",
                    example: "contact@entreprise.com",
                    description: "Email de contact pour la facturation (obligatoire)"
                ),
                new OA\Property(
                    property: "entrepriseId",
                    type: "string",
                    example: "ENT001",
                    description: "Identifiant de l'entreprise (obligatoire)"
                ),
                new OA\Property(
                    property: "numero",
                    type: "string",
                    example: "+225 0123456789",
                    description: "Numéro de téléphone pour le paiement mobile (obligatoire)"
                ),
                new OA\Property(
                    property: "operateur",
                    type: "string",
                    enum: ["orange", "mtn", "moov", "wave"],
                    example: "orange",
                    description: "Opérateur de paiement mobile (obligatoire)"
                )
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: "Abonnement créé avec succès et paiement traité",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "success", type: "boolean", example: true, description: "Statut de la création"),
                new OA\Property(property: "abonnementId", type: "integer", example: 15, description: "ID de l'abonnement créé"),
                new OA\Property(property: "transactionId", type: "string", example: "TRX-2025-001", description: "ID de la transaction de paiement"),
                new OA\Property(property: "statut", type: "string", example: "en_attente", description: "Statut du paiement"),
                new OA\Property(property: "message", type: "string", example: "Abonnement créé avec succès, paiement en cours de traitement", description: "Message de confirmation")
            ]
        )
    )]
    #[OA\Response(response: 400, description: "Données invalides ou manquantes")]
    #[OA\Response(response: 401, description: "Non authentifié")]
    #[OA\Response(response: 404, description: "Module d'abonnement non trouvé")]
    #[OA\Response(response: 500, description: "Erreur lors du traitement du paiement")]
    public function createAbonnement(
        Request $request,
        UserRepository $userRepository,
        PaiementService $paiementService,
        AbonnementRepository $abonnementRepository,
        Utils $utils,
        ModuleAbonnement $moduleAbonnement,
        FactureRepository $factureRepository,
        PaiementFactureRepository $paiementRepository
    ): Response {
        $data = json_decode($request->getContent(), true);

        $createTransactionData = $paiementService->traiterPaiement([
            'dataUser' => $data['dataUser'] ?? [],
            'dataBoutique' => $data['dataBoutique'] ?? [],
            'dataSuccursale' => $data['dataSuccursale'] ?? [],
            'email' => $data['email'],
            'entrepriseId' => $data['entrepriseId'],
            'numero' => $data['numero'],
            'operateur' => $data['operateur'],
        ], $this->getUser(), $moduleAbonnement);

        return $this->response($createTransactionData);
    }

    /**
     * Liste tous les abonnements d'une entreprise spécifique
     */
    #[Route('/entreprise', methods: ['GET'])]
    #[OA\Get(
        path: "/api/abonnement/entreprise",
        summary: "Lister les abonnements d'une entreprise",
        description: "Retourne la liste paginée de tous les abonnements de l'entreprise de l'utilisateur authentifié",
        tags: ['abonnement']
    )]
    #[OA\Response(
        response: 200,
        description: "Liste des abonnements de l'entreprise récupérée avec succès",
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(
                type: "object",
                properties: [
                    new OA\Property(property: "id", type: "integer", example: 1, description: "Identifiant de l'abonnement"),
                    new OA\Property(property: "libelle", type: "string", example: "Abonnement Premium", description: "Nom de l'abonnement"),
                    new OA\Property(property: "prix", type: "number", format: "float", example: 99.99, description: "Prix"),
                    new OA\Property(property: "duree", type: "integer", example: 12, description: "Durée en mois"),
                    new OA\Property(property: "etat", type: "string", example: "actif", description: "État de l'abonnement"),
                    new OA\Property(
                        property: "entreprise",
                        type: "object",
                        description: "Informations de l'entreprise",
                        properties: [
                            new OA\Property(property: "id", type: "integer", example: 5),
                            new OA\Property(property: "nom", type: "string", example: "Mon Entreprise SARL")
                        ]
                    ),
                    new OA\Property(property: "dateDebut", type: "string", format: "date-time", example: "2025-01-01T00:00:00+00:00"),
                    new OA\Property(property: "dateFin", type: "string", format: "date-time", nullable: true, example: "2026-01-01T00:00:00+00:00")
                ]
            )
        )
    )]
    #[OA\Response(response: 401, description: "Non authentifié")]
    #[OA\Response(response: 500, description: "Erreur lors de la récupération")]
    public function indexAll(AbonnementRepository $abonnementRepository): Response
    {
        try {
            $typeMesures = $this->paginationService->paginate(
                $abonnementRepository->findBy(
                    ['entreprise' => $this->getUser()->getEntreprise()],
                    ['id' => 'ASC']
                )
            );

            $response = $this->responseData($typeMesures, 'group1', ['Content-Type' => 'application/json']);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            $this->setMessage("Erreur lors de la récupération des abonnements de l'entreprise");
            $response = $this->response('[]');
        }

        return $response;
    }

    /**
     * Liste uniquement les abonnements actifs d'une entreprise
     */
    #[Route('/entreprise/actif', methods: ['GET'])]
    #[OA\Get(
        path: "/api/abonnement/entreprise/actif",
        summary: "Lister les abonnements actifs d'une entreprise",
        description: "Retourne la liste paginée des abonnements actifs de l'entreprise de l'utilisateur authentifié. Exclut les abonnements expirés, suspendus ou inactifs.",
        tags: ['abonnement']
    )]
    #[OA\Response(
        response: 200,
        description: "Liste des abonnements actifs de l'entreprise récupérée avec succès",
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(
                type: "object",
                properties: [
                    new OA\Property(property: "id", type: "integer", example: 1, description: "Identifiant de l'abonnement"),
                    new OA\Property(property: "libelle", type: "string", example: "Abonnement Premium", description: "Nom de l'abonnement"),
                    new OA\Property(property: "prix", type: "number", format: "float", example: 99.99, description: "Prix de l'abonnement"),
                    new OA\Property(property: "duree", type: "integer", example: 12, description: "Durée en mois"),
                    new OA\Property(property: "etat", type: "string", example: "actif", description: "État de l'abonnement (toujours 'actif' pour cet endpoint)"),
                    new OA\Property(
                        property: "entreprise",
                        type: "object",
                        description: "Informations de l'entreprise",
                        properties: [
                            new OA\Property(property: "id", type: "integer", example: 5),
                            new OA\Property(property: "nom", type: "string", example: "Mon Entreprise SARL")
                        ]
                    ),
                    new OA\Property(property: "dateDebut", type: "string", format: "date-time", example: "2025-01-01T00:00:00+00:00", description: "Date de début de l'abonnement"),
                    new OA\Property(property: "dateFin", type: "string", format: "date-time", example: "2026-01-01T00:00:00+00:00", description: "Date de fin prévue"),
                    new OA\Property(property: "joursRestants", type: "integer", example: 365, description: "Nombre de jours restants avant expiration")
                ]
            )
        )
    )]
    #[OA\Response(response: 401, description: "Non authentifié")]
    #[OA\Response(response: 500, description: "Erreur lors de la récupération")]
    public function indexActif(AbonnementRepository $moduleRepository): Response
    {
        try {
            $typeMesures = $this->paginationService->paginate(
                $moduleRepository->findBy(
                    ['entreprise' => $this->getUser()->getEntreprise(), 'etat' => 'actif'],
                    ['id' => 'ASC']
                )
            );

            $response = $this->responseData($typeMesures, 'group1', ['Content-Type' => 'application/json']);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            $this->setMessage("Erreur lors de la récupération des abonnements actifs");
            $response = $this->response('[]');
        }

        return $response;
    }
}
