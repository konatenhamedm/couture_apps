<?php

namespace App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use App\Entity\LigneModule;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Entity\ModuleAbonnement;
use App\Repository\LigneModuleRepository;
use App\Repository\ModuleAbonnementRepository;
use App\Repository\ModuleRepository;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Annotation\Model as Model;
use Nelmio\ApiDocBundle\Attribute\Security;
use Nelmio\ApiDocBundle\Attribute\Model as AttributeModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;


/**
 * Contrôleur pour la gestion des modules d'abonnement
 * Permet de créer, lire, mettre à jour et supprimer des plans d'abonnement avec leurs modules/fonctionnalités associés
 */
#[Route('/api/moduleAbonnement')]
#[OA\Tag(name: 'moduleAbonnement', description: 'Gestion des plans d\'abonnement et leurs modules/fonctionnalités inclus')]
class ApiModuleAbonnementController extends ApiInterface
{


    /**
     * Liste tous les modules d'abonnement (plans d'abonnement)
     */
    #[Route('/', methods: ['GET'])]
    #[OA\Get(
        path: "/api/moduleAbonnement/",
        summary: "Lister tous les plans d'abonnement",
        description: "Retourne la liste paginée de tous les modules d'abonnement (plans tarifaires) disponibles dans le système avec leurs modules/fonctionnalités inclus.",
        tags: ['moduleAbonnement']
    )]
    #[OA\Response(
        response: 200,
        description: "Liste des plans d'abonnement récupérée avec succès",
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(
                type: "object",
                properties: [
                    new OA\Property(property: "id", type: "integer", example: 1, description: "Identifiant unique du plan d'abonnement"),
                    new OA\Property(property: "code", type: "string", example: "PREMIUM", description: "Code du plan"),
                    new OA\Property(property: "libelle", type: "string", example: "Abonnement Premium", description: "Nom du plan"),
                    new OA\Property(property: "description", type: "string", example: "Plan complet avec toutes les fonctionnalités", description: "Description détaillée"),
                    new OA\Property(property: "montant", type: "number", format: "float", example: 50000, description: "Prix de l'abonnement en FCFA"),
                    new OA\Property(property: "duree", type: "integer", example: 30, description: "Durée en jours"),
                    new OA\Property(property: "etat", type: "boolean", example: true, description: "Statut actif/inactif"),
                    new OA\Property(
                        property: "ligneModules",
                        type: "array",
                        description: "Liste des modules/fonctionnalités inclus",
                        items: new OA\Items(
                            type: "object",
                            properties: [
                                new OA\Property(property: "id", type: "integer", example: 1),
                                new OA\Property(property: "libelle", type: "string", example: "Gestion des stocks"),
                                new OA\Property(property: "description", type: "string", example: "Module de gestion complète des stocks"),
                                new OA\Property(property: "module", type: "object", description: "Module système associé")
                            ]
                        )
                    ),
                    new OA\Property(property: "createdAt", type: "string", format: "date-time")
                ]
            )
        )
    )]
    #[OA\Response(response: 500, description: "Erreur serveur lors de la récupération")]
    public function index(ModuleAbonnementRepository $moduleAbonnementRepository): Response
    {
        try {
            $moduleAbonnements = $this->paginationService->paginate($moduleAbonnementRepository->findAll());
            $response = $this->responseData($moduleAbonnements, 'group1', ['Content-Type' => 'application/json']);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            $this->setMessage("Erreur lors de la récupération des plans d'abonnement");
            $response = $this->response([]);
        }

        return $response;
    }

    /**
     * Récupère les détails d'un plan d'abonnement spécifique
     */
    #[Route('/get/one/{id}', methods: ['GET'])]
    #[OA\Get(
        path: "/api/moduleAbonnement/get/one/{id}",
        summary: "Détails d'un plan d'abonnement",
        description: "Affiche les informations détaillées d'un module d'abonnement spécifique, incluant tous les modules/fonctionnalités inclus, le prix et la durée.",
        tags: ['moduleAbonnement']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: "Identifiant unique du plan d'abonnement",
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: "Plan d'abonnement trouvé avec succès",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "id", type: "integer", example: 1),
                new OA\Property(property: "code", type: "string", example: "PREMIUM"),
                new OA\Property(property: "libelle", type: "string", example: "Abonnement Premium"),
                new OA\Property(property: "description", type: "string", example: "Plan complet avec toutes les fonctionnalités"),
                new OA\Property(property: "montant", type: "number", format: "float", example: 50000),
                new OA\Property(property: "duree", type: "integer", example: 30, description: "Durée en jours"),
                new OA\Property(property: "etat", type: "boolean", example: true),
                new OA\Property(
                    property: "ligneModules",
                    type: "array",
                    description: "Modules inclus dans cet abonnement",
                    items: new OA\Items(
                        type: "object",
                        properties: [
                            new OA\Property(property: "id", type: "integer", example: 1),
                            new OA\Property(property: "libelle", type: "string", example: "Gestion des stocks"),
                            new OA\Property(property: "description", type: "string", example: "Module de gestion complète des stocks"),
                            new OA\Property(property: "module", type: "object")
                        ]
                    )
                ),
                new OA\Property(property: "createdAt", type: "string", format: "date-time"),
                new OA\Property(property: "updatedAt", type: "string", format: "date-time")
            ]
        )
    )]
    #[OA\Response(response: 404, description: "Plan d'abonnement non trouvé")]
    public function getOne(?ModuleAbonnement $moduleAbonnement): Response
    {
        try {
            if ($moduleAbonnement) {
                $response = $this->response($moduleAbonnement);
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
     * Crée un nouveau plan d'abonnement avec ses modules inclus
     */
    #[Route('/create', methods: ['POST'])]
    #[OA\Post(
        path: "/api/moduleAbonnement/create",
        summary: "Créer un plan d'abonnement",
        description: "Permet de créer un nouveau plan d'abonnement (formule tarifaire) avec la liste des modules/fonctionnalités inclus. Chaque plan a un code unique, un prix, une durée et peut contenir plusieurs modules.",
        tags: ['moduleAbonnement']
    )]
    #[OA\RequestBody(
        required: true,
        description: "Données du plan d'abonnement à créer",
        content: new OA\JsonContent(
            type: "object",
            required: ["code", "montant", "duree", "etat", "description", "ligneModules"],
            properties: [
                new OA\Property(
                    property: "code",
                    type: "string",
                    example: "PREMIUM",
                    description: "Code unique du plan (obligatoire, en majuscules)"
                ),
                new OA\Property(
                    property: "montant",
                    type: "number",
                    format: "float",
                    example: 50000,
                    description: "Prix de l'abonnement en FCFA (obligatoire)"
                ),
                new OA\Property(
                    property: "duree",
                    type: "integer",
                    example: 30,
                    description: "Durée de validité en jours (obligatoire, ex: 30, 90, 365)"
                ),
                new OA\Property(
                    property: "etat",
                    type: "boolean",
                    example: true,
                    description: "Statut du plan (true=actif, false=inactif) (obligatoire)"
                ),
                new OA\Property(
                    property: "description",
                    type: "string",
                    example: "Plan complet avec toutes les fonctionnalités pour grandes entreprises",
                    description: "Description détaillée du plan (obligatoire)"
                ),
                new OA\Property(
                    property: "ligneModules",
                    type: "array",
                    description: "Liste des modules/fonctionnalités inclus dans ce plan (obligatoire, minimum 1)",
                    items: new OA\Items(
                        type: "object",
                        required: ["libelle", "description", "module"],
                        properties: [
                            new OA\Property(
                                property: "libelle",
                                type: "string",
                                example: "Gestion des stocks",
                                description: "Nom de la fonctionnalité (obligatoire)"
                            ),
                            new OA\Property(
                                property: "description",
                                type: "string",
                                example: "Module complet de gestion des entrées et sorties de stock",
                                description: "Description de la fonctionnalité (obligatoire)"
                            ),
                            new OA\Property(
                                property: "module",
                                type: "integer",
                                example: 1,
                                description: "ID du module système (obligatoire)"
                            )
                        ]
                    ),
                    minItems: 1,
                    example: [
                        [
                            "libelle" => "Gestion des stocks",
                            "description" => "Module complet de gestion des stocks",
                            "module" => 1
                        ],
                        [
                            "libelle" => "Gestion des factures",
                            "description" => "Facturation et suivi des paiements",
                            "module" => 2
                        ]
                    ]
                )
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: "Plan d'abonnement créé avec succès",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "id", type: "integer", example: 5),
                new OA\Property(property: "code", type: "string", example: "PREMIUM"),
                new OA\Property(property: "montant", type: "number", example: 50000),
                new OA\Property(property: "duree", type: "integer", example: 30),
                new OA\Property(property: "numero", type: "integer", example: 1),

                new OA\Property(property: "etat", type: "boolean", example: true),
                new OA\Property(property: "description", type: "string"),
                new OA\Property(property: "ligneModules", type: "array", items: new OA\Items(type: "object")),
                new OA\Property(property: "createdAt", type: "string", format: "date-time")
            ]
        )
    )]
    #[OA\Response(response: 400, description: "Données invalides ou module non trouvé")]
    #[OA\Response(response: 401, description: "Non authentifié")]
    public function create(Request $request, ModuleAbonnementRepository $moduleAbonnementRepository, ModuleRepository $moduleRepository): Response
    {
        $data = json_decode($request->getContent(), true);

        $moduleAbonnement = new ModuleAbonnement();
        $moduleAbonnement->setEtat($data['etat']);
        $moduleAbonnement->setCode($data['code']);
        $moduleAbonnement->setIsActive(true);
        $moduleAbonnement->setDescription($data['description']);
        $moduleAbonnement->setMontant($data['montant']);
        $moduleAbonnement->setDuree($data['duree']);
        $moduleAbonnement->setNumero($data['numero']);
        $moduleAbonnement->setCreatedBy($this->getUser());
        $moduleAbonnement->setUpdatedBy($this->getUser());
        $moduleAbonnement->setCreatedAtValue(new \DateTime());
        $moduleAbonnement->setUpdatedAt(new \DateTime());

        $errorResponse = $this->errorResponse($moduleAbonnement);
        if ($errorResponse !== null) {
            return $errorResponse;
        }

        // Ajout des lignes de modules
        $ligneModules = $data['ligneModules'] ?? [];
        foreach ($ligneModules as $ligneModuleData) {
            $module = $moduleRepository->find($ligneModuleData['module']);
            if (!$module) {
                $this->setMessage("Module non trouvé avec l'ID: " . $ligneModuleData['module']);
                return $this->response('[]', 400);
            }

            $ligneModule = new LigneModule();
            $ligneModule->setLibelle($ligneModuleData['libelle']);
            $ligneModule->setDescription($ligneModuleData['description']);
            $ligneModule->setModule($module);
            $ligneModule->setIsActive(true);
            $moduleAbonnement->addLigneModule($ligneModule);
        }

        $moduleAbonnementRepository->add($moduleAbonnement, true);

        return $this->responseData($moduleAbonnement, 'group1', ['Content-Type' => 'application/json']);
    }

    /**
     * Met à jour un plan d'abonnement avec gestion des modules
     */
    #[Route('/update/{id}', methods: ['PUT', 'POST'])]
    #[OA\Put(
        path: "/api/moduleAbonnement/update/{id}",
        summary: "Mettre à jour un plan d'abonnement",
        description: "Permet de mettre à jour un plan d'abonnement existant, y compris l'ajout, la modification ou la suppression de modules/fonctionnalités inclus. Les modules existants avec un ID seront mis à jour, ceux sans ID seront créés, et ceux dans ligneModulesDelete seront supprimés.",
        tags: ['moduleAbonnement']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: "Identifiant unique du plan d'abonnement à mettre à jour",
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\RequestBody(
        required: true,
        description: "Nouvelles données du plan d'abonnement",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "code", type: "string", example: "PREMIUM_PLUS", description: "Nouveau code"),
                new OA\Property(property: "montant", type: "number", format: "float", example: 65000, description: "Nouveau prix"),
                new OA\Property(property: "duree", type: "integer", example: 90, description: "Nouvelle durée"),
                new OA\Property(property: "etat", type: "boolean", example: true, description: "Nouveau statut"),
                new OA\Property(property: "description", type: "string", example: "Plan premium amélioré"),
                new OA\Property(
                    property: "ligneModules",
                    type: "array",
                    description: "Modules à ajouter ou mettre à jour (inclure 'id' pour modifier, exclure pour créer)",
                    items: new OA\Items(
                        type: "object",
                        properties: [
                            new OA\Property(property: "id", type: "integer", nullable: true, example: 1, description: "ID pour mise à jour, null pour création"),
                            new OA\Property(property: "libelle", type: "string", example: "Gestion des stocks avancée"),
                            new OA\Property(property: "description", type: "string", example: "Module de gestion des stocks avec analytics"),
                            new OA\Property(property: "module", type: "integer", example: 1)

                        ]
                    ),
                    example: [
                        ["id" => 1, "libelle" => "Gestion stocks avancée", "description" => "Module avancé", "module" => 1],
                        ["libelle" => "Nouveau module", "description" => "Nouvelle fonctionnalité", "module" => 3]
                    ]
                ),
                new OA\Property(
                    property: "ligneModulesDelete",
                    type: "array",
                    description: "IDs des modules à supprimer du plan",
                    items: new OA\Items(
                        type: "object",
                        properties: [
                            new OA\Property(property: "id", type: "integer", example: 2, description: "ID du module à supprimer")
                        ]
                    ),
                    example: [["id" => 2], ["id" => 5]]
                )
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: "Plan d'abonnement mis à jour avec succès",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "id", type: "integer", example: 1),
                new OA\Property(property: "code", type: "string", example: "PREMIUM_PLUS"),
                new OA\Property(property: "montant", type: "number", example: 65000),
                new OA\Property(property: "updatedAt", type: "string", format: "date-time"),
                new OA\Property(property: "numero", type: "integer", example: 1)

            ]
        )
    )]
    #[OA\Response(response: 400, description: "Données invalides")]
    #[OA\Response(response: 401, description: "Non authentifié")]
    #[OA\Response(response: 404, description: "Plan d'abonnement non trouvé")]
    public function update(
        Request $request,
        ModuleAbonnement $moduleAbonnement,
        LigneModuleRepository $ligneModuleRepository,
        ModuleAbonnementRepository $moduleAbonnementRepository,
        ModuleRepository $moduleRepository
    ): Response {
        try {
            $data = json_decode($request->getContent(), true);

            if ($moduleAbonnement != null) {
                $moduleAbonnement->setEtat($data['etat']);
                $moduleAbonnement->setCode($data['code']);
                $moduleAbonnement->setDescription($data['description']);
                $moduleAbonnement->setMontant($data['montant']);
                $moduleAbonnement->setNumero($data['numero']);
                $moduleAbonnement->setDuree($data['duree']);
                $moduleAbonnement->setUpdatedBy($this->getUser());
                $moduleAbonnement->setUpdatedAt(new \DateTime());

                // Gestion des lignes de modules (ajout et mise à jour)
                $ligneModules = $data['ligneModules'] ?? [];
                foreach ($ligneModules as $ligneModuleData) {
                    if (!isset($ligneModuleData['id']) || $ligneModuleData['id'] == null) {
                        // Création d'une nouvelle ligne
                        $module = $moduleRepository->find($ligneModuleData['module']);
                        if (!$module) {
                            $this->setMessage("Module non trouvé avec l'ID: " . $ligneModuleData['module']);
                            return $this->response('[]', 400);
                        }

                        $ligneModule = new LigneModule();
                        $ligneModule->setLibelle($ligneModuleData['libelle']);
                        $ligneModule->setDescription($ligneModuleData['description']);
                        $ligneModule->setModule($module);
                        $ligneModule->setIsActive(true);
                        
                        $moduleAbonnement->addLigneModule($ligneModule);
                    } else {
                        // Mise à jour d'une ligne existante
                        $ligneModule = $ligneModuleRepository->find($ligneModuleData['id']);
                        if ($ligneModule != null) {
                            $module = $moduleRepository->find($ligneModuleData['module']);
                            if (!$module) {
                                $this->setMessage("Module non trouvé avec l'ID: " . $ligneModuleData['module']);
                                return $this->response('[]', 400);
                            }

                            $ligneModule->setLibelle($ligneModuleData['libelle']);
                            $ligneModule->setDescription($ligneModuleData['description']);
                            $ligneModule->setModule($module);
                            $ligneModuleRepository->add($ligneModule, true);
                        }
                    }
                }

                // Gestion des suppressions de lignes
                $ligneModulesDeletes = $data['ligneModulesDelete'] ?? [];
                if (isset($ligneModulesDeletes) && is_array($ligneModulesDeletes)) {
                    foreach ($ligneModulesDeletes as $ligneModuleData) {
                        $ligneModule = $ligneModuleRepository->find($ligneModuleData['id']);
                        if ($ligneModule != null) {
                            $moduleAbonnement->removeLigneModule($ligneModule);
                            $ligneModuleRepository->remove($ligneModule, true);
                        }
                    }
                }

                $errorResponse = $this->errorResponse($moduleAbonnement);
                if ($errorResponse !== null) {
                    return $errorResponse;
                } else {
                    $moduleAbonnementRepository->add($moduleAbonnement, true);
                }

                $response = $this->responseData($moduleAbonnement, 'group1', ['Content-Type' => 'application/json']);
            } else {
                $this->setMessage("Cette ressource est inexistante");
                $this->setStatusCode(404);
                $response = $this->response([]);
            }
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            $this->setMessage("Erreur lors de la mise à jour du plan d'abonnement");
            $response = $this->response([]);
        }
        return $response;
    }

    /**
     * Supprime un plan d'abonnement
     */
    #[Route('/delete/{id}', methods: ['DELETE'])]
    #[OA\Delete(
        path: "/api/moduleAbonnement/delete/{id}",
        summary: "Supprimer un plan d'abonnement",
        description: "Permet de supprimer définitivement un plan d'abonnement par son identifiant. Attention : cette action supprime également toutes les lignes de modules associées et peut affecter les abonnements actifs utilisant ce plan.",
        tags: ['moduleAbonnement']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: "Identifiant unique du plan d'abonnement à supprimer",
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: "Plan d'abonnement supprimé avec succès",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "message", type: "string", example: "Operation effectuées avec succès"),
                new OA\Property(property: "deleted", type: "boolean", example: true)
            ]
        )
    )]
    #[OA\Response(response: 404, description: "Plan d'abonnement non trouvé")]
    #[OA\Response(response: 500, description: "Erreur lors de la suppression")]
    public function delete(Request $request, ModuleAbonnement $moduleAbonnement, ModuleAbonnementRepository $villeRepository): Response
    {
        try {
            if ($moduleAbonnement != null) {
                $villeRepository->remove($moduleAbonnement, true);
                $this->setMessage("Operation effectuées avec succès");
                $response = $this->response($moduleAbonnement);
            } else {
                $this->setMessage("Cette ressource est inexistante");
                $this->setStatusCode(404);
                $response = $this->response([]);
            }
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            $this->setMessage("Erreur lors de la suppression du plan d'abonnement");
            $response = $this->response([]);
        }
        return $response;
    }

    /**
     * Supprime plusieurs plans d'abonnement en masse
     */
    #[Route('/delete/all/items', methods: ['DELETE'])]
    #[OA\Delete(
        path: "/api/moduleAbonnement/delete/all/items",
        summary: "Supprimer plusieurs plans d'abonnement",
        description: "Permet de supprimer plusieurs plans d'abonnement en une seule opération en fournissant un tableau d'identifiants. Toutes les lignes de modules associées seront également supprimées.",
        tags: ['moduleAbonnement']
    )]
    #[OA\RequestBody(
        required: true,
        description: "Tableau des identifiants des plans d'abonnement à supprimer",
        content: new OA\JsonContent(
            type: "object",
            required: ["ids"],
            properties: [
                new OA\Property(
                    property: 'ids',
                    type: 'array',
                    description: "Liste des identifiants des plans d'abonnement à supprimer",
                    items: new OA\Items(type: 'integer', example: 1),
                    example: [1, 2, 3, 5]
                )
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: "Plans d'abonnement supprimés avec succès",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "message", type: "string", example: "Operation effectuées avec succès"),
                new OA\Property(property: "deletedCount", type: "integer", example: 4, description: "Nombre de plans supprimés")
            ]
        )
    )]
    #[OA\Response(response: 400, description: "Données invalides")]
    #[OA\Response(response: 401, description: "Non authentifié")]
    #[OA\Response(response: 500, description: "Erreur lors de la suppression")]
    /* #[Security(name: 'Bearer')] */
    public function deleteAll(Request $request, ModuleAbonnementRepository $villeRepository): Response
    {
        try {
            $data = json_decode($request->getContent(), true);

            foreach ($data['ids'] as $id) {
                $moduleAbonnement = $villeRepository->find($id);

                if ($moduleAbonnement != null) {
                    $villeRepository->remove($moduleAbonnement);
                }
            }
            $this->setMessage("Operation effectuées avec succès");
            $response = $this->response([]);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            $this->setMessage("Erreur lors de la suppression des plans d'abonnement");
            $response = $this->response([]);
        }
        return $response;
    }
}
