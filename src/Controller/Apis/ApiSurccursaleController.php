<?php

namespace App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use App\Entity\Caisse;
use App\Entity\CaisseSuccursale;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Entity\Surccursale;
use App\Repository\CaisseSuccursaleRepository;
use App\Repository\EntrepriseRepository;
use App\Repository\SurccursaleRepository;
use App\Repository\UserRepository;
use App\Service\SubscriptionChecker;
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
 * Contrôleur pour la gestion des succursales
 * Permet de gérer les établissements secondaires d'une entreprise avec création automatique de caisse
 */
#[Route('/api/succursale')]
#[OA\Tag(name: 'surccursale', description: 'Gestion des succursales (établissements secondaires) avec caisses dédiées')]
class ApiSurccursaleController extends ApiInterface
{
    /**
     * Liste toutes les succursales du système
     */
    #[Route('/', methods: ['GET'])]
    #[OA\Get(
        path: "/api/succursale/",
        summary: "Lister toutes les succursales",
        description: "Retourne la liste paginée de toutes les succursales du système, tous établissements confondus.",
        tags: ['surccursale']
    )]
    #[OA\Response(
        response: 200,
        description: "Liste des succursales récupérée avec succès",
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(
                type: "object",
                properties: [
                    new OA\Property(property: "id", type: "integer", example: 1, description: "Identifiant unique de la succursale"),
                    new OA\Property(property: "libelle", type: "string", example: "Succursale Abidjan Plateau", description: "Nom de la succursale"),
                    new OA\Property(property: "contact", type: "string", example: "+225 27 20 12 34 56", description: "Numéro de contact de la succursale"),
                    new OA\Property(property: "isActive", type: "boolean", example: true, description: "Statut actif/inactif"),
                    new OA\Property(
                        property: "entreprise",
                        type: "object",
                        description: "Entreprise propriétaire",
                        properties: [
                            new OA\Property(property: "id", type: "integer", example: 1),
                            new OA\Property(property: "nom", type: "string", example: "Fashion Boutique CI")
                        ]
                    ),
                    new OA\Property(
                        property: "caisse",
                        type: "object",
                        nullable: true,
                        description: "Caisse associée à la succursale",
                        properties: [
                            new OA\Property(property: "id", type: "integer", example: 5),
                            new OA\Property(property: "montant", type: "number", example: 500000, description: "Solde en caisse"),
                            new OA\Property(property: "reference", type: "string", example: "CAIS-2025-005")
                        ]
                    ),
                    new OA\Property(property: "boutiques", type: "array", description: "Liste des boutiques de cette succursale", items: new OA\Items(type: "object")),
                    new OA\Property(property: "createdAt", type: "string", format: "date-time", example: "2025-01-15T10:30:00+00:00")
                ]
            )
        )
    )]
    #[OA\Response(response: 500, description: "Erreur serveur lors de la récupération")]
    public function index(SurccursaleRepository $surccursaleRepository): Response
    {
        try {
            $surccursales = $this->paginationService->paginate($surccursaleRepository->findAll());
            $response = $this->responseData($surccursales, 'group1', ['Content-Type' => 'application/json']);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            $this->setMessage("Erreur lors de la récupération des succursales");
            $response = $this->response('[]');
        }

        return $response;
    }

    /**
     * Liste les succursales actives de l'entreprise de l'utilisateur
     */
    #[Route('/active/entreprise', methods: ['GET'])]
    #[OA\Get(
        path: "/api/succursale/active/entreprise",
        summary: "Lister les succursales actives de l'entreprise",
        description: "Retourne la liste paginée uniquement des succursales actives de l'entreprise de l'utilisateur connecté. Utile pour les formulaires de sélection.",
        tags: ['surccursale']
    )]
    #[OA\Response(
        response: 200,
        description: "Liste des succursales actives récupérée avec succès",
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(
                type: "object",
                properties: [
                    new OA\Property(property: "id", type: "integer", example: 1),
                    new OA\Property(property: "libelle", type: "string", example: "Succursale Abidjan Plateau"),
                    new OA\Property(property: "contact", type: "string", example: "+225 27 20 12 34 56"),
                    new OA\Property(property: "isActive", type: "boolean", example: true, description: "Toujours true dans cet endpoint"),
                    new OA\Property(property: "entreprise", type: "object"),
                    new OA\Property(property: "caisse", type: "object", nullable: true)
                ]
            )
        )
    )]
    #[OA\Response(response: 401, description: "Non authentifié")]
    #[OA\Response(response: 500, description: "Erreur lors de la récupération")]
    public function indexAllActive(SurccursaleRepository $surccursaleRepository): Response
    {
        try {
            $surccursales = $this->paginationService->paginate($surccursaleRepository->findBy(
                ['entreprise' => $this->getUser()->getEntreprise(), 'isActive' => true],
                ['libelle' => 'ASC']
            ));

            $response = $this->responseData($surccursales, 'group1', ['Content-Type' => 'application/json']);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            $this->setMessage("Erreur lors de la récupération des succursales actives");
            $response = $this->response('[]');
        }

        return $response;
    }

    /**
     * Liste toutes les succursales de l'entreprise de l'utilisateur
     */
    #[Route('/entreprise', methods: ['GET'])]
    #[OA\Get(
        path: "/api/succursale/entreprise",
        summary: "Lister toutes les succursales de l'entreprise",
        description: "Retourne la liste paginée de toutes les succursales (actives et inactives) de l'entreprise de l'utilisateur connecté.",
        tags: ['surccursale']
    )]
    #[OA\Response(
        response: 200,
        description: "Liste des succursales de l'entreprise récupérée avec succès",
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(
                type: "object",
                properties: [
                    new OA\Property(property: "id", type: "integer", example: 1),
                    new OA\Property(property: "libelle", type: "string", example: "Succursale Abidjan Plateau"),
                    new OA\Property(property: "contact", type: "string", example: "+225 27 20 12 34 56"),
                    new OA\Property(property: "isActive", type: "boolean", example: true),
                    new OA\Property(property: "entreprise", type: "object"),
                    new OA\Property(property: "caisse", type: "object", nullable: true),
                    new OA\Property(property: "boutiques", type: "array", description: "Boutiques de la succursale", items: new OA\Items(type: "object"))
                ]
            )
        )
    )]
    #[OA\Response(response: 401, description: "Non authentifié")]
    #[OA\Response(response: 500, description: "Erreur lors de la récupération")]
    public function indexAll(SurccursaleRepository $surccursaleRepository): Response
    {
        try {
            $surccursales = $this->paginationService->paginate($surccursaleRepository->findBy(
                ['entreprise' => $this->getUser()->getEntreprise()],
                ['id' => 'DESC']
            ));

            $response = $this->responseData($surccursales, 'group1', ['Content-Type' => 'application/json']);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            $this->setMessage("Erreur lors de la récupération des succursales de l'entreprise");
            $response = $this->response('[]');
        }

        return $response;
    }

    /**
     * Récupère les détails d'une succursale spécifique
     */
    #[Route('/get/one/{id}', methods: ['GET'])]
    #[OA\Get(
        path: "/api/succursale/get/one/{id}",
        summary: "Détails d'une succursale",
        description: "Affiche les informations détaillées d'une succursale spécifique, incluant sa caisse, ses boutiques et tous ses utilisateurs.",
        tags: ['surccursale']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: "Identifiant unique de la succursale",
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: "Succursale trouvée avec succès",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "id", type: "integer", example: 1),
                new OA\Property(property: "libelle", type: "string", example: "Succursale Abidjan Plateau"),
                new OA\Property(property: "contact", type: "string", example: "+225 27 20 12 34 56"),
                new OA\Property(property: "isActive", type: "boolean", example: true),
                new OA\Property(property: "entreprise", type: "object", description: "Entreprise mère"),
                new OA\Property(
                    property: "caisse",
                    type: "object",
                    nullable: true,
                    description: "Caisse de la succursale",
                    properties: [
                        new OA\Property(property: "id", type: "integer", example: 5),
                        new OA\Property(property: "montant", type: "number", example: 500000),
                        new OA\Property(property: "reference", type: "string", example: "CAIS-2025-005"),
                        new OA\Property(property: "type", type: "string", example: "succursale")
                    ]
                ),
                new OA\Property(property: "boutiques", type: "array", description: "Liste des boutiques rattachées", items: new OA\Items(type: "object")),
                new OA\Property(property: "users", type: "array", description: "Utilisateurs de cette succursale", items: new OA\Items(type: "object")),
                new OA\Property(property: "createdAt", type: "string", format: "date-time"),
                new OA\Property(property: "updatedAt", type: "string", format: "date-time")
            ]
        )
    )]
    #[OA\Response(response: 404, description: "Succursale non trouvée")]
    public function getOne(?Surccursale $surccursale): Response
    {
        try {
            if ($surccursale) {
                $response = $this->response($surccursale);
            } else {
                $this->setMessage('Cette ressource est inexistante');
                $this->setStatusCode(404);
                $response = $this->response(null);
            }
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            $this->setMessage($exception->getMessage());
            $response = $this->response('[]');
        }

        return $response;
    }

    /**
     * Crée une nouvelle succursale avec caisse automatique
     */
    #[Route('/create', methods: ['POST'])]
    #[OA\Post(
        path: "/api/succursale/create",
        summary: "Créer une succursale",
        description: "Permet de créer une nouvelle succursale pour l'entreprise. Crée automatiquement une caisse dédiée à cette succursale avec un solde initial de 0 FCFA. Le statut actif/inactif est déterminé par les paramètres de l'entreprise. Nécessite un abonnement actif.",
        tags: ['surccursale']
    )]
    #[OA\RequestBody(
        required: true,
        description: "Données de la succursale à créer",
        content: new OA\JsonContent(
            type: "object",
            required: ["libelle", "contact"],
            properties: [
                new OA\Property(
                    property: "libelle",
                    type: "string",
                    example: "Succursale Abidjan Cocody",
                    description: "Nom de la succursale (obligatoire)"
                ),
                new OA\Property(
                    property: "contact",
                    type: "string",
                    example: "+225 27 20 12 34 56",
                    description: "Numéro de téléphone de contact de la succursale (obligatoire)"
                )
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: "Succursale créée avec succès",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "id", type: "integer", example: 15),
                new OA\Property(property: "libelle", type: "string", example: "Succursale Abidjan Cocody"),
                new OA\Property(property: "contact", type: "string", example: "+225 27 20 12 34 56"),
        
            ]
        )
    )]
    #[OA\Response(response: 400, description: "Données invalides")]
    #[OA\Response(response: 401, description: "Non authentifié")]
    #[OA\Response(response: 403, description: "Abonnement requis pour cette fonctionnalité")]
    public function create(
        Request $request,
        Utils $utils,
        SubscriptionChecker $subscriptionChecker,
        CaisseSuccursaleRepository $caisseSuccursaleRepository,
        SurccursaleRepository $surccursaleRepository,
        EntrepriseRepository $entrepriseRepository
    ): Response {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        $data = json_decode($request->getContent(), true);

        // Création de la succursale
        $surccursale = new Surccursale();
        $surccursale->setLibelle($data['libelle']);
        $surccursale->setContact($data['contact']);
        $surccursale->setIsActive($subscriptionChecker->getSettingByUser($this->getUser()->getEntreprise(), "succursale"));
        $surccursale->setEntreprise($this->getUser()->getEntreprise());
        $surccursale->setCreatedBy($this->getUser());
        $surccursale->setUpdatedBy($this->getUser());
        $surccursale->setCreatedAtValue(new \DateTime());
        $surccursale->setUpdatedAt(new \DateTime());

        $errorResponse = $this->errorResponse($surccursale);
        if ($errorResponse !== null) {
            return $errorResponse;
        } else {
            $surccursaleRepository->add($surccursale, true);

            // Création automatique de la caisse succursale
            $caisse = new CaisseSuccursale();
            $caisse->setMontant(0);
            $caisse->setSuccursale($surccursale);
            $caisse->setReference($utils->generateReference('CAIS'));
            $caisse->setType(Caisse::TYPE['succursale']);
            $caisse->setEntreprise($this->getUser()->getEntreprise());
            $caisse->setCreatedBy($this->getUser());
            $caisse->setUpdatedBy($this->getUser());
            $caisse->setCreatedAtValue(new \DateTime());
            $caisse->setUpdatedAt(new \DateTime());
            $caisseSuccursaleRepository->add($caisse, true);
        }

        return $this->responseData($surccursale, 'group_surccursale', ['Content-Type' => 'application/json']);
    }

    /**
     * Met à jour une succursale existante
     */
    #[Route('/update/{id}', methods: ['PUT', 'POST'])]
    #[OA\Put(
        path: "/api/succursale/update/{id}",
        summary: "Mettre à jour une succursale",
        description: "Permet de modifier les informations d'une succursale existante, incluant son nom et son numéro de contact. Nécessite un abonnement actif.",
        tags: ['surccursale']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: "Identifiant unique de la succursale à mettre à jour",
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\RequestBody(
        required: true,
        description: "Nouvelles données de la succursale",
        content: new OA\JsonContent(
            type: "object",
            required: ["libelle", "contact"],
            properties: [
                new OA\Property(
                    property: "libelle",
                    type: "string",
                    example: "Succursale Abidjan Cocody (Mise à jour)",
                    description: "Nouveau nom de la succursale (obligatoire)"
                ),
                new OA\Property(
                    property: "contact",
                    type: "string",
                    example: "+225 27 20 98 76 54",
                    description: "Nouveau numéro de contact (obligatoire)"
                )
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: "Succursale mise à jour avec succès",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "id", type: "integer", example: 1),
                new OA\Property(property: "libelle", type: "string", example: "Succursale Abidjan Cocody (Mise à jour)"),
                new OA\Property(property: "contact", type: "string", example: "+225 27 20 98 76 54"),
                new OA\Property(property: "updatedAt", type: "string", format: "date-time")
            ]
        )
    )]
    #[OA\Response(response: 400, description: "Données invalides")]
    #[OA\Response(response: 401, description: "Non authentifié")]
    #[OA\Response(response: 403, description: "Abonnement requis pour cette fonctionnalité")]
    #[OA\Response(response: 404, description: "Succursale non trouvée")]
    public function update(
        Request $request,
        Surccursale $surccursale,
        SurccursaleRepository $surccursaleRepository,
        EntrepriseRepository $entrepriseRepository
    ): Response {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        try {
            $data = json_decode($request->getContent(), true);

            if ($surccursale != null) {
                if (isset($data['libelle'])) {
                    $surccursale->setLibelle($data['libelle']);
                }
                if (isset($data['contact'])) {
                    $surccursale->setContact($data['contact']);
                }

                $surccursale->setEntreprise($this->getUser()->getEntreprise());
                $surccursale->setUpdatedBy($this->getUser());
                $surccursale->setUpdatedAt(new \DateTime());

                $errorResponse = $this->errorResponse($surccursale);
                if ($errorResponse !== null) {
                    return $errorResponse;
                } else {
                    $surccursaleRepository->add($surccursale, true);
                }

                $response = $this->responseData($surccursale, 'group_surccursale', ['Content-Type' => 'application/json']);
            } else {
                $this->setMessage("Cette ressource est inexistante");
                $this->setStatusCode(404);
                $response = $this->response('[]');
            }
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            $this->setMessage("Erreur lors de la mise à jour de la succursale");
            $response = $this->response('[]');
        }
        return $response;
    }

    /**
     * Supprime une succursale
     */
    #[Route('/delete/{id}', methods: ['DELETE'])]
    #[OA\Delete(
        path: "/api/succursale/delete/{id}",
        summary: "Supprimer une succursale",
        description: "Permet de supprimer définitivement une succursale par son identifiant. Attention : cette action supprime également la caisse associée, toutes les boutiques de la succursale et leurs stocks. Nécessite un abonnement actif.",
        tags: ['surccursale']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: "Identifiant unique de la succursale à supprimer",
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: "Succursale supprimée avec succès",
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
    #[OA\Response(response: 404, description: "Succursale non trouvée")]
    #[OA\Response(response: 500, description: "Erreur lors de la suppression (peut-être des dépendances)")]
    public function delete(Request $request, Surccursale $surccursale, SurccursaleRepository $villeRepository): Response
    {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        try {
            if ($surccursale != null) {
                $villeRepository->remove($surccursale, true);
                $this->setMessage("Operation effectuées avec succès");
                $response = $this->response($surccursale);
            } else {
                $this->setMessage("Cette ressource est inexistante");
                $this->setStatusCode(404);
                $response = $this->response('[]');
            }
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            $this->setMessage("Erreur lors de la suppression de la succursale");
            $response = $this->response('[]');
        }
        return $response;
    }

    /**
     * Supprime plusieurs succursales en masse
     */
    #[Route('/delete/all/items', methods: ['DELETE'])]
    #[OA\Delete(
        path: "/api/succursale/delete/all/items",
        summary: "Supprimer plusieurs succursales",
        description: "Permet de supprimer plusieurs succursales en une seule opération en fournissant un tableau d'identifiants. Attention : toutes les caisses, boutiques et stocks associés seront également supprimés. Nécessite un abonnement actif.",
        tags: ['surccursale']
    )]
    #[OA\RequestBody(
        required: true,
        description: "Tableau des identifiants des succursales à supprimer",
        content: new OA\JsonContent(
            type: "object",
            required: ["ids"],
            properties: [
                new OA\Property(
                    property: 'ids',
                    type: 'array',
                    description: "Liste des identifiants des succursales à supprimer",
                    items: new OA\Items(type: 'integer', example: 1),
                    example: [1, 2, 3, 5, 8]
                )
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: "Succursales supprimées avec succès",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "message", type: "string", example: "Operation effectuées avec succès"),
                new OA\Property(property: "deletedCount", type: "integer", example: 5, description: "Nombre de succursales supprimées")
            ]
        )
    )]
    #[OA\Response(response: 400, description: "Données invalides")]
    #[OA\Response(response: 401, description: "Non authentifié")]
    #[OA\Response(response: 403, description: "Abonnement requis pour cette fonctionnalité")]
    #[OA\Response(response: 500, description: "Erreur lors de la suppression")]
    public function deleteAll(Request $request, SurccursaleRepository $villeRepository): Response
    {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        try {
            $data = json_decode($request->getContent(), true);

            $count = 0;
            foreach ($data['ids'] as $id) {
                $surccursale = $villeRepository->find($id);
                if ($surccursale != null) {
                    $villeRepository->remove($surccursale);
                    $count++;
                }
            }
            $this->setMessage("Operation effectuées avec succès");
            $response = $this->json(['message' => 'Operation effectuées avec succès', 'deletedCount' => $count]);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            $this->setMessage("Erreur lors de la suppression des succursales");
            $response = $this->response('[]');
        }
        return $response;
    }
}
