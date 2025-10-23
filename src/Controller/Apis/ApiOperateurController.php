<?php

namespace  App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use App\DTO\OperateurDTO;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Entity\Operateur;
use App\Entity\Pays;
use App\Repository\OperateurRepository;
use App\Repository\PaysRepository;
use App\Repository\UserRepository;
use DateTime;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Attribute\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

#[Route('/api/operateur')]
class ApiOperateurController extends ApiInterface
{

    #[Route('/', methods: ['GET'])]
    /**
     * Retourne la liste des operateurs.
     * 
     */
    #[OA\Response(
        response: 200,
        description: 'Returns the rewards of a user',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Operateur::class, groups: ['full']))
        )
    )]
    #[OA\Tag(name: 'operateur')]
    // #[Security(name: 'Bearer')]
    public function index(OperateurRepository $operateurRepository): Response
    {
        try {

            $operateurs = $this->paginationService->paginate($operateurRepository->findAll());



            $response =  $this->responseData($operateurs, 'group1', ['Content-Type' => 'application/json']);
        } catch (\Exception $exception) {
            $this->setMessage("");
            $response = $this->response('[]');
        }

        // On envoie la réponse
        return $response;
    }

     #[Route('/{id}', methods: ['GET'])]
    /**
     * Retourne la liste des operateurs d'un pays.
     * 
     */
    #[OA\Response(
        response: 200,
        description: 'Returns the rewards of a user',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Operateur::class, groups: ['full']))
        )
    )]
    #[OA\Tag(name: 'operateur')]
    // #[Security(name: 'Bearer')]
    public function indexByPays(OperateurRepository $operateurRepository,Pays $pays): Response
    {
        try {

            $operateurs = $this->paginationService->paginate($operateurRepository->findBy(['pays'=>$pays->getId(),'actif'=>true]));

            $response =  $this->responseData($operateurs, 'group1', ['Content-Type' => 'application/json']);
        } catch (\Exception $exception) {
            $this->setMessage("");
            $response = $this->response('[]');
        }

        // On envoie la réponse
        return $response;
    }



    #[Route('/get/one/{id}', methods: ['GET'])]
    /**
     * Affiche un(e) operateur en offrant un identifiant.
     */
    #[OA\Response(
        response: 200,
        description: 'Affiche un(e) operateur en offrant un identifiant',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Operateur::class, groups: ['full']))

        )
    )]
    #[OA\Parameter(
        name: 'code',
        in: 'query',
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Tag(name: 'operateur')]
    //#[Security(name: 'Bearer')]
    public function getOne(?Operateur $operateur)
    {
        try {
            if ($operateur) {
                $response = $this->response($operateur);
            } else {
                $this->setMessage('Cette ressource est inexistante');
                $this->setStatusCode(300);
                $response = $this->response($operateur);
            }
        } catch (\Exception $exception) {
            $this->setMessage($exception->getMessage());
            $response = $this->response('[]');
        }


        return $response;
    }


    #[Route('/create', methods: ['POST'])]
    #[OA\Post(
        summary: "Création d'une operateur.",
        description: "Création d'une operateur.",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "multipart/form-data",
                schema: new OA\Schema(
                    type: "object",
                    required: ["libelle", "code"],
                    properties: [
                        new OA\Property(property: "libelle", type: "string"),
                        new OA\Property(property: "code", type: "string"),
                        new OA\Property(property: "photo", type: "string", format: "binary"),

                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 401, description: "Invalid credentials")
        ]
    )]
    #[OA\Tag(name: 'operateur')]
    public function create(Request $request, OperateurRepository $operateurRepository): Response
    {
        $names = 'document_' . '01';
        $filePrefix  = str_slug($names);
        $filePath = $this->getUploadDir(self::UPLOAD_PATH, true);
        $libelle = $request->request->get('libelle');
        $code = $request->request->get('code');


        $operateur = new Operateur();
        $operateur->setLibelle($libelle);
        $operateur->setCode($code);
        $operateur->setActif(true);
        $operateur->setCreatedBy($this->getUser());
        $operateur->setUpdatedBy($this->getUser());
        $operateur->setCreatedAtValue(new \DateTime());
        $operateur->setUpdatedAt(new \DateTime());

        $uploadedFile = $request->files->get('photo');

        if ($uploadedFile) {
            if ($fichier = $this->utils->sauvegardeFichier($filePath, $filePrefix, $uploadedFile, self::UPLOAD_PATH)) {
                $operateur->setPhoto($fichier);
            }
        }

        $errorResponse = $this->errorResponse($operateur);
        if ($errorResponse !== null) {
            return $errorResponse;
        }

        $operateurRepository->add($operateur, true);

        return $this->responseData($operateur, 'group1', ['Content-Type' => 'application/json']);
    }



    #[Route('/update/{id}', methods: ['PUT', 'POST'])]
    #[OA\Post(
        summary: "Mise à jour de operateur.",
        description: "Mise à jour de operateur.",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "multipart/form-data",
                schema: new OA\Schema(
                    type: "object",
                    required: ["libelle", "code", "userUpdate"],
                    properties: [
                        new OA\Property(property: "libelle", type: "string"),
                        new OA\Property(property: "code", type: "string"),
                        new OA\Property(property: "pays", type: "string"),
                        new OA\Property(property: "actif", type: "boolean"),
                        new OA\Property(property: "photo", type: "string", format: "binary"),



                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 401, description: "Invalid credentials")
        ]
    )]
    #[OA\Tag(name: 'operateur')]
    public function update(Request $request, PaysRepository $paysRepository, Operateur $operateur, OperateurRepository $operateurRepository): Response
    {
        try {
            $names = 'document_' . '01';
            $filePrefix  = str_slug($names);
            $filePath = $this->getUploadDir(self::UPLOAD_PATH, true);
            $libelle = $request->get('libelle');
            $code = $request->get('code');
            $pays = $request->get('pays');
            $actif = $request->get('actif');

            if ($operateur !== null) {
                $operateur->setLibelle($libelle);
                $operateur->setCode($code);
                $operateur->setActif($actif);
                $operateur->setPays($paysRepository->find($pays));
                /*  $operateur->setP($code); */
                $operateur->setUpdatedBy($this->getUser());
                $operateur->setUpdatedAt(new \DateTime());


                $uploadedFile = $request->files->get('photo');

                if ($uploadedFile) {
                    if ($fichier = $this->utils->sauvegardeFichier($filePath, $filePrefix, $uploadedFile, self::UPLOAD_PATH)) {
                        $operateur->setPhoto($fichier);
                    }
                }

                $errorResponse = $this->errorResponse($operateur);
                if ($errorResponse !== null) {
                    return $errorResponse;
                }

                $operateurRepository->add($operateur, true);
                return $this->responseData($operateur, 'group1', ['Content-Type' => 'application/json']);
            } else {
                $this->setMessage("Cette ressource est inexistante");
                $this->setStatusCode(300);
                return $this->response('[]');
            }
        } catch (\Exception $exception) {
            $this->setMessage("");
            return $this->response('[]');
        }
    }


    //const TAB_ID = 'parametre-tabs';

    #[Route('/delete/{id}',  methods: ['DELETE'])]
    /**
     * permet de supprimer un(e) operateur.
     */
    #[OA\Response(
        response: 200,
        description: 'permet de supprimer un(e) operateur',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Operateur::class, groups: ['full']))

        )
    )]
    #[OA\Tag(name: 'operateur')]
    //#[Security(name: 'Bearer')]
    public function delete(Request $request, Operateur $operateur, OperateurRepository $villeRepository): Response
    {
        try {

            if ($operateur != null) {

                $villeRepository->remove($operateur, true);

                // On retourne la confirmation
                $this->setMessage("Operation effectuées avec success");
                $response = $this->response($operateur);
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
     * Permet de supprimer plusieurs operateur.
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
    #[OA\Tag(name: 'operateur')]
    public function deleteAll(Request $request, OperateurRepository $villeRepository): Response
    {
        try {
            $data = json_decode($request->getContent());

            foreach ($data['ids'] as $id) {
                $operateur = $villeRepository->find($id);

                if ($operateur != null) {
                    $villeRepository->remove($operateur);
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
