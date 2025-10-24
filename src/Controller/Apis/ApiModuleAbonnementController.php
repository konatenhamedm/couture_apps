<?php

namespace  App\Controller\Apis;

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

#[Route('/api/moduleAbonnement')]
class ApiModuleAbonnementController extends ApiInterface
{



    #[Route('/', methods: ['GET'])]
    /**
     * Retourne la liste des moduleAbonnements.
     * 
     */
    #[OA\Response(
        response: 200,
        description: 'Returns the rewards of an user',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new AttributeModel(type: ModuleAbonnement::class, groups: ['full']))
        )
    )]
    #[OA\Tag(name: 'moduleAbonnement')]
    // #[Security(name: 'Bearer')]
    public function index(ModuleAbonnementRepository $moduleAbonnementRepository): Response
    {
        try {

            $moduleAbonnements = $this->paginationService->paginate($moduleAbonnementRepository->findAll());



            $response =  $this->responseData($moduleAbonnements, 'group1', ['Content-Type' => 'application/json']);
        } catch (\Exception $exception) {
            $this->setMessage("");
            $response = $this->response('[]');
        }

        // On envoie la réponse
        return $response;
    }




    #[Route('/get/one/{id}', methods: ['GET'])]
    /**
     * Affiche un(e) moduleAbonnement en offrant un identifiant.
     */
    #[OA\Response(
        response: 200,
        description: 'Affiche un(e) moduleAbonnement en offrant un identifiant',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new AttributeModel(type: ModuleAbonnement::class, groups: ['full']))
        )
    )]
    #[OA\Parameter(
        name: 'code',
        in: 'query',
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Tag(name: 'moduleAbonnement')]
    //#[Security(name: 'Bearer')]
    public function getOne(?ModuleAbonnement $moduleAbonnement)
    {
        try {
            if ($moduleAbonnement) {
                $response = $this->response($moduleAbonnement);
            } else {
                $this->setMessage('Cette ressource est inexistante');
                $this->setStatusCode(300);
                $response = $this->response($moduleAbonnement);
            }
        } catch (\Exception $exception) {
            $this->setMessage($exception->getMessage());
            $response = $this->response('[]');
        }


        return $response;
    }


    #[Route('/create',  methods: ['POST'])]
    /**
     * Permet de créer un(e) moduleAbonnement.
     */
    #[OA\Post(
        summary: "Authentification admin",
        description: "Génère un token JWT pour les administrateurs.",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "montant", type: "string"),
                    new OA\Property(property: "etat", type: "boolean"),
                    new OA\Property(property: "code", type: "string"),
                    new OA\Property(property: "duree", type: "boolean"),
                    new OA\Property(property: "description", type: "boolean"),
                    new OA\Property(property: "userUpdate", type: "string"),

                    new OA\Property(
                        property: "ligneModules",
                        type: "array",
                        items: new OA\Items(
                            type: "object",
                            properties: [
                                new OA\Property(property: "libelle", type: "string"),
                                new OA\Property(property: "description", type: "string"),
                                new OA\Property(property: "module", type: "string"),

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
    #[OA\Tag(name: 'moduleAbonnement')]
    public function create(Request $request, ModuleAbonnementRepository $moduleAbonnementRepository, ModuleRepository $moduleRepository): Response
    {

        $data = json_decode($request->getContent(), true);
        $moduleAbonnement = new ModuleAbonnement();
        $moduleAbonnement->setEtat($data['etat']);
        $moduleAbonnement->setCode($data['code']);
        $moduleAbonnement->setDescription($data['description']);
        $moduleAbonnement->setMontant($data['montant']);
        $moduleAbonnement->setDuree($data['duree']);
        $moduleAbonnement->setCreatedBy($this->getUser());
        $moduleAbonnement->setUpdatedBy($this->getUser());
        $errorResponse = $this->errorResponse($moduleAbonnement);

        $ligneModules = $data['ligneModules'] ?? [];
        foreach ($ligneModules as $ligneModuleData) {
            $ligneModule = new LigneModule();
            $ligneModule->setLibelle($ligneModuleData['libelle']);
            $ligneModule->setDescription($ligneModuleData['description']);
            $ligneModule->setModule($moduleRepository->find($ligneModuleData['module']));
            $moduleAbonnement->addLigneModule($ligneModule);
        }

        if ($errorResponse !== null) {
            return $errorResponse; 
        } else {

            $moduleAbonnementRepository->add($moduleAbonnement, true);
        }

        return $this->responseData($moduleAbonnement, 'group1', ['Content-Type' => 'application/json']);
    }


    #[Route('/update/{id}', methods: ['PUT', 'POST'])]
    #[OA\Post(
        summary: "Creation de moduleAbonnement",
        description: "Permet de créer un moduleAbonnement.",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "montant", type: "string"),
                    new OA\Property(property: "code", type: "string"),
                    new OA\Property(property: "etat", type: "boolean"),
                    new OA\Property(property: "duree", type: "boolean"),
                    new OA\Property(property: "description", type: "boolean"),
                    new OA\Property(property: "userUpdate", type: "string"),

                    new OA\Property(
                        property: "ligneModules",
                        type: "array",
                        items: new OA\Items(
                            type: "object",
                            properties: [
                                new OA\Property(property: "id", type: "string"),
                                new OA\Property(property: "libelle", type: "string"),
                                new OA\Property(property: "description", type: "string"),
                                new OA\Property(property: "module", type: "string"),

                            ]
                        ),
                    ),

                    new OA\Property(
                        property: "ligneModulesDelete",
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
    #[OA\Tag(name: 'moduleAbonnement')]
    public function update(Request $request, ModuleAbonnement $moduleAbonnement, LigneModuleRepository $ligneModuleRepository, ModuleAbonnementRepository $moduleAbonnementRepository, ModuleRepository $moduleRepository): Response
    {
        try {
            $data = json_decode($request->getContent());
            if ($moduleAbonnement != null) {

                $moduleAbonnement->setEtat($data->etat);
                $moduleAbonnement->setCode($data->code);
                $moduleAbonnement->setDescription($data->description);
                $moduleAbonnement->setMontant($data->montant);
                $moduleAbonnement->setDuree($data->duree);
                $moduleAbonnement->setUpdatedBy($this->getUser());
                $moduleAbonnement->setUpdatedAt(new \DateTime());
                $errorResponse = $this->errorResponse($moduleAbonnement);

                // On gère les lignes de module
                $ligneModules = $data->ligneModules ?? [];
                foreach ($ligneModules as $ligneModuleData) {

                    if (!isset($ligneModuleData->id) || $ligneModuleData->id == null) {
                        // Création d'une nouvelle ligne de module si l'ID n'est pas fourni
                        $ligneModule = new LigneModule();
                        $ligneModule->setLibelle($ligneModuleData->libelle);
                        $ligneModule->setDescription($ligneModuleData->description);
                        $ligneModule->setModule($moduleRepository->find($ligneModuleData->module));
                        $moduleAbonnement->addLigneModule($ligneModule);
                    } else {
                        $ligneModule = $ligneModuleRepository->find($ligneModuleData->id);
                        if ($ligneModule != null) {

                            $ligneModule->setLibelle($ligneModuleData->libelle);
                            $ligneModule->setDescription($ligneModuleData->description);
                            $ligneModule->setModule($moduleRepository->find($ligneModuleData->module));
                            $ligneModuleRepository->add($ligneModule, true);
                        }
                    }
                }

                // On gère les lignes de module à supprimer
                $ligneModulesDeletes = $data->ligneModulesDelete ?? [];

                if (isset($ligneModulesDeletes) && is_array($ligneModulesDeletes)) {
                    foreach ($ligneModulesDeletes as $ligneModuleData) {
                        $ligneModule = $ligneModuleRepository->find($ligneModuleData->id);
                        if ($ligneModule != null) {
                            $moduleAbonnement->removeLigneModule($ligneModule);
                            $ligneModuleRepository->remove($ligneModule, true);
                        }
                    }
                }

                if ($errorResponse !== null) {
                    return $errorResponse; 
                } else {
                    $moduleAbonnementRepository->add($moduleAbonnement, true);
                }



                // On retourne la confirmation
                $response = $this->responseData($moduleAbonnement, 'group1', ['Content-Type' => 'application/json']);
            } else {
                $this->setMessage("Cette ressource est inexsitante");
                $this->setStatusCode(300);
                $response = $this->response('[]');
            }
        } catch (\Exception $exception) {
            $this->setMessage("");
            $response = $this->response('[]');
        }
        return $response;
    }

    //const TAB_ID = 'parametre-tabs';

    #[Route('/delete/{id}',  methods: ['DELETE'])]
    /**
     * permet de supprimer un(e) moduleAbonnement.
     */
    #[OA\Response(
        response: 200,
        description: 'permet de supprimer un(e) moduleAbonnement',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new AttributeModel(type: ModuleAbonnement::class, groups: ['full']))
        )
    )]
    #[OA\Tag(name: 'moduleAbonnement')]
    //#[Security(name: 'Bearer')]
    public function delete(Request $request, ModuleAbonnement $moduleAbonnement, ModuleAbonnementRepository $villeRepository): Response
    {
        try {

            if ($moduleAbonnement != null) {

                $villeRepository->remove($moduleAbonnement, true);

                // On retourne la confirmation
                $this->setMessage("Operation effectuées avec success");
                $response = $this->response($moduleAbonnement);
            } else {
                $this->setMessage("Cette ressource est inexistante");
                $this->setStatusCode(300);
                $response = $this->response('[]');
            }
        } catch (\Exception $exception) {
            $this->setMessage("");
            $response = $this->response('[]');
        }
        return $response;
    }

    #[Route('/delete/all',  methods: ['DELETE'])]
    /**
     * Permet de supprimer plusieurs moduleAbonnement.
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
    #[OA\Tag(name: 'moduleAbonnement')]
    #[Security(name: 'Bearer')]
    public function deleteAll(Request $request, ModuleAbonnementRepository $villeRepository): Response
    {
        try {
            $data = json_decode($request->getContent());

            foreach ($data['ids'] as $id) {
                $moduleAbonnement = $villeRepository->find($id);

                if ($moduleAbonnement != null) {
                    $villeRepository->remove($moduleAbonnement);
                }
            }
            $this->setMessage("Operation effectuées avec success");
            $response = $this->response('[]');
        } catch (\Exception $exception) {
            $this->setMessage("");
            $response = $this->response('[]');
        }
        return $response;
    }
}
