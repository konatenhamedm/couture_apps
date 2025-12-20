<?php

namespace App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use App\DTO\PaysDTO;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Entity\CategorieTypeMesure;
use App\Repository\CategorieMesureRepository;
use App\Repository\CategorieTypeMesureRepository;
use App\Repository\PaysRepository;
use App\Repository\TypeMesureRepository;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Attribute\Model as Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

/**
 * Contrôleur pour la gestion des categorieTypeMesure
 * Permet de gérer les categorieTypeMesure avec leurs indicatifs téléphoniques et leurs opérateurs associés
 */
#[Route('/api/categorieTypeMesure')]
#[OA\Tag(name: 'categorieTypeMesure', description: 'Gestion des categorieTypeMesure avec indicatifs téléphoniques et opérateurs mobiles')]
class ApiCategorieTypeMesureController extends ApiInterface
{
    
 /**
     * Liste toutes les catégories de mesure du système
     */
    #[Route('/{typeMesure}', methods: ['GET'])]
    #[OA\Get(
        path: "/api/categorieTypeMesure/{typeMesure}",
        summary: "Lister toutes les catégories mesure d'un type de mesure",
        description: "",
        tags: ['categorieTypeMesure']
    )]
    #[OA\Response(
        response: 200,
        description: "Liste des catégories de mesure récupérée avec succès",
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(
                type: "object",
                properties: [
                    
                ]
            )
        )
    )]
    #[OA\Response(response: 500, description: "Erreur serveur lors de la récupération")]
    public function index($typeMesure,CategorieTypeMesureRepository $categorieTypeMesureRepository): Response
    {
        try {
            
            $categories = $this->paginationService->paginate($categorieTypeMesureRepository->findByInEnvironment(['typeMesure' => $typeMesure,'entreprise'=> $this->getManagedEntreprise() ,'isActive' => true],['id' => 'ASC']));
            $response = $this->responseData($categories, 'group1', ['Content-Type' => 'application/json'], true);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            $this->setMessage("Erreur lors de la récupération des catégories de mesure");
            $response = $this->response([]);
        }

        return $response;
    }

    /**
     * Crée un nouveau categorieTypeMesure
     */
    #[Route('/create', methods: ['POST'])]
    #[OA\Post(
        path: "/api/categorieTypeMesure/create",
        summary: "Créer un nouveau categorieTypeMesure",
        description: "Permet de créer un nouveau categorieTypeMesure avec son nom, son code ISO et son indicatif téléphonique. Le categorieTypeMesure sera créé avec le statut actif par défaut.",
        tags: ['categorieTypeMesure']
    )]
    #[OA\RequestBody(
        required: true,
        description: "Données du categorieTypeMesure à créer",
        content: new OA\JsonContent(
            type: "object",
            required: ["typeMesure", "categorieMesure"],
            properties: [
                new OA\Property(
                    property: "typeMesure",
                    type: "integer",
                    example: "1",
                  
                ),
                new OA\Property(
                    property: 'categorieMesures',
                    type: 'array',
                    description: "Liste des identifiants des catégories de mesure à supprimer",
                    items: new OA\Items(type: 'integer', example: 1),
                    example: [1, 2, 3, 5, 8]
                )
            
                
            ],
            
        )
    )]
    #[OA\Response(
        response: 201,
        description: "CategorieTypeMesure créé avec succès",
        content: new OA\JsonContent(
            type: "object",
            properties: [
               
                new OA\Property(property: "typeMesure", type: "integer", example: "1"),
                new OA\Property(property: "categorieMesure", type: "string", example: "SN"),
               
            ]
        )
    )]
    #[OA\Response(response: 400, description: "Données invalides ou code categorieTypeMesure déjà existant")]
    #[OA\Response(response: 401, description: "Non authentifié")]
    public function create(Request $request, CategorieTypeMesureRepository $categorieTypeMesureRepository,TypeMesureRepository $typeMesureRepository,CategorieMesureRepository $categorieMesureRepository): Response
    {
        $data = json_decode($request->getContent(), true);

        foreach ($data['categorieMesures'] as $categorieMesure) {
            $categorieTypeMesure = new CategorieTypeMesure();
            $categorieTypeMesure->setEntreprise($this->getManagedEntreprise());
            // Récupérer l'entité et s'assurer qu'elle est gérée
            $foundTypeMesure = $typeMesureRepository->findInEnvironment($data['typeMesure']);
            if ($foundTypeMesure) {
                $managedTypeMesure = $this->getManagedEntityFromEnvironment($foundTypeMesure);
                $categorieTypeMesure->setTypeMesure($managedTypeMesure);
            };
            // Récupérer l'entité et s'assurer qu'elle est gérée
            $foundCategorieMesure = $categorieMesureRepository->findInEnvironment($categorieMesure);
            if ($foundCategorieMesure) {
                $managedCategorieMesure = $this->getManagedEntityFromEnvironment($foundCategorieMesure);
                $categorieTypeMesure->setCategorieMesure($managedCategorieMesure);
            };
            $categorieTypeMesure->setCreatedBy($this->getManagedUser());
            $categorieTypeMesure->setUpdatedBy($this->getManagedUser());
            $categorieTypeMesure->setIsActive(true);
            $categorieTypeMesure->setCreatedAtValue();
            $categorieTypeMesure->setUpdatedAt();
            $categorieTypeMesureRepository->add($categorieTypeMesure, true);
        }


        $categories = $categorieTypeMesureRepository->findByInEnvironment(['typeMesure' => $data['typeMesure'],'isActive' => true],['id' => 'ASC']);
        
    
        return $this->responseData($categories, 'group1', ['Content-Type' => 'application/json']);
    }



    /**
     * Met à jour un categorieTypeMesure existant
     */
    #[Route('/update/{id}', methods: ['PUT', 'POST'])]
    #[OA\Put(
        path: "/api/categorieTypeMesure/update/{id}",
        summary: "Mettre à jour un categorieTypeMesure",
        description: "Permet de mettre à jour les informations d'un categorieTypeMesure, incluant son nom, son code ISO, son indicatif téléphonique et son statut actif/inactif.",
        tags: ['categorieTypeMesure']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: "Identifiant unique du categorieTypeMesure à mettre à jour",
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\RequestBody(
        required: true,
        description: "Nouvelles données du categorieTypeMesure",
        content: new OA\JsonContent(
            type: "object",
            required: ["libelle", "code", "indicatif", "actif"],
            properties: [
                   new OA\Property(
                    property: "categorieMesure",
                    type: "string",
                    example: "SN",
                  
                ),
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: "CategorieTypeMesure mis à jour avec succès",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                  new OA\Property(
                    property: "categorieMesure",
                    type: "string",
                    example: "SN",
                  
                ),
            ]
        )
    )]
    #[OA\Response(response: 400, description: "Données invalides")]
    #[OA\Response(response: 401, description: "Non authentifié")]
    #[OA\Response(response: 404, description: "CategorieTypeMesure non trouvé")]
    public function update(Request $request, int $id, CategorieTypeMesureRepository $categorieTypeMesureRepository, CategorieMesureRepository $categorieMesureRepository ): Response
    {
        try {
            $categorieTypeMesure = $categorieTypeMesureRepository->findInEnvironment($id);
            $data = json_decode($request->getContent(), true);

            if ($categorieTypeMesure != null) {
                if (isset($data['categorieMesure'])) {
                    // Récupérer l'entité et s'assurer qu'elle est gérée
            $foundCategorieMesure = $categorieMesureRepository->findInEnvironment($data['categorieMesure']);
            if ($foundCategorieMesure) {
                $managedCategorieMesure = $this->getManagedEntityFromEnvironment($foundCategorieMesure);
                $categorieTypeMesure->setCategorieMesure($managedCategorieMesure);
            };
                }
                

                $categorieTypeMesure->setUpdatedBy($this->getManagedUser());
                $categorieTypeMesure->setUpdatedAt();

                $errorResponse = $this->errorResponse($categorieTypeMesure);
                if ($errorResponse !== null) {
                    return $errorResponse;
                } else {
                    $categorieTypeMesureRepository->add($categorieTypeMesure, true);
                }

                $response = $this->responseData($categorieTypeMesure, 'group1', ['Content-Type' => 'application/json']);
            } else {
                $this->setMessage("Cette ressource est inexistante");
                $this->setStatusCode(404);
                $response = $this->response([]);
            }
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            $this->setMessage("Erreur lors de la mise à jour du categorieTypeMesure");
            $response = $this->response([]);
        }
        return $response;
    }

    /**
     * Supprime un categorieTypeMesure
     */
    #[Route('/delete/{id}', methods: ['DELETE'])]
    #[OA\Delete(
        path: "/api/categorieTypeMesure/delete/{id}",
        summary: "Supprimer un categorieTypeMesure",
        description: "Permet de supprimer définitivement un categorieTypeMesure par son identifiant. Attention : cette action supprime également tous les opérateurs téléphoniques associés à ce categorieTypeMesure.",
        tags: ['categorieTypeMesure']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: "Identifiant unique du categorieTypeMesure à supprimer",
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: "CategorieTypeMesure supprimé avec succès",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "message", type: "string", example: "Operation effectuées avec succès"),
                new OA\Property(property: "deleted", type: "boolean", example: true)
            ]
        )
    )]
    #[OA\Response(response: 401, description: "Non authentifié")]
    #[OA\Response(response: 404, description: "CategorieTypeMesure non trouvé")]
    #[OA\Response(response: 500, description: "Erreur lors de la suppression (peut-être des dépendances)")]
    public function delete(Request $request, int $id, CategorieTypeMesureRepository $categorieTypeMesureRepository): Response
    {
        try {
            $categorieTypeMesure = $categorieTypeMesureRepository->findInEnvironment($id);
            if ($categorieTypeMesure != null) {
                $categorieTypeMesure->setIsActive(false);
                $categorieTypeMesure->setUpdatedBy($this->getManagedUser());
                $categorieTypeMesure->setUpdatedAt();
                $categorieTypeMesureRepository->add($categorieTypeMesure, true);
                $this->setMessage("Operation effectuées avec succès");
                $response = $this->response($categorieTypeMesure);
            } else {
                $this->setMessage("Cette ressource est inexistante");
                $this->setStatusCode(404);
                $response = $this->response([]);
            }
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            $this->setMessage("Erreur lors de la suppression du categorieTypeMesure");
            $response = $this->response([]);
        }
        return $response;
    }

    /**
     * Supprime plusieurs categorieTypeMesure en masse
     */
    #[Route('/delete/all/items', methods: ['DELETE'])]
    #[OA\Delete(
        path: "/api/categorieTypeMesure/delete/all/items/items",
        summary: "Supprimer plusieurs categorieTypeMesure",
        description: "Permet de supprimer plusieurs categorieTypeMesure en une seule opération en fournissant un tableau d'identifiants. Attention : tous les opérateurs téléphoniques associés seront également supprimés.",
        tags: ['categorieTypeMesure']
    )]
    #[OA\RequestBody(
        required: true,
        description: "Tableau des identifiants des categorieTypeMesure à supprimer",
        content: new OA\JsonContent(
            type: "object",
            required: ["ids"],
            properties: [
                new OA\Property(
                    property: 'ids',
                    type: 'array',
                    description: "Liste des identifiants des categorieTypeMesure à supprimer",
                    items: new OA\Items(type: 'integer', example: 1),
                    example: [1, 2, 3, 5, 8]
                )
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: "CategorieTypeMesure supprimés avec succès",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "message", type: "string", example: "Operation effectuées avec succès"),
                new OA\Property(property: "deletedCount", type: "integer", example: 5, description: "Nombre de categorieTypeMesure supprimés")
            ]
        )
    )]
    #[OA\Response(response: 400, description: "Données invalides")]
    #[OA\Response(response: 401, description: "Non authentifié")]
    #[OA\Response(response: 500, description: "Erreur lors de la suppression")]
    public function deleteAll(Request $request, CategorieTypeMesureRepository $categorieTypeMesureRepository): Response
    {
       // dd($request->getContent());
        try {
            $data = json_decode($request->getContent(), true);

            $count = 0;
            foreach ($data['ids'] as $id) {
                $categorieTypeMesure = $categorieTypeMesureRepository->findInEnvironment($id);

                if ($categorieTypeMesure != null) {
                    $categorieTypeMesureRepository->remove($categorieTypeMesure);
                    $count++;
                }
            }
            $this->setMessage("Operation effectuées avec succès");
            $response = $this->json(['message' => 'Operation effectuées avec succès', 'deletedCount' => $count]);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            $this->setMessage("Erreur lors de la suppression des categorieTypeMesure");
            $response = $this->response([]);
        }
        return $response;
    }
}