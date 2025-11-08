<?php

namespace App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Entity\CategorieMesure;
use App\Repository\CategorieMesureRepository;
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
 * Contrôleur pour la gestion des catégories de mesure
 * Permet de créer, lire, mettre à jour et supprimer des catégories de mesure
 */
#[Route('/api/categorieMesure')]
#[OA\Tag(name: 'categorieMesure', description: 'Gestion des catégories de mesure')]
class ApiCategorieMesureController extends ApiInterface
{
    /**
     * Liste toutes les catégories de mesure du système
     */
    #[Route('/', methods: ['GET'])]
    #[OA\Get(
        path: "/api/categorieMesure/",
        summary: "Lister toutes les catégories de mesure",
        description: "Retourne la liste paginée de toutes les catégories de mesure disponibles dans le système",
        tags: ['categorieMesure']
    )]
    #[OA\Response(
        response: 200,
        description: "Liste des catégories de mesure récupérée avec succès",
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(
                type: "object",
                properties: [
                    new OA\Property(property: "id", type: "integer", example: 1, description: "Identifiant unique de la catégorie"),
                    new OA\Property(property: "libelle", type: "string", example: "Mesures corporelles", description: "Nom de la catégorie de mesure"),
                    new OA\Property(property: "code", type: "string", example: "CORP", description: "Code unique de la catégorie"),
                    new OA\Property(property: "entreprise", type: "object", description: "Entreprise associée", nullable: true),
                    new OA\Property(property: "createdAt", type: "string", format: "date-time", example: "2025-01-15T10:30:00+00:00", description: "Date de création"),
                    new OA\Property(property: "updatedAt", type: "string", format: "date-time", example: "2025-01-20T14:20:00+00:00", description: "Date de mise à jour")
                ]
            )
        )
    )]
    #[OA\Response(response: 500, description: "Erreur serveur lors de la récupération")]
    public function index(CategorieMesureRepository $moduleRepository): Response
    {
        try {
            $categories = $this->paginationService->paginate($moduleRepository->findAll());
            $response = $this->responseData($categories, 'group1', ['Content-Type' => 'application/json'], true);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            $this->setMessage("Erreur lors de la récupération des catégories de mesure");
            $response = $this->response([]);
        }

        return $response;
    }

    /**
     * Liste les catégories de mesure d'une entreprise spécifique
     */
    #[Route('/entreprise', methods: ['GET'])]
    #[OA\Get(
        path: "/api/categorieMesure/entreprise",
        summary: "Lister les catégories de mesure d'une entreprise",
        description: "Retourne la liste paginée des catégories de mesure de l'entreprise de l'utilisateur authentifié. Nécessite un abonnement actif.",
        tags: ['categorieMesure']
    )]
    #[OA\Response(
        response: 200,
        description: "Liste des catégories de mesure de l'entreprise récupérée avec succès",
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(
                type: "object",
                properties: [
                    new OA\Property(property: "id", type: "integer", example: 1, description: "Identifiant de la catégorie"),
                    new OA\Property(property: "libelle", type: "string", example: "Mesures corporelles", description: "Nom de la catégorie"),
                    new OA\Property(property: "code", type: "string", example: "CORP", description: "Code unique"),
                    new OA\Property(
                        property: "entreprise",
                        type: "object",
                        description: "Informations de l'entreprise",
                        properties: [
                            new OA\Property(property: "id", type: "integer", example: 5),
                            new OA\Property(property: "nom", type: "string", example: "Mon Entreprise SARL")
                        ]
                    ),
                    new OA\Property(property: "createdAt", type: "string", format: "date-time"),
                    new OA\Property(property: "updatedAt", type: "string", format: "date-time")
                ]
            )
        )
    )]
    #[OA\Response(response: 401, description: "Non authentifié")]
    #[OA\Response(response: 403, description: "Abonnement requis pour cette fonctionnalité")]
    #[OA\Response(response: 500, description: "Erreur lors de la récupération")]
    public function indexAll(CategorieMesureRepository $moduleRepository): Response
    {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        try {
            $typeMesures = $this->paginationService->paginate(
                $moduleRepository->findBy(
                    ['setIsActive' => true],
                    ['id' => 'ASC']
                )
            );

            $response = $this->responseData($typeMesures, 'group1', ['Content-Type' => 'application/json']);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            $this->setMessage("Erreur lors de la récupération des catégories de mesure de l'entreprise");
            $response = $this->response([]);
        }

        return $response;
    }

    /**
     * Récupère les détails d'une catégorie de mesure spécifique
     */
    #[Route('/get/one/{id}', methods: ['GET'])]
    #[OA\Get(
        path: "/api/categorieMesure/get/one/{id}",
        summary: "Détails d'une catégorie de mesure",
        description: "Affiche les informations détaillées d'une catégorie de mesure spécifique par son identifiant. Nécessite un abonnement actif.",
        tags: ['categorieMesure']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: "Identifiant unique de la catégorie de mesure",
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: "Catégorie de mesure trouvée avec succès",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "id", type: "integer", example: 1, description: "Identifiant de la catégorie"),
                new OA\Property(property: "libelle", type: "string", example: "Mesures corporelles", description: "Nom de la catégorie"),
                new OA\Property(property: "code", type: "string", example: "CORP", description: "Code unique"),
                new OA\Property(property: "entreprise", type: "object", description: "Entreprise associée"),
                new OA\Property(property: "createdAt", type: "string", format: "date-time", description: "Date de création"),
                new OA\Property(property: "updatedAt", type: "string", format: "date-time", description: "Date de mise à jour"),
                new OA\Property(property: "createdBy", type: "object", description: "Utilisateur créateur"),
                new OA\Property(property: "updatedBy", type: "object", description: "Dernier utilisateur modificateur")
            ]
        )
    )]
    #[OA\Response(response: 401, description: "Non authentifié")]
    #[OA\Response(response: 403, description: "Abonnement requis pour cette fonctionnalité")]
    #[OA\Response(response: 404, description: "Catégorie de mesure non trouvée")]
    #[OA\Response(response: 500, description: "Erreur lors de la récupération")]
    public function getOne(?CategorieMesure $categorieMesure): Response
    {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        try {
            if ($categorieMesure) {
                $response = $this->response($categorieMesure);
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
     * Crée une nouvelle catégorie de mesure
     */
    #[Route('/create', methods: ['POST'])]
    #[OA\Post(
        path: "/api/categorieMesure/create",
        summary: "Créer une nouvelle catégorie de mesure",
        description: "Permet de créer une nouvelle catégorie de mesure pour l'entreprise. Nécessite un abonnement actif.",
        tags: ['categorieMesure']
    )]
    #[OA\RequestBody(
        required: true,
        description: "Données de la nouvelle catégorie de mesure à créer",
        content: new OA\JsonContent(
            type: "object",
            required: ["libelle"],
            properties: [
                new OA\Property(
                    property: "libelle",
                    type: "string",
                    example: "Mesures corporelles",
                    description: "Nom de la catégorie de mesure (obligatoire)"
                ),
                new OA\Property(
                    property: "code",
                    type: "string",
                    example: "CORP",
                    description: "Code unique de la catégorie (optionnel, peut être généré automatiquement)"
                )
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: "Catégorie de mesure créée avec succès",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "id", type: "integer", example: 5, description: "ID de la catégorie créée"),
                new OA\Property(property: "libelle", type: "string", example: "Mesures corporelles"),
                new OA\Property(property: "code", type: "string", example: "CORP"),
                new OA\Property(property: "entreprise", type: "object"),
                new OA\Property(property: "createdAt", type: "string", format: "date-time"),
                new OA\Property(property: "createdBy", type: "object", description: "Utilisateur créateur")
            ]
        )
    )]
    #[OA\Response(response: 400, description: "Données invalides")]
    #[OA\Response(response: 401, description: "Non authentifié")]
    #[OA\Response(response: 403, description: "Abonnement requis pour cette fonctionnalité")]
    public function create(Request $request, CategorieMesureRepository $moduleRepository): Response
    {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        $data = json_decode($request->getContent(), true);
        $categorieMesure = new CategorieMesure();
        $categorieMesure->setLibelle($data['libelle']);
        $categorieMesure->setEntreprise($this->getUser()->getEntreprise());
        $categorieMesure->setCreatedBy($this->getUser());
        $categorieMesure->setUpdatedBy($this->getUser());
        $categorieMesure->setIsActive(true);
        $categorieMesure->setCreatedAtValue(new \DateTime());
        $categorieMesure->setUpdatedAt(new \DateTime());

        $errorResponse = $this->errorResponse($categorieMesure);
        if ($errorResponse !== null) {
            return $errorResponse;
        } else {
            $moduleRepository->add($categorieMesure, true);
        }

        return $this->responseData($categorieMesure, 'group1', ['Content-Type' => 'application/json']);
    }

    /**
     * Met à jour une catégorie de mesure existante
     */
    #[Route('/update/{id}', methods: ['PUT', 'POST'])]
    #[OA\Put(
        path: "/api/categorieMesure/update/{id}",
        summary: "Mettre à jour une catégorie de mesure",
        description: "Permet de mettre à jour les informations d'une catégorie de mesure existante. Nécessite un abonnement actif.",
        tags: ['categorieMesure']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: "Identifiant unique de la catégorie de mesure à mettre à jour",
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\RequestBody(
        required: true,
        description: "Données à mettre à jour",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(
                    property: "libelle",
                    type: "string",
                    example: "Mesures corporelles (mis à jour)",
                    description: "Nouveau nom de la catégorie"
                ),
                new OA\Property(
                    property: "code",
                    type: "string",
                    example: "CORP_V2",
                    description: "Nouveau code de la catégorie"
                )
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: "Catégorie de mesure mise à jour avec succès",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "id", type: "integer", example: 1),
                new OA\Property(property: "libelle", type: "string", example: "Mesures corporelles (mis à jour)"),
            
            ]
        )
    )]
    #[OA\Response(response: 400, description: "Données invalides")]
    #[OA\Response(response: 401, description: "Non authentifié")]
    #[OA\Response(response: 403, description: "Abonnement requis pour cette fonctionnalité")]
    #[OA\Response(response: 404, description: "Catégorie de mesure non trouvée")]
    public function update(Request $request, CategorieMesure $categorieMesure, CategorieMesureRepository $moduleRepository): Response
    {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        try {
            $data = json_decode($request->getContent());
            if ($categorieMesure != null) {
                $categorieMesure->setLibelle($data->libelle);

                $categorieMesure->setUpdatedBy($this->getUser());
                $categorieMesure->setUpdatedAt(new \DateTime());

                $errorResponse = $this->errorResponse($categorieMesure);
                if ($errorResponse !== null) {
                    return $errorResponse;
                } else {
                    $moduleRepository->add($categorieMesure, true);
                }

                $response = $this->responseData($categorieMesure, 'group1', ['Content-Type' => 'application/json']);
            } else {
                $this->setMessage("Cette ressource est inexistante");
                $this->setStatusCode(404);
                $response = $this->response([]);
            }
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            $this->setMessage("Erreur lors de la mise à jour de la catégorie de mesure");
            $response = $this->response([]);
        }
        return $response;
    }

    /**
     * Supprime une catégorie de mesure
     */
    #[Route('/delete/{id}', methods: ['DELETE'])]
    #[OA\Delete(
        path: "/api/categorieMesure/delete/{id}",
        summary: "Supprimer une catégorie de mesure",
        description: "Permet de supprimer définitivement une catégorie de mesure par son identifiant. Nécessite un abonnement actif.",
        tags: ['categorieMesure']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: "Identifiant unique de la catégorie de mesure à supprimer",
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: "Catégorie de mesure supprimée avec succès",
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
    #[OA\Response(response: 404, description: "Catégorie de mesure non trouvée")]
    #[OA\Response(response: 500, description: "Erreur lors de la suppression")]
    public function delete(Request $request, CategorieMesure $categorieMesure, CategorieMesureRepository $villeRepository): Response
    {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        try {
            if ($categorieMesure != null) {
                $categorieMesure->setIsActive(false);
                $categorieMesure->setUpdatedBy($this->getUser());
                $categorieMesure->setUpdatedAt(new \DateTime());
                $villeRepository->add($categorieMesure, true);
                $this->setMessage("Operation effectuées avec succès");
                $response = $this->response($categorieMesure);
            } else {
                $this->setMessage("Cette ressource est inexistante");
                $this->setStatusCode(404);
                $response = $this->response([]);
            }
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            $this->setMessage("Erreur lors de la suppression de la catégorie de mesure");
            $response = $this->response([]);
        }
        return $response;
    }

    /**
     * Supprime plusieurs catégories de mesure en masse
     */
    #[Route('/delete/all/items', methods: ['DELETE'])]
    #[OA\Delete(
        path: "/api/categorieMesure/delete/all/items",
        summary: "Supprimer plusieurs catégories de mesure",
        description: "Permet de supprimer plusieurs catégories de mesure en une seule opération en fournissant un tableau d'identifiants. Nécessite un abonnement actif.",
        tags: ['categorieMesure']
    )]
    #[OA\RequestBody(
        required: true,
        description: "Tableau des identifiants des catégories de mesure à supprimer",
        content: new OA\JsonContent(
            type: "object",
            required: ["ids"],
            properties: [
                new OA\Property(
                    property: 'ids',
                    type: 'array',
                    description: "Liste des identifiants des catégories de mesure à supprimer",
                    items: new OA\Items(type: 'integer', example: 1),
                    example: [1, 2, 3, 5, 8]
                )
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: "Catégories de mesure supprimées avec succès",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "message", type: "string", example: "Operation effectuées avec succès"),
                new OA\Property(property: "deletedCount", type: "integer", example: 5, description: "Nombre de catégories supprimées")
            ]
        )
    )]
    #[OA\Response(response: 400, description: "Données invalides")]
    #[OA\Response(response: 401, description: "Non authentifié")]
    #[OA\Response(response: 403, description: "Abonnement requis pour cette fonctionnalité")]
    #[OA\Response(response: 500, description: "Erreur lors de la suppression")]
    public function deleteAll(Request $request, CategorieMesureRepository $villeRepository): Response
    {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        try {
            $data = json_decode($request->getContent(), true);

            foreach ($data['ids'] as $id) {
                $categorieMesure = $villeRepository->find($id);

                if ($categorieMesure != null) {
                    $villeRepository->remove($categorieMesure);
                }
            }
            $this->setMessage("Operation effectuées avec succès");
            $response = $this->response([]);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            $this->setMessage("Erreur lors de la suppression des catégories de mesure");
            $response = $this->response([]);
        }
        return $response;
    }
}
