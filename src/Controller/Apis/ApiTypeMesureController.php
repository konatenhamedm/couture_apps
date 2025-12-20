<?php

namespace  App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use App\Entity\CategorieMesure;
use App\Entity\CategorieTypeMesure;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Entity\TypeMesure;
use App\Repository\CategorieMesureRepository;
use App\Repository\CategorieTypeMesureRepository;
use App\Repository\TypeMesureRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Attribute\Model as AttributeModel;
use Symfony\Component\HttpFoundation\Request;

#[Route('/api/typeMesure')]
class ApiTypeMesureController extends ApiInterface
{



    #[Route('/', methods: ['GET'])]
    /**
     * Retourne la liste des typeMesures.
     * 
     */
    #[OA\Response(
        response: 200,
        description: 'Returns the rewards of an user',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new AttributeModel(type: TypeMesure::class, groups: ['full']))
        )
    )]
    #[OA\Tag(name: 'typeMesure')]
    // #[Security(name: 'Bearer')]
    public function index(TypeMesureRepository $typeMesureRepository): Response
    {
        try {

            $typeMesures = $this->paginationService->paginate($typeMesureRepository->findAll());

            $response =  $this->responseData($typeMesures, 'group1', ['Content-Type' => 'application/json']);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            $this->setMessage("");
            $response = $this->response([]);
        }

        // On envoie la réponse
        return $response;
    }

    #[Route('/entreprise', methods: ['GET'])]
    /**
     * Retourne la liste des typeMesures d'une entreprise.
     * 
     */
    #[OA\Response(
        response: 200,
        description: 'Returns the rewards of an user',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new AttributeModel(type: TypeMesure::class, groups: ['full']))
        )
    )]
    #[OA\Tag(name: 'typeMesure')]
    // #[Security(name: 'Bearer')]
    public function indexAll(TypeMesureRepository $typeMesureRepository, CategorieTypeMesureRepository $categorieTypeMesureRepository): Response
    {
        /*  if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }  */

        try {
            $typeMesures = $typeMesureRepository->findBy([], ['id' => 'ASC']);


            $formattedTypeMesures = array_map(function ($typeMesure) use ($categorieTypeMesureRepository) {

                $categorieTypeMesures = $categorieTypeMesureRepository->findBy(['typeMesure' => $typeMesure, 'entreprise' => $this->getUser()->getEntreprise()]);

                return [
                    'id' => $typeMesure->getId(),
                    'libelle' => $typeMesure->getLibelle(),
                    'categories' => array_map(function ($categorieTypeMesure) {
                        return [
                            'id' => $categorieTypeMesure->getId(),
                            'idCategorie' => $categorieTypeMesure->getCategorieMesure()->getId(),
                            'libelleCategorie' => $categorieTypeMesure->getCategorieMesure()->getLibelle(),
                        ];
                    }, $categorieTypeMesures),
                ];
            }, $typeMesures);

            //  $typeMesures = $this->paginationService->paginate($typeMesures);





            $response =  $this->responseData($formattedTypeMesures, 'group1', ['Content-Type' => 'application/json']);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            $this->setMessage("");
            $response = $this->response([]);
        }

        // On envoie la réponse
        return $response;
    }

    #[Route('/categorie/by/type/{typeMesure}', methods: ['GET'])]
    /**
     * Retourne la liste des categorieMesure d'un type.
     * 
     */
    #[OA\Response(
        response: 200,
        description: 'Returns the rewards of an user',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new AttributeModel(type: TypeMesure::class, groups: ['full']))
        )
    )]
    #[OA\Tag(name: 'typeMesure')]
    // #[Security(name: 'Bearer')]
    public function indexAllCategorieByTypeMessure(TypeMesureRepository $typeMesureRepository, CategorieTypeMesureRepository $categorieTypeMesureRepository, $typeMesure): Response
    {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        try {

            $categories = $this->paginationService->paginate($categorieTypeMesureRepository->findBy(
                ['typeMesure' => $typeMesure],
                ['id' => 'ASC']
            ));



            $response =  $this->responseData($categories, 'group_type', ['Content-Type' => 'application/json']);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            $this->setMessage("");
            $response = $this->response([]);
        }

        // On envoie la réponse
        return $response;
    }


    #[Route('/get/one/{id}', methods: ['GET'])]
    /**
     * Affiche un(e) typeMesure en offrant un identifiant.
     */
    #[OA\Response(
        response: 200,
        description: 'Affiche un(e) typeMesure en offrant un identifiant',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new AttributeModel(type: TypeMesure::class, groups: ['full']))
        )
    )]
    #[OA\Parameter(
        name: 'code',
        in: 'query',
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Tag(name: 'typeMesure')]
    //#[Security(name: 'Bearer')]
    public function getOne(?TypeMesure $typeMesure)
    {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        try {
            if ($typeMesure) {
                $response = $this->response($typeMesure);
            } else {
                $this->setMessage('Cette ressource est inexistante');
                $this->setStatusCode(300);
                $response = $this->response($typeMesure);
            }
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            $this->setMessage($exception->getMessage());
            $response = $this->response([]);
        }


        return $response;
    }


    #[Route('/create',  methods: ['POST'])]
    /**
     * Permet de créer un(e) typeMesure avec ses lignes.
     */
    #[OA\Post(
        summary: "Permet de créer un(e) typeMesure avec ses lignes",
        description: "Permet de créer un(e) typeMesure avec ses lignes.",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "libelle", type: "string"),


                    new OA\Property(
                        property: "lignes",
                        type: "array",
                        items: new OA\Items(
                            type: "object",
                            properties: [
                                new OA\Property(property: "categorieId", type: "string"),


                            ]
                        ),
                    ),
                ],
                type: "object"
            )
        ),
        responses: [
            new OA\Response(response: 401, description: "Invalid credentials")
        ]
    )]
    #[OA\Tag(name: 'typeMesure')]
    public function create(Request $request, CategorieMesureRepository $categorieMesureRepository, TypeMesureRepository $typeMesureRepository, EntrepriseRepository $entrepriseRepository): Response
    {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        $data = json_decode($request->getContent(), true);
        $typeMesure = new TypeMesure();
        $typeMesure->setLibelle($data['libelle']);
        $typeMesure->setEntreprise($this->getUser()->getEntreprise());
        $typeMesure->setCreatedBy($this->getUser());
        $typeMesure->setIsActive(true);
        $typeMesure->setUpdatedBy($this->getUser());
        $errorResponse = $this->errorResponse($typeMesure);
        // On vérifie si l'entreprise existe
        $lignesCategoriesMesure = $data['lignes'];

        if (isset($lignesCategoriesMesure) && is_array($lignesCategoriesMesure)) {
            foreach ($lignesCategoriesMesure as $ligneCategorieMesure) {
                $categorieMesure = new CategorieTypeMesure();
                $categorieMesure->setCategorieMesure($categorieMesureRepository->find($ligneCategorieMesure['categorieId']));
                $errorResponse = $this->errorResponse($categorieMesure);

                $typeMesure->addCategorieTypeMesure($categorieMesure);
            }
        }


        if ($errorResponse !== null) {
            return $errorResponse;
        } else {

            $typeMesureRepository->add($typeMesure, true);
        }

        return $this->responseData($typeMesure, 'group1', ['Content-Type' => 'application/json']);
    }


    #[Route('/update/{id}', methods: ['PUT', 'POST'])]
    #[OA\Post(
        summary: "Permet de mettre a jour un typeMesure.",
        description: "Permet de mettre a jour un typeMesure.",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "libelle", type: "string"),
                    new OA\Property(
                        property: "categories",
                        type: "array",
                        items: new OA\Items(
                            type: "object",
                            properties: [
                                new OA\Property(property: "idCategorie", type: "integer"),
                            ]
                        ),
                    ),
                ],
                type: "object"
            )
        ),
        responses: [
            new OA\Response(response: 401, description: "Invalid credentials")
        ]
    )]
    #[OA\Tag(name: 'typeMesure')]
    public function update(
        Request $request, 
        TypeMesure $typeMesure, 
        TypeMesureRepository $typeMesureRepository,
        CategorieMesureRepository $categorieMesureRepository,
        CategorieTypeMesureRepository $categorieTypeMesureRepository
    ): Response
    {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        try {
            // Parse JSON payload with error handling
            $jsonContent = $request->getContent();
            if (empty($jsonContent)) {
                return new JsonResponse([
                    'code' => 400,
                    'message' => 'Empty request body',
                    'errors' => ['Request body cannot be empty']
                ], 400);
            }

            $data = json_decode($jsonContent, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return new JsonResponse([
                    'code' => 400,
                    'message' => 'Malformed JSON',
                    'errors' => ['Invalid JSON format: ' . json_last_error_msg()]
                ], 400);
            }

            if ($typeMesure == null) {
                return new JsonResponse([
                    'code' => 404,
                    'message' => 'TypeMesure not found',
                    'errors' => ['TypeMesure with this ID does not exist']
                ], 404);
            }

            // Start transaction for atomic updates
            $this->em->beginTransaction();

            try {
                // Update libelle if provided
                if (isset($data['libelle'])) {
                    $typeMesure->setLibelle($data['libelle']);
                }
                
                $typeMesure->setUpdatedBy($this->getUser());
                $typeMesure->setUpdatedAt(new \DateTime());

                // Validate TypeMesure entity
                $errorResponse = $this->errorResponse($typeMesure);
                if ($errorResponse !== null) {
                    $this->em->rollback();
                    return $errorResponse;
                }

                // Handle categories if provided
                if (isset($data['categories']) && is_array($data['categories'])) {
                    // Validate all category IDs exist before making changes
                    $categoryIds = [];
                    foreach ($data['categories'] as $category) {
                        if (!isset($category['idCategorie'])) {
                            $this->em->rollback();
                            return new JsonResponse([
                                'code' => 400,
                                'message' => 'Invalid category format',
                                'errors' => ['Each category must have an idCategorie field']
                            ], 400);
                        }

                        $categoryId = $category['idCategorie'];
                        $categoryIds[] = $categoryId;

                        // Check if category exists
                        $categorieMesure = $categorieMesureRepository->find($categoryId);
                        if (!$categorieMesure) {
                            $this->em->rollback();
                            return new JsonResponse([
                                'code' => 400,
                                'message' => 'Category not found',
                                'errors' => ["Category {$categoryId} not found"]
                            ], 400);
                        }
                    }

                    // Remove all existing category associations
                    $existingAssociations = $categorieTypeMesureRepository->findBy([
                        'typeMesure' => $typeMesure,
                        'entreprise' => $this->getUser()->getEntreprise()
                    ]);

                    foreach ($existingAssociations as $association) {
                        $typeMesure->removeCategorieTypeMesure($association);
                        $this->em->remove($association);
                    }

                    // Create new category associations
                    foreach ($categoryIds as $categoryId) {
                        $categorieMesure = $categorieMesureRepository->find($categoryId);
                        
                        $categorieTypeMesure = new CategorieTypeMesure();
                        $categorieTypeMesure->setTypeMesure($typeMesure);
                        $categorieTypeMesure->setCategorieMesure($categorieMesure);
                        $categorieTypeMesure->setEntreprise($this->getUser()->getEntreprise());
                        $categorieTypeMesure->setCreatedBy($this->getUser());
                        $categorieTypeMesure->setUpdatedBy($this->getUser());

                        $typeMesure->addCategorieTypeMesure($categorieTypeMesure);
                        $this->em->persist($categorieTypeMesure);
                    }
                }

                // Persist changes
                $typeMesureRepository->add($typeMesure, true);
                
                // Commit transaction
                $this->em->commit();

                // Prepare response with formatted category details
                $categorieTypeMesures = $categorieTypeMesureRepository->findBy([
                    'typeMesure' => $typeMesure, 
                    'entreprise' => $this->getUser()->getEntreprise()
                ]);

                $formattedTypeMesure = [
                    'id' => $typeMesure->getId(),
                    'libelle' => $typeMesure->getLibelle(),
                    'categories' => array_map(function ($categorieTypeMesure) {
                        return [
                            'id' => $categorieTypeMesure->getId(),
                            'idCategorie' => $categorieTypeMesure->getCategorieMesure()->getId(),
                            'libelleCategorie' => $categorieTypeMesure->getCategorieMesure()->getLibelle(),
                        ];
                    }, $categorieTypeMesures),
                ];

                return new JsonResponse([
                    'code' => 200,
                    'message' => 'Operation effectuée avec succes',
                    'data' => $formattedTypeMesure,
                    'errors' => []
                ], 200);

            } catch (\Exception $e) {
                $this->em->rollback();
                throw $e;
            }

        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            $this->setMessage("Internal server error");
            return new JsonResponse([
                'code' => 500,
                'message' => 'Internal server error',
                'errors' => []
            ], 500);
        }
    }

    //const TAB_ID = 'parametre-tabs';

    #[Route('/delete/{id}',  methods: ['DELETE'])]
    /**
     * permet de supprimer un(e) typeMesure.
     */
    #[OA\Response(
        response: 200,
        description: 'permet de supprimer un(e) typeMesure',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new AttributeModel(type: TypeMesure::class, groups: ['full']))
        )
    )]
    #[OA\Tag(name: 'typeMesure')]
    //#[Security(name: 'Bearer')]
    public function delete(Request $request, TypeMesure $typeMesure, TypeMesureRepository $villeRepository): Response
    {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        try {

            if ($typeMesure != null) {

                $villeRepository->remove($typeMesure, true);

                // On retourne la confirmation
                $this->setMessage("Operation effectuées avec success");
                $response = $this->response($typeMesure);
            } else {
                $this->setMessage("Cette ressource est inexistante");
                $this->setStatusCode(300);
                $response = $this->response([]);
            }
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            $this->setMessage("");
            $response = $this->response([]);
        }
        return $response;
    }

    #[Route('/delete/all/items',  methods: ['DELETE'])]
    /**
     * Permet de supprimer plusieurs typeMesure.
     */
    #[OA\RequestBody(
        required: true,
        description: 'Tableau d’identifiants à supprimer',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: 'ids',
                    type: 'array',
                    items: new OA\Items(type: 'integer', example: 1)
                )
            ]
        )
    )]
    #[OA\Tag(name: 'typeMesure')]
    public function deleteAll(Request $request, TypeMesureRepository $villeRepository): Response
    {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        try {
            $data = json_decode($request->getContent());

            foreach ($data['ids'] as $id) {
                $typeMesure = $villeRepository->find($id);

                if ($typeMesure != null) {
                    $villeRepository->remove($typeMesure);
                }
            }
            $this->setMessage("Operation effectuées avec success");
            $response = $this->response([]);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            $this->setMessage("");
            $response = $this->response([]);
        }
        return $response;
    }
}
