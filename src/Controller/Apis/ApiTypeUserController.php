<?php

namespace  App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use App\DTO\TypeUserDTO;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Entity\TypeUser;
use App\Repository\TypeUserRepository;
use App\Repository\UserRepository;
use DateTime;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Attribute\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

#[Route('/api/typeUser')]
class ApiTypeUserController extends ApiInterface
{



    #[Route('', methods: ['GET'])]
    /**
     * Retourne la liste des typeUsers.
     * 
     */
    #[OA\Response(
        response: 200,
        description: 'Returns the rewards of an user',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: TypeUser::class, groups: ['full']))
        )
    )]
    #[OA\Tag(name: 'typeUser')]
    // #[Security(name: 'Bearer')]
    public function index(TypeUserRepository $typeUserRepository): Response
    {
        try {

            $typeUsers = $this->paginationService->paginate($typeUserRepository->getTypeWithoutUser());



            $response =  $this->responseData($typeUsers, 'group1', ['Content-Type' => 'application/json']);
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
     * Affiche un(e) typeUser en offrant un identifiant.
     */
    #[OA\Response(
        response: 200,
        description: 'Affiche un(e) typeUser en offrant un identifiant',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: TypeUser::class, groups: ['full']))
        )
    )]
    #[OA\Parameter(
        name: 'code',
        in: 'query',
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Tag(name: 'typeUser')]
    //#[Security(name: 'Bearer')]
    public function getOne(?TypeUser $typeUser)
    {
        try {
            if ($typeUser) {
                $response = $this->response($typeUser);
            } else {
                $this->setMessage('Cette ressource est inexistante');
                $this->setStatusCode(300);
                $response = $this->response($typeUser);
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
     * Permet de créer un(e) typeUser.
     */
    #[OA\Post(
        summary: "Authentification admin",
        description: "Génère un token JWT pour les administrateurs.",
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
    #[OA\Tag(name: 'typeUser')]
    public function create(Request $request, TypeUserRepository $typeUserRepository): Response
    {

        $data = json_decode($request->getContent(), true);
        $typeUser = new TypeUser();
        $typeUser->setLibelle($data['libelle']);
        $typeUser->setCode($data['code']);
        $typeUser->setCreatedAtValue(new DateTime());
        $typeUser->setCreatedBy($this->getuser());
        $typeUser->setIsActive(true);
        $typeUser->setUpdatedBy($this->getuser());
        $errorResponse = $this->errorResponse($typeUser);
        if ($errorResponse !== null) {
            return $errorResponse;
        } else {

            $typeUserRepository->add($typeUser, true);
        }

        return $this->responseData($typeUser, 'group1', ['Content-Type' => 'application/json']);
    }


    #[Route('/update/{id}', methods: ['PUT', 'POST'])]
    #[OA\Post(
        summary: "Creation de typeUser",
        description: "Permet de créer un typeUser.",
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
    #[OA\Tag(name: 'typeUser')]
    public function update(Request $request, TypeUser $typeUser, TypeUserRepository $typeUserRepository): Response
    {
        try {
            $data = json_decode($request->getContent());
            if ($typeUser != null) {

                $typeUser->setLibelle($data->libelle);
                $typeUser->setCode($data->code);
                $typeUser->setUpdatedBy($this->getuser());
                $typeUser->setUpdatedAt(new \DateTime());
                $errorResponse = $this->errorResponse($typeUser);

                if ($errorResponse !== null) {
                    return $errorResponse;
                } else {
                    $typeUserRepository->add($typeUser, true);
                }



                // On retourne la confirmation
                $response = $this->responseData($typeUser, 'group1', ['Content-Type' => 'application/json']);
            } else {
                $this->setMessage("Cette ressource est inexsitante");
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

    //const TAB_ID = 'parametre-tabs';

    #[Route('/delete/{id}',  methods: ['DELETE'])]
    /**
     * permet de supprimer un(e) typeUser.
     */
    #[OA\Response(
        response: 200,
        description: 'permet de supprimer un(e) typeUser',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: TypeUser::class, groups: ['full']))
        )
    )]
    #[OA\Tag(name: 'typeUser')]
    //#[Security(name: 'Bearer')]
    public function delete(Request $request, TypeUser $typeUser, TypeUserRepository $villeRepository): Response
    {
        try {

            if ($typeUser != null) {

                $villeRepository->remove($typeUser, true);

                // On retourne la confirmation
                $this->setMessage("Operation effectuées avec success");
                $response = $this->response($typeUser);
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
     * Permet de supprimer plusieurs typeUser.
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
    #[OA\Tag(name: 'typeUser')]
    public function deleteAll(Request $request, TypeUserRepository $villeRepository): Response
    {
        try {
            $data = json_decode($request->getContent());

            foreach ($data['ids'] as $id) {
                $typeUser = $villeRepository->find($id);

                if ($typeUser != null) {
                    $villeRepository->remove($typeUser);
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
