<?php

namespace  App\Controller\Apis;

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

#[Route('/api/categorieMesure')]
class ApiCategorieMesureController extends ApiInterface
{

    #[Route('/', methods: ['GET'])]
    /**
     * Retourne la liste des modules.
     * 
     */
    #[OA\Response(
        response: 200,
        description: 'Returns the rewards of an user',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new AttributeModel(type: CategorieMesure::class, groups: ['full']))
        )
    )]
    #[OA\Tag(name: 'categorieMesure')]
    // #[Security(name: 'Bearer')]
    public function index(CategorieMesureRepository $moduleRepository): Response
    {
        //dd($this->getUser());
        try {

            $categories = $this->paginationService->paginate($moduleRepository->findAll());
            /* dd($categories); */


            $response =  $this->responseData($categories, 'group1', ['Content-Type' => 'application/json'], true);
        } catch (\Exception $exception) {
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
            items: new OA\Items(ref: new AttributeModel(type: CategorieMesure::class, groups: ['full']))
        )
    )]
    #[OA\Tag(name: 'typeMesure')]
    // #[Security(name: 'Bearer')]
    public function indexAll(CategorieMesureRepository $moduleRepository): Response
    {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        try {

            $typeMesures = $this->paginationService->paginate($moduleRepository->findBy(
                ['entreprise' => $this->getUser()->getEntreprise()],
                ['id' => 'ASC']
            ));



            $response =  $this->responseData($typeMesures, 'group1', ['Content-Type' => 'application/json']);
        } catch (\Exception $exception) {
            $this->setMessage("");
            $response = $this->response('[]');
        }

        // On envoie la réponse
        return $response;
    }



    #[Route('/get/one/{id}', methods: ['GET'])]
    /**
     * Affiche un(e) categorieMesure en offrant un identifiant.
     */
    #[OA\Response(
        response: 200,
        description: 'Affiche un(e) categorieMesure en offrant un identifiant',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new AttributeModel(type: CategorieMesure::class, groups: ['full']))
        )
    )]
    #[OA\Parameter(
        name: 'code',
        in: 'query',
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Tag(name: 'categorieMesure')]
    //#[Security(name: 'Bearer')]
    public function getOne(?categorieMesure $categorieMesure)
    {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        try {
            if ($categorieMesure) {
                $response = $this->response($categorieMesure);
            } else {
                $this->setMessage('Cette ressource est inexistante');
                $this->setStatusCode(300);
                $response = $this->response($categorieMesure);
            }
        } catch (\Exception $exception) {
            $this->setMessage($exception->getMessage());
            $response = $this->response('[]');
        }


        return $response;
    }


    #[Route('/create',  methods: ['POST'])]
    /**
     * Permet de créer un(e) categorieMesure.
     */
    #[OA\Post(
        summary: "Permet de créer un(e) categorieMesure.",
        description: "Permet de créer un(e) categorieMesure..",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "libelle", type: "string"),
                    new OA\Property(property: "code", type: "string"),

                ],
                type: "object"
            )
        ),
        responses: [
            new OA\Response(response: 401, description: "Invalid credentials")
        ]
    )]
    #[OA\Tag(name: 'categorieMesure')]
    public function create(Request $request, CategorieMesureRepository $moduleRepository): Response
    {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }


        $data = json_decode($request->getContent(), true);
        $categorieMesure = new CategorieMesure();
        $categorieMesure->setLibelle($data['libelle']);
        $categorieMesure->setCreatedBy($this->getUser());
        $categorieMesure->setUpdatedBy($this->getUser());
        $errorResponse = $this->errorResponse($categorieMesure);
        if ($errorResponse !== null) {
            return $errorResponse; 
        } else {

            $moduleRepository->add($categorieMesure, true);
        }

        return $this->responseData($categorieMesure, 'group1', ['Content-Type' => 'application/json']);
    }


    #[Route('/update/{id}', methods: ['PUT', 'POST'])]
    #[OA\Post(
        summary: "Permet de mettre a jour un categorieMesure.",
        description: "Permet de mettre a jour un categorieMesure.",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "libelle", type: "string"),
                    new OA\Property(property: "code", type: "string"),


                ],
                type: "object"
            )
        ),
        responses: [
            new OA\Response(response: 401, description: "Invalid credentials")
        ]
    )]
    #[OA\Tag(name: 'categorieMesure')]
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

                // On retourne la confirmation
                $response = $this->responseData($categorieMesure, 'group1', ['Content-Type' => 'application/json']);
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
     * permet de supprimer un(e) categorieMesure.
     */
    #[OA\Response(
        response: 200,
        description: 'permet de supprimer un(e) categorieMesure',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new AttributeModel(type: CategorieMesure::class, groups: ['full']))
        )
    )]
    #[OA\Tag(name: 'categorieMesure')]
    //#[Security(name: 'Bearer')]
    public function delete(Request $request, CategorieMesure $categorieMesure, CategorieMesureRepository $villeRepository): Response
    {
        try {

            if ($categorieMesure != null) {

                $villeRepository->remove($categorieMesure, true);

                // On retourne la confirmation
                $this->setMessage("Operation effectuées avec success");
                $response = $this->response($categorieMesure);
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
     * Permet de supprimer plusieurs categorieMesure.
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
    #[OA\Tag(name: 'categorieMesure')]
    #[Security(name: 'Bearer')]
    public function deleteAll(Request $request, CategorieMesureRepository $villeRepository): Response
    {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        try {
            $data = json_decode($request->getContent());

            foreach ($data['ids'] as $id) {
                $categorieMesure = $villeRepository->find($id);

                if ($categorieMesure != null) {
                    $villeRepository->remove($categorieMesure);
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
