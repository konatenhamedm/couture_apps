<?php

namespace App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use App\DTO\ModeleBoutiqueDTO;
use App\Entity\Boutique;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Entity\ModeleBoutique;
use App\Repository\BoutiqueRepository;
use App\Repository\ModeleBoutiqueRepository;
use App\Repository\ModeleRepository;
use App\Repository\TypeUserRepository;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Attribute\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;


/**
 * Contrôleur pour la gestion des modèles de boutique
 * Gère l'association entre les modèles de vêtements et les boutiques avec prix et quantités spécifiques
 */
#[Route('/api/modeleBoutique')]
#[OA\Tag(name: 'modeleBoutique', description: 'Gestion des modèles de vêtements dans les boutiques (prix et stock par boutique)')]
class ApiModeleBoutiqueController extends ApiInterface
{
   
    /**
     * Récupère toutes les informations liées à un modèle de boutique
     */
    #[Route('/details/{id}', methods: ['GET'])]
    #[OA\Get(
        path: "/api/modeleBoutique/details/{id}",
        summary: "Détails complets d'un modèle de boutique",
        description: "Retourne toutes les informations liées à un modèle de boutique : entrées de stock, réservations et paiements boutique. Permet d'avoir une vue d'ensemble complète de l'activité d'un modèle dans une boutique.",
        tags: ['modeleBoutique']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: "Identifiant unique du modèle de boutique",
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: "Détails du modèle de boutique récupérés avec succès",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(
                    property: "modeleBoutique",
                    type: "object",
                    description: "Informations du modèle de boutique",
                    properties: [
                        new OA\Property(property: "id", type: "integer", example: 1),
                        new OA\Property(property: "prix", type: "string", example: "25000"),
                        new OA\Property(property: "quantite", type: "integer", example: 45),
                        new OA\Property(property: "taille", type: "string", example: "M"),
                        new OA\Property(property: "modele", type: "object", description: "Modèle parent"),
                        new OA\Property(property: "boutique", type: "object", description: "Boutique")
                    ]
                ),
                new OA\Property(
                    property: "entreesStock",
                    type: "array",
                    description: "Historique des entrées de stock pour ce modèle",
                    items: new OA\Items(
                        type: "object",
                        properties: [
                            new OA\Property(property: "id", type: "integer", example: 5),
                            new OA\Property(property: "quantite", type: "integer", example: 20),
                            new OA\Property(
                                property: "entreStock",
                                type: "object",
                                properties: [
                                    new OA\Property(property: "id", type: "integer", example: 12),
                                    new OA\Property(property: "type", type: "string", example: "Entree"),
                                    new OA\Property(property: "quantite", type: "integer", example: 100),
                                    new OA\Property(property: "createdAt", type: "string", format: "date-time")
                                ]
                            )
                        ]
                    )
                ),
                new OA\Property(
                    property: "reservations",
                    type: "array",
                    description: "Réservations incluant ce modèle",
                    items: new OA\Items(
                        type: "object",
                        properties: [
                            new OA\Property(property: "id", type: "integer", example: 8),
                            new OA\Property(property: "quantite", type: "integer", example: 2),
                            new OA\Property(
                                property: "reservation",
                                type: "object",
                                properties: [
                                    new OA\Property(property: "id", type: "integer", example: 3),
                                    new OA\Property(property: "montant", type: "number", example: 50000),
                                    new OA\Property(property: "avance", type: "number", example: 20000),
                                    new OA\Property(property: "dateRetrait", type: "string", format: "date-time"),
                                    new OA\Property(property: "client", type: "object", description: "Client")
                                ]
                            )
                        ]
                    )
                ),
                new OA\Property(
                    property: "paiementsBoutique",
                    type: "array",
                    description: "Paiements boutique pour ce modèle",
                    items: new OA\Items(
                        type: "object",
                        properties: [
                            new OA\Property(property: "id", type: "integer", example: 15),
                            new OA\Property(property: "quantite", type: "integer", example: 1),
                            new OA\Property(property: "prix", type: "string", example: "25000"),
                            new OA\Property(
                                property: "paiementBoutique",
                                type: "object",
                                properties: [
                                    new OA\Property(property: "id", type: "integer", example: 7),
                                    new OA\Property(property: "montant", type: "number", example: 25000),
                                    new OA\Property(property: "reference", type: "string", example: "PMT250115001"),
                                    new OA\Property(property: "createdAt", type: "string", format: "date-time")
                                ]
                            )
                        ]
                    )
                )
            ]
        )
    )]
    #[OA\Response(response: 401, description: "Non authentifié")]
    #[OA\Response(response: 403, description: "Abonnement requis pour cette fonctionnalité")]
    #[OA\Response(response: 404, description: "Modèle de boutique non trouvé")]
    #[OA\Response(response: 500, description: "Erreur lors de la récupération")]
    public function getDetails(int $id): Response {
       /*  if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        } */

        try {
            // Utiliser le trait pour trouver le modèle dans le bon environnement
            $modeleBoutique = $this->find(ModeleBoutique::class, $id);
            
            if (!$modeleBoutique) {
                $this->setMessage("Modèle de boutique non trouvé");
                return $this->response('[]', 404);
            }

            // Récupérer les entrées de stock
            $entreesStock = $modeleBoutique->getLigneEntres();

            // Récupérer les réservations
            $reservations = $modeleBoutique->getLigneReservations();

            // Récupérer les paiements boutique
            $paiementsBoutique = $modeleBoutique->getPaiementBoutiqueLignes();

            $result = [
                'modeleBoutique' => $modeleBoutique,
                'entreesStock' => $entreesStock->toArray(),
                'reservations' => $reservations->toArray(),
                'paiementsBoutique' => $paiementsBoutique->toArray()
            ];

            $response = $this->responseData($result, 'group_details', ['Content-Type' => 'application/json']);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            $this->setMessage("Erreur lors de la récupération des détails du modèle");
            $response = $this->response([]);
        }

        return $response;
    } 

    /**
     * Liste tous les modèles de boutique du système
     */
    #[Route('/', methods: ['GET'])]
    #[OA\Get(
        path: "/api/modeleBoutique/",
        summary: "Lister tous les modèles de boutique",
        description: "Retourne la liste paginée de tous les modèles de boutique disponibles dans le système, incluant les prix et quantités spécifiques à chaque boutique.",
        tags: ['modeleBoutique']
    )]
    #[OA\Response(
        response: 200,
        description: "Liste des modèles de boutique récupérée avec succès",
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(
                type: "object",
                properties: [
                    new OA\Property(property: "id", type: "integer", example: 1, description: "Identifiant unique du modèle de boutique"),
                    new OA\Property(property: "prix", type: "number", format: "float", example: 15000, description: "Prix de vente du modèle dans cette boutique"),
                    new OA\Property(property: "quantite", type: "integer", example: 50, description: "Quantité en stock dans cette boutique"),
                    new OA\Property(
                        property: "modele",
                        type: "object",
                        description: "Modèle de vêtement associé",
                        properties: [
                            new OA\Property(property: "id", type: "integer", example: 3),
                            new OA\Property(property: "libelle", type: "string", example: "Robe Wax Élégante"),
                            new OA\Property(property: "reference", type: "string", example: "MOD-2025-003"),
                            new OA\Property(property: "quantiteGlobale", type: "integer", example: 150, description: "Stock total tous établissements confondus")
                        ]
                    ),
                    new OA\Property(
                        property: "boutique",
                        type: "object",
                        description: "Boutique concernée",
                        properties: [
                            new OA\Property(property: "id", type: "integer", example: 1),
                            new OA\Property(property: "libelle", type: "string", example: "Boutique Centre-Ville")
                        ]
                    ),
                    new OA\Property(property: "createdAt", type: "string", format: "date-time")
                ]
            )
        )
    )]
    #[OA\Response(response: 500, description: "Erreur serveur lors de la récupération")]
    public function indexAllEntreprise(): Response
    {
        try {
            // Utiliser le trait pour obtenir automatiquement les données du bon environnement
            $modeleBoutiquesData = $this->findAll(ModeleBoutique::class);
            $modeleBoutiques = $this->paginationService->paginate($modeleBoutiquesData);
            $response = $this->responseData($modeleBoutiques, 'group1', ['Content-Type' => 'application/json']);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            $this->setMessage("Erreur lors de la récupération des modèles de boutique");
            $response = $this->response([]);
        }

        return $response;
    }

    /**
     * Liste tous les modèles de boutique du système
     */
    #[Route('/entreprise', methods: ['GET'])]
    #[OA\Get(
        path: "/api/modeleBoutique/entreprise",
        summary: "Lister tous les modèles de boutique",
        description: "Retourne la liste paginée de tous les modèles de boutique disponibles dans le système, incluant les prix et quantités spécifiques à chaque boutique.",
        tags: ['modeleBoutique']
    )]
    #[OA\Response(
        response: 200,
        description: "Liste des modèles de boutique récupérée avec succès",
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(
                type: "object",
                properties: [
                    new OA\Property(property: "id", type: "integer", example: 1, description: "Identifiant unique du modèle de boutique"),
                    new OA\Property(property: "prix", type: "number", format: "float", example: 15000, description: "Prix de vente du modèle dans cette boutique"),
                    new OA\Property(property: "quantite", type: "integer", example: 50, description: "Quantité en stock dans cette boutique"),
                    new OA\Property(
                        property: "modele",
                        type: "object",
                        description: "Modèle de vêtement associé",
                        properties: [
                            new OA\Property(property: "id", type: "integer", example: 3),
                            new OA\Property(property: "libelle", type: "string", example: "Robe Wax Élégante"),
                            new OA\Property(property: "reference", type: "string", example: "MOD-2025-003"),
                            new OA\Property(property: "quantiteGlobale", type: "integer", example: 150, description: "Stock total tous établissements confondus")
                        ]
                    ),
                    new OA\Property(
                        property: "boutique",
                        type: "object",
                        description: "Boutique concernée",
                        properties: [
                            new OA\Property(property: "id", type: "integer", example: 1),
                            new OA\Property(property: "libelle", type: "string", example: "Boutique Centre-Ville")
                        ]
                    ),
                    new OA\Property(property: "createdAt", type: "string", format: "date-time")
                ]
            )
        )
    )]
    #[OA\Response(response: 500, description: "Erreur serveur lors de la récupération")]
    public function index(): Response
    {
        try {
            // Utiliser le trait pour obtenir automatiquement les données du bon environnement
            $modeleBoutiquesData = $this->findBy(
                ModeleBoutique::class,
                ['entreprise' => $this->getUser()->getEntreprise()],
                ['id' => 'DESC']
            );
            $modeleBoutiques = $this->paginationService->paginate($modeleBoutiquesData);
            $response = $this->responseData($modeleBoutiques, "group_modeleBoutique", ['Content-Type' => 'application/json']);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            $this->setMessage("Erreur lors de la récupération des modèles de boutique");
            $response = $this->response([]);
        }

        return $response;
    }

    /**
     * Liste tous les modèles disponibles dans une boutique spécifique
     */
    #[Route('/modele/by/boutique/{id}', methods: ['GET'])]
    #[OA\Get(
        path: "/api/modeleBoutique/modele/by/boutique/{id}",
        summary: "Lister les modèles d'une boutique",
        description: "Retourne la liste paginée de tous les modèles de vêtements disponibles dans une boutique spécifique avec leurs prix et quantités. Nécessite un abonnement actif.",
        tags: ['modeleBoutique']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: "Identifiant unique de la boutique",
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: "Liste des modèles de la boutique récupérée avec succès",
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(
                type: "object",
                properties: [
                    new OA\Property(property: "id", type: "integer", example: 1),
                    new OA\Property(property: "prix", type: "number", format: "float", example: 15000),
                    new OA\Property(property: "quantite", type: "integer", example: 50),
                    new OA\Property(property: "modele", type: "object", description: "Détails du modèle"),
                    new OA\Property(property: "boutique", type: "object", description: "Boutique")
                ]
            )
        )
    )]
    #[OA\Response(response: 401, description: "Non authentifié")]
    #[OA\Response(response: 403, description: "Abonnement requis pour cette fonctionnalité")]
    #[OA\Response(response: 404, description: "Boutique non trouvée")]
    public function indexByBoutique(Boutique $boutique): Response
    {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        try {
            // Utiliser le trait pour obtenir automatiquement les données du bon environnement
            $modelesData = $this->findBy(
                ModeleBoutique::class,
                ['boutique' => $boutique->getId()],
                ['id' => 'DESC']
            );
            $modeles = $this->paginationService->paginate($modelesData);

            $response = $this->responseData($modeles, "group_modeleBoutique", ['Content-Type' => 'application/json']);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            $this->setMessage("Erreur lors de la récupération des modèles de la boutique");
            $response = $this->response([]);
        }

        return $response;
    }

    /**
     * Liste les modèles de boutique selon les droits de l'utilisateur
     */
    #[Route('/entreprise/{boutique}', methods: ['GET'])]
    #[OA\Get(
        path: "/api/modeleBoutique/entreprise/{boutique}",
        summary: "Lister les modèles selon les droits utilisateur",
        description: "Retourne la liste des modèles de boutique filtrée selon le type d'utilisateur : Super-admin peut spécifier une boutique, autres utilisateurs voient uniquement leur boutique. Nécessite un abonnement actif.",
        tags: ['modeleBoutique']
    )]
    #[OA\Parameter(
        name: 'boutique',
        in: 'path',
        required: true,
        description: "Identifiant de la boutique (utilisé uniquement pour les super-admins)",
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: "Liste des modèles de boutique récupérée avec succès",
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(
                type: "object",
                properties: [
                    new OA\Property(property: "id", type: "integer", example: 1),
                    new OA\Property(property: "prix", type: "number", format: "float", example: 15000),
                    new OA\Property(property: "quantite", type: "integer", example: 50),
                    new OA\Property(property: "modele", type: "object"),
                    new OA\Property(property: "boutique", type: "object")
                ]
            )
        )
    )]
    #[OA\Response(response: 401, description: "Non authentifié")]
    #[OA\Response(response: 403, description: "Abonnement requis pour cette fonctionnalité")]
    public function indexAll($boutique): Response
    {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        try {
            // Utiliser le trait pour obtenir automatiquement les données du bon environnement
            $typeUserSADM = $this->getRepository(\App\Entity\TypeUser::class)->findOneBy(['code' => 'SADM']);
            
            if ($this->getUser()->getType() == $typeUserSADM) {
                $modeleBoutiquesData = $this->findBy(
                    ModeleBoutique::class,
                    ['boutique' => $boutique],
                    ['id' => 'DESC']
                );
            } else {
                $modeleBoutiquesData = $this->findBy(
                    ModeleBoutique::class,
                    ['boutique' => $this->getUser()->getBoutique()],
                    ['id' => 'DESC']
                );
            }
            
            $modeleBoutiques = $this->paginationService->paginate($modeleBoutiquesData);
            $response = $this->responseData($modeleBoutiques, "group_modeleBoutique", ['Content-Type' => 'application/json']);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            $this->setMessage("Erreur lors de la récupération des modèles de boutique");
            $response = $this->response([]);
        }

        return $response;
    }

    /**
     * Récupère les détails d'un modèle de boutique spécifique
     */
    #[Route('/get/one/{id}', methods: ['GET'])]
    #[OA\Get(
        path: "/api/modeleBoutique/get/one/{id}",
        summary: "Détails d'un modèle de boutique",
        description: "Affiche les informations détaillées d'un modèle de boutique spécifique, incluant le prix, la quantité en stock et les informations du modèle parent. Nécessite un abonnement actif.",
        tags: ['modeleBoutique']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: "Identifiant unique du modèle de boutique",
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: "Modèle de boutique trouvé avec succès",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "id", type: "integer", example: 1),
                new OA\Property(property: "prix", type: "number", format: "float", example: 15000, description: "Prix de vente dans cette boutique"),
                new OA\Property(property: "quantite", type: "integer", example: 50, description: "Quantité disponible en stock"),
                new OA\Property(
                    property: "modele",
                    type: "object",
                    description: "Modèle de vêtement",
                    properties: [
                        new OA\Property(property: "id", type: "integer", example: 3),
                        new OA\Property(property: "libelle", type: "string", example: "Robe Wax Élégante"),
                        new OA\Property(property: "reference", type: "string", example: "MOD-2025-003"),
                        new OA\Property(property: "description", type: "string", example: "Belle robe en tissu wax"),
                        new OA\Property(property: "quantiteGlobale", type: "integer", example: 150)
                    ]
                ),
                new OA\Property(property: "boutique", type: "object", description: "Boutique"),
                new OA\Property(property: "createdAt", type: "string", format: "date-time"),
                new OA\Property(property: "updatedAt", type: "string", format: "date-time")
            ]
        )
    )]
    #[OA\Response(response: 401, description: "Non authentifié")]
    #[OA\Response(response: 403, description: "Abonnement requis pour cette fonctionnalité")]
    #[OA\Response(response: 404, description: "Modèle de boutique non trouvé")]
    public function getOne(?ModeleBoutique $modeleBoutique): Response
    {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        try {
            if ($modeleBoutique) {
                $response = $this->response($modeleBoutique);
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
     * Crée un nouveau modèle de boutique (association modèle-boutique avec prix et quantité)
     */
    #[Route('/create', methods: ['POST'])]
    #[OA\Post(
        path: "/api/modeleBoutique/create",
        summary: "Créer un modèle de boutique",
        description: "Permet d'associer un modèle de vêtement à une boutique avec un prix de vente et une quantité en stock spécifiques. Met automatiquement à jour la quantité globale du modèle. Nécessite un abonnement actif.",
        tags: ['modeleBoutique']
    )]
    #[OA\RequestBody(
        required: true,
        description: "Données du modèle de boutique à créer",
        content: new OA\JsonContent(
            type: "object",
            required: ["prix", "quantite", "modele", "boutique"],
            properties: [
                new OA\Property(
                    property: "prix",
                    type: "number",
                    format: "float",
                    example: 15000,
                    description: "Prix de vente du modèle dans cette boutique en FCFA (obligatoire)"
                ),
                new OA\Property(
                    property: "quantite",
                    type: "integer",
                    example: 50,
                    description: "Quantité initiale en stock dans cette boutique (obligatoire)"
                ),
                new OA\Property(
                    property: "taille",
                    type: "string",
                    example: "M",
                    description: "Taille du modèle dans cette boutique (obligatoire)"
                ),
                new OA\Property(
                    property: "modele",
                    type: "integer",
                    example: 3,
                    description: "ID du modèle de vêtement à associer (obligatoire)"
                ),
                new OA\Property(
                    property: "boutique",
                    type: "integer",
                    example: 1,
                    description: "ID de la boutique concernée (obligatoire)"
                )
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: "Modèle de boutique créé avec succès",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                
                new OA\Property(property: "prix", type: "number", example: 15000),
                new OA\Property(property: "quantite", type: "integer", example: 50),
                new OA\Property(property: "modele", type: "object"),
                new OA\Property(property: "taille", type: "string", example: "M", description: "Taille du modèle dans cette boutique"),
                new OA\Property(property: "boutique", type: "object"),
                
            ]
        )
    )]
    #[OA\Response(response: 400, description: "Données invalides ou modèle/boutique non trouvé")]
    #[OA\Response(response: 401, description: "Non authentifié")]
    #[OA\Response(response: 403, description: "Abonnement requis pour cette fonctionnalité")]
    public function create(Request $request): Response
    {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        $data = json_decode($request->getContent(), true);

        // Utiliser le trait pour trouver les entités dans le bon environnement
        $modele = $this->find(\App\Entity\Modele::class, $data['modele']);
        if (!$modele) {
            $this->setMessage("Modèle non trouvé avec l'ID: " . $data['modele']);
            return $this->response('[]', 400);
        }

        $boutique = $this->find(Boutique::class, $data['boutique']);
        if (!$boutique) {
            $this->setMessage("Boutique non trouvée avec l'ID: " . $data['boutique']);
            return $this->response('[]', 400);
        }

        $modeleBoutique = new ModeleBoutique();
        $modeleBoutique->setPrix($data['prix']);
        $modeleBoutique->setQuantite($data['quantite']);
        $modeleBoutique->setBoutique($boutique);
        $modeleBoutique->setModele($modele);
        $modeleBoutique->setTaille($data['taille']);
        $modeleBoutique->setCreatedBy($this->getUser());
        $modeleBoutique->setUpdatedBy($this->getUser());
        $modeleBoutique->setCreatedAtValue(new \DateTime());
        $modeleBoutique->setUpdatedAt(new \DateTime());

        $errorResponse = $this->errorResponse($modeleBoutique);
        if ($errorResponse !== null) {
            return $errorResponse;
        } else {
            // Utiliser le trait pour sauvegarder dans le bon environnement
            $this->save($modeleBoutique);

            // Mise à jour de la quantité globale du modèle
            $modele->setQuantiteGlobale($modele->getQuantiteGlobale() + $modeleBoutique->getQuantite());
            $this->save($modele);
        }

        return $this->responseData($modeleBoutique, 'group_modeleBoutique', ['Content-Type' => 'application/json']);
    }

    /**
     * Met à jour un modèle de boutique existant
     */
    #[Route('/update/{id}', methods: ['PUT', 'POST'])]
    #[OA\Put(
        path: "/api/modeleBoutique/update/{id}",
        summary: "Mettre à jour un modèle de boutique",
        description: "Permet de mettre à jour les informations d'un modèle de boutique (prix, modèle associé, boutique). Note : La quantité ne peut pas être modifiée directement ici, utilisez les endpoints de gestion de stock. Nécessite un abonnement actif.",
        tags: ['modeleBoutique']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: "Identifiant unique du modèle de boutique à mettre à jour",
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\RequestBody(
        required: true,
        description: "Nouvelles données du modèle de boutique",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(
                    property: "prix",
                    type: "number",
                    format: "float",
                    example: 18000,
                    description: "Nouveau prix de vente en FCFA"
                ),
                 new OA\Property(
                    property: "taille",
                    type: "string",
                    example: "M",
                    description: "Taille du modèle dans cette boutique (obligatoire)"
                ),
                new OA\Property(
                    property: "modele",
                    type: "integer",
                    example: 5,
                    description: "Nouvel ID du modèle à associer"
                ),
                new OA\Property(
                    property: "boutique",
                    type: "integer",
                    example: 2,
                    description: "Nouvel ID de la boutique"
                )
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: "Modèle de boutique mis à jour avec succès",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "id", type: "integer", example: 1),
                new OA\Property(property: "modele", type: "object", description: "Modèle inchangé"),
                new OA\Property(property: "taille", type: "string", example: "M", description: "Taille inchangée"),
                new OA\Property(property: "prix", type: "number", example: 18000),
                new OA\Property(property: "boutique", type: "object", description: "Boutique inchangée"),
                
               
            ]
        )
    )]
    #[OA\Response(response: 400, description: "Données invalides")]
    #[OA\Response(response: 401, description: "Non authentifié")]
    #[OA\Response(response: 403, description: "Abonnement requis pour cette fonctionnalité")]
    #[OA\Response(response: 404, description: "Modèle de boutique non trouvé")]
    public function update(Request $request, int $id): Response
    {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        try {
            $data = json_decode($request->getContent(), true);

            // Utiliser le trait pour trouver le modèle dans le bon environnement
            $modeleBoutique = $this->find(ModeleBoutique::class, $id);

            if ($modeleBoutique != null) {
                if (isset($data['modele'])) {
                    $modele = $this->find(\App\Entity\Modele::class, $data['modele']);
                    if (!$modele) {
                        $this->setMessage("Modèle non trouvé avec l'ID: " . $data['modele']);
                        return $this->response('[]', 400);
                    }
                    $modeleBoutique->setModele($modele);
                }

                if (isset($data['prix'])) {
                    $modeleBoutique->setPrix($data['prix']);
                }

                if (isset($data['taille'])) {
                    $modeleBoutique->setTaille($data['taille']);
                }

                if (isset($data['boutique'])) {
                    $boutique = $this->find(Boutique::class, $data['boutique']);
                    if (!$boutique) {
                        $this->setMessage("Boutique non trouvée avec l'ID: " . $data['boutique']);
                        return $this->response('[]', 400);
                    }
                    $modeleBoutique->setBoutique($boutique);
                }

                $modeleBoutique->setUpdatedBy($this->getUser());
                $modeleBoutique->setUpdatedAt(new \DateTime());

                $errorResponse = $this->errorResponse($modeleBoutique);
                if ($errorResponse !== null) {
                    return $errorResponse;
                } else {
                    // Utiliser le trait pour sauvegarder dans le bon environnement
                    $this->save($modeleBoutique);
                }

                $response = $this->responseData($modeleBoutique, "group_modeleBoutique", ['Content-Type' => 'application/json']);
            } else {
                $this->setMessage("Cette ressource est inexistante");
                $this->setStatusCode(404);
                $response = $this->response([]);
            }
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            $this->setMessage("Erreur lors de la mise à jour du modèle de boutique");
            $response = $this->response([]);
        }
        return $response;
    }

    /**
     * Supprime un modèle de boutique
     */
    #[Route('/delete/{id}', methods: ['DELETE'])]
    #[OA\Delete(
        path: "/api/modeleBoutique/delete/{id}",
        summary: "Supprimer un modèle de boutique",
        description: "Permet de supprimer définitivement l'association entre un modèle et une boutique. Attention : cette action supprime également l'historique de stock lié à ce modèle dans cette boutique. Nécessite un abonnement actif.",
        tags: ['modeleBoutique']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: "Identifiant unique du modèle de boutique à supprimer",
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: "Modèle de boutique supprimé avec succès",
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
    #[OA\Response(response: 404, description: "Modèle de boutique non trouvé")]
    #[OA\Response(response: 500, description: "Erreur lors de la suppression")]
    public function delete(Request $request, int $id): Response
    {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        try {
            // Utiliser le trait pour trouver le modèle dans le bon environnement
            $modeleBoutique = $this->find(ModeleBoutique::class, $id);
            
            if ($modeleBoutique != null) {
                // Utiliser le trait pour supprimer dans le bon environnement
                $this->remove($modeleBoutique);
                $this->setMessage("Operation effectuées avec succès");
                $response = $this->response($modeleBoutique);
            } else {
                $this->setMessage("Cette ressource est inexistante");
                $this->setStatusCode(404);
                $response = $this->response([]);
            }
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            $this->setMessage("Erreur lors de la suppression du modèle de boutique");
            $response = $this->response([]);
        }
        return $response;
    }

    /**
     * Supprime plusieurs modèles de boutique en masse
     */
    #[Route('/delete/all/items', methods: ['DELETE'])]
    #[OA\Delete(
        path: "/api/modeleBoutique/delete/all/items",
        summary: "Supprimer plusieurs modèles de boutique",
        description: "Permet de supprimer plusieurs associations modèle-boutique en une seule opération en fournissant un tableau d'identifiants. Nécessite un abonnement actif.",
        tags: ['modeleBoutique']
    )]
    #[OA\RequestBody(
        required: true,
        description: "Tableau des identifiants des modèles de boutique à supprimer",
        content: new OA\JsonContent(
            type: "object",
            required: ["ids"],
            properties: [
                new OA\Property(
                    property: 'ids',
                    type: 'array',
                    description: "Liste des identifiants des modèles de boutique à supprimer",
                    items: new OA\Items(type: 'integer', example: 1),
                    example: [1, 2, 3, 5, 8]
                )
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: "Modèles de boutique supprimés avec succès",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "message", type: "string", example: "Operation effectuées avec succès"),
                new OA\Property(property: "deletedCount", type: "integer", example: 5, description: "Nombre de modèles supprimés")
            ]
        )
    )]
    #[OA\Response(response: 400, description: "Données invalides")]
    #[OA\Response(response: 401, description: "Non authentifié")]
    #[OA\Response(response: 403, description: "Abonnement requis pour cette fonctionnalité")]
    #[OA\Response(response: 500, description: "Erreur lors de la suppression")]
    public function deleteAll(Request $request): Response
    {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        try {
            $data = json_decode($request->getContent(), true);

            $count = 0;
            foreach ($data['ids'] as $id) {
                // Utiliser le trait pour trouver le modèle dans le bon environnement
                $modeleBoutique = $this->find(ModeleBoutique::class, $id);

                if ($modeleBoutique != null) {
                    // Utiliser le trait pour supprimer dans le bon environnement
                    $this->remove($modeleBoutique, false); // Ne pas flush à chaque suppression
                    $count++;
                }
            }
            
            // Flush une seule fois à la fin
            $this->getEntityManager()->flush();
            
            $this->setMessage("Operation effectuées avec succès");
            $response = $this->json(['message' => 'Operation effectuées avec succès', 'deletedCount' => $count]);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            $this->setMessage("Erreur lors de la suppression des modèles de boutique");
            $response = $this->response([]);
        }
        return $response;
    }
}
