<?php

namespace  App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use App\Entity\CategorieMesure;
use App\Entity\CategorieTypeMesure;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Entity\TypeMesure;
use App\Repository\CategorieMesureRepository;
use App\Repository\CategorieTypeMesureRepository;
use App\Repository\EntrepriseRepository;
use App\Repository\TypeMesureRepository;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Annotation\Model as Model;
use Nelmio\ApiDocBundle\Attribute\Security;
use Nelmio\ApiDocBundle\Attribute\Model as AttributeModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

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
            $response = $this->response('[]');
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
    public function indexAll(TypeMesureRepository $typeMesureRepository): Response
    {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        try {

            $typeMesures = $this->paginationService->paginate($typeMesureRepository->findBy(
                ['entreprise' => $this->getUser()->getEntreprise()],
                ['id' => 'ASC']
            ));

          

            $response =  $this->responseData($typeMesures, 'group1', ['Content-Type' => 'application/json']);
        } catch (\Exception $exception) {
$this->setStatusCode(500);
            $this->setMessage("");
            $response = $this->response('[]');
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
    public function indexAllCategorieByTypeMessure(TypeMesureRepository $typeMesureRepository,CategorieTypeMesureRepository $categorieTypeMesureRepository,$typeMesure): Response
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
            $response = $this->response('[]');
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
            $response = $this->response('[]');
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
    public function create(Request $request,CategorieMesureRepository $categorieMesureRepository, TypeMesureRepository $typeMesureRepository,EntrepriseRepository $entrepriseRepository): Response
    {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        $data = json_decode($request->getContent(), true);
        $typeMesure = new TypeMesure();
        $typeMesure->setLibelle($data['libelle']);
        $typeMesure->setEntreprise($this->getUser()->getEntreprise());
        $typeMesure->setCreatedBy($this->getUser());
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
                    new OA\Property(property: "entrepriseId", type: "string"),
                    new OA\Property(property: "userUpdate", type: "string"),

                    new OA\Property(
                        property: "ligneCategorieMesures",
                        type: "array",
                        items: new OA\Items(
                            type: "object",
                            properties: [
                                new OA\Property(property: "id", type: "string"), 
                                new OA\Property(property: "libelle", type: "string"), 
                               
                            ]
                        ),
                    ),
                    new OA\Property(
                        property: "ligneCategorieMesuresDelete",
                        type: "array",
                        items: new OA\Items(
                            type: "object",
                            properties: [
                                new OA\Property(property: "id", type: "string"),
                               
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
    public function update(Request $request, TypeMesure $typeMesure, TypeMesureRepository $typeMesureRepository,EntrepriseRepository $entrepriseRepository,CategorieMesureRepository $categorieMesureRepository): Response
    {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        try {
            $data = json_decode($request->getContent());
            if ($typeMesure != null) {

                $typeMesure->setLibelle($data->libelle);
                $typeMesure->setUpdatedBy($this->getUser());
                $typeMesure->setUpdatedAt(new \DateTime());
                $errorResponse = $this->errorResponse($typeMesure);

                // On vérifie si lignesCategoriesMesure n'est pas vide
                $lignesCategoriesMesure = $data->ligneCategorieMesures;
                if (isset($lignesCategoriesMesure) && is_array($lignesCategoriesMesure)) {
                    foreach ($lignesCategoriesMesure as $ligneCategorieMesure) {
                        
                        if(!isset($ligneCategorieMesure->id) || $ligneCategorieMesure->id == null){
                            $categorieMesure = new CategorieMesure();
                            $categorieMesure->setLibelle($ligneCategorieMesure->libelle);
                            $categorieMesure->setEntreprise($this->getUser()->getEntreprise());
                            $categorieMesure->setCreatedBy($this->getUser());
                            $categorieMesure->setUpdatedBy($this->getUser());
                            $categorieMesureRepository->add($categorieMesure, true);
                        }else{
                            $categorieMesure = $categorieMesureRepository->find($ligneCategorieMesure->id);
                            $categorieMesure->setLibelle($ligneCategorieMesure->libelle);
                            $categorieMesure->setCreatedBy($this->getUser());
                            $categorieMesure->setUpdatedBy($this->getUser());
                            $categorieMesureRepository->add($categorieMesure, true);

                        }
                    }
                }

                // On vérifie si les catégories mesures à supprimer existent 
                $lignesCategoriesMesureDelete = $data->ligneCategorieMesuresDelete;

                if (isset($lignesCategoriesMesureDelete) && is_array($lignesCategoriesMesureDelete)) {
                    foreach ($lignesCategoriesMesureDelete as $ligneCategorieMesure) {
                        $categorieMesure = $categorieMesureRepository->find($ligneCategorieMesure->id);
                        if ($categorieMesure != null) {
                            $typeMesure->removeCategorieTypeMesure($categorieMesure);
                            $categorieMesureRepository->remove($categorieMesure, true);
                        }
                    }
                }
                

                if ($errorResponse !== null) {
                    return $errorResponse; 
                } else {
                    $typeMesureRepository->add($typeMesure, true);
                }



                // On retourne la confirmation
                $response = $this->responseData($typeMesure, 'group1', ['Content-Type' => 'application/json']);
            } else {
                $this->setMessage("Cette ressource est inexsitante");
                $this->setStatusCode(300);
                $response = $this->response('[]');
            }
        } catch (\Exception $exception) {
$this->setStatusCode(500);
            $this->setMessage("");
            $response = $this->response('[]');
        }
        return $response;
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
                $response = $this->response('[]');
            }
        } catch (\Exception $exception) {
$this->setStatusCode(500);
            $this->setMessage("");
            $response = $this->response('[]');
        }
        return $response;
    }

    #[Route('/delete/all',  methods: ['DELETE'])]
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
            $response = $this->response('[]');
        } catch (\Exception $exception) {
$this->setStatusCode(500);
            $this->setMessage("");
            $response = $this->response('[]');
        }
        return $response;
    }
}
