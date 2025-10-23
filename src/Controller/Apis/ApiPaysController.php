<?php

namespace  App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use App\DTO\PaysDTO;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Entity\Pays;
use App\Repository\PaysRepository;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Attribute\Model as Model;

use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

#[Route('/api/pays')]
class ApiPaysController extends ApiInterface
{



    #[Route('/', methods: ['GET'])]
    /**
     * Retourne la liste des pays.
     * 
     */
    #[OA\Response(
        response: 200,
        description: 'Returns the rewards of an user',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Pays::class, groups: ['full']))
        )
    )]
    #[OA\Tag(name: 'pays')]
    public function index(PaysRepository $paysRepository): Response
    {
        try {

            $pays = $this->paginationService->paginate($paysRepository->findAll());

          

            $response =  $this->responseData($pays, 'group1', ['Content-Type' => 'application/json']);
        } catch (\Exception $exception) {
            $this->setMessage("");
            $response = $this->response('[]');
        }

        // On envoie la réponse
        return $response;
    }
    #[Route('/actif', methods: ['GET'])]
    /**
     * Retourne la liste des pays actif.
     * 
     */
    #[OA\Response(
        response: 200,
        description: 'Returns the rewards of an user',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Pays::class, groups: ['full']))
        )
    )]
    #[OA\Tag(name: 'pays')]
    public function indexActif(PaysRepository $paysRepository): Response
    {
        try {

            $pays = $this->paginationService->paginate($paysRepository->findBy(['actif' => true]));

          

            $response =  $this->responseData($pays, 'group1', ['Content-Type' => 'application/json']);
        } catch (\Exception $exception) {
            $this->setMessage("");
            $response = $this->response('[]');
        }

        // On envoie la réponse
        return $response;
    }


    #[Route('/get/one/{id}', methods: ['GET'])]
    /**
     * Affiche un(e) pays en offrant un identifiant.
     */
    #[OA\Response(
        response: 200,
        description: 'Affiche un(e) pays en offrant un identifiant',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Pays::class, groups: ['full']))
        )
    )]
    #[OA\Parameter(
        name: 'code',
        in: 'query',
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Tag(name: 'pays')]
    //#[Security(name: 'Bearer')]
    public function getOne(?Pays $pays)
    {
        try {
            if ($pays) {
                $response = $this->response($pays);
            } else {
                $this->setMessage('Cette ressource est inexistante');
                $this->setStatusCode(300);
                $response = $this->response($pays);
            }
        } catch (\Exception $exception) {
            $this->setMessage($exception->getMessage());
            $response = $this->response('[]');
        }


        return $response;
    }


    #[Route('/create',  methods: ['POST'])]
    /**
     * Permet de créer un(e) pays.
     */
    #[OA\Post(
        summary: "Permet de créer un(e) pays",
        description: "Permet de créer un(e) pays.",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "libelle", type: "string"),
                    new OA\Property(property: "code", type: "string"),
                    new OA\Property(property: "indicatif", type: "string"),
                  

                ],
                type: "object"
            )
        ),
        responses: [
            new OA\Response(response: 401, description: "Invalid credentials")
        ]
    )]
    #[OA\Tag(name: 'pays')]
    public function create(Request $request, PaysRepository $paysRepository): Response
    {

        $data = json_decode($request->getContent(), true);
        $pays = new Pays();
        $pays->setLibelle($data['libelle']);
        $pays->setCode($data['code']);
        $pays->setActif(true);
        $pays->setIndicatif($data['indicatif']);
        $pays->setCreatedBy($this->getUser());
        $pays->setUpdatedBy($this->getUser());
        $errorResponse = $this->errorResponse($pays);
        if ($errorResponse !== null) {
            return $errorResponse; 
        } else {

            $paysRepository->add($pays, true);
        }

        return $this->responseData($pays, 'group1', ['Content-Type' => 'application/json']);
    }


    #[Route('/update/{id}', methods: ['PUT', 'POST'])]
    #[OA\Post(
        summary: "Creation de pays",
        description: "Permet de créer un pays.",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "libelle", type: "string"),
                    new OA\Property(property: "code", type: "string"),
                    new OA\Property(property: "indicatif", type: "string"),
                    new OA\Property(property: "actif", type: "boolean"),

                ],
                type: "object"
            )
        ),
        responses: [
            new OA\Response(response: 401, description: "Invalid credentials")
        ]
    )]
    #[OA\Tag(name: 'pays')]
    public function update(Request $request, Pays $pays, PaysRepository $paysRepository): Response
    {
        try {
            $data = json_decode($request->getContent());
            if ($pays != null) {

                $pays->setLibelle($data->libelle);
                $pays->setCode($data->code);
                $pays->setActif($data->actif);
                $pays->setIndicatif($data->indicatif);
                $pays->setUpdatedBy($this->getUser());
                $pays->setUpdatedAt(new \DateTime());
                $errorResponse = $this->errorResponse($pays);

                if ($errorResponse !== null) {
                    return $errorResponse; 
                } else {
                    $paysRepository->add($pays, true);
                }



                // On retourne la confirmation
                $response = $this->responseData($pays, 'group1', ['Content-Type' => 'application/json']);
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
     * permet de supprimer un(e) pays.
     */
    #[OA\Response(
        response: 200,
        description: 'permet de supprimer un(e) pays',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Pays::class, groups: ['full']))
        )
    )]
    #[OA\Tag(name: 'pays')]
    //#[Security(name: 'Bearer')]
    public function delete(Request $request, Pays $pays, PaysRepository $villeRepository): Response
    {
        try {

            if ($pays != null) {

                $villeRepository->remove($pays, true);

                // On retourne la confirmation
                $this->setMessage("Operation effectuées avec success");
                $response = $this->response($pays);
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
     * Permet de supprimer plusieurs pays.
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
    #[OA\Tag(name: 'pays')]
    public function deleteAll(Request $request, PaysRepository $villeRepository): Response
    {
        try {
            $data = json_decode($request->getContent());

            foreach ($data['ids'] as $id) {
                $pays = $villeRepository->find($id);

                if ($pays != null) {
                    $villeRepository->remove($pays);
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
