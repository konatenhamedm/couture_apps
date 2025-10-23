<?php

namespace  App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use App\DTO\ClientDTO;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Entity\Client;
use App\Repository\BoutiqueRepository;
use App\Repository\ClientRepository;
use App\Repository\SurccursaleRepository;
use App\Repository\TypeUserRepository;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Attribute\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

#[Route('/api/client')]
class ApiClientController extends ApiInterface
{



    #[Route('/', methods: ['GET'])]
    /**
     * Retourne la liste des clients.
     * 
     */
    #[OA\Response(
        response: 200,
        description: 'Returns the rewards of an user',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Client::class, groups: ['full']))
        )
    )]
    #[OA\Tag(name: 'client')]
    // #[Security(name: 'Bearer')]
    public function index(ClientRepository $clientRepository): Response
    {
        try {

            $clients = $this->paginationService->paginate($clientRepository->findAll());



            $response =  $this->responseData($clients, 'group1', ['Content-Type' => 'application/json']);
        } catch (\Exception $exception) {
            $this->setMessage("");
            $response = $this->response('[]');
        }

        // On envoie la réponse
        return $response;
    }


    #[Route('/entreprise', methods: ['GET'])]
    /**
     * Retourne la liste des clients d'une entreprise.
     * 
     */
    #[OA\Response(
        response: 200,
        description: 'Returns the rewards of an user',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Client::class, groups: ['full']))
        )
    )]
    #[OA\Tag(name: 'client')]
    // #[Security(name: 'Bearer')]
    public function indexAll(ClientRepository $clientRepository, TypeUserRepository $typeUserRepository): Response
    {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }
        try {
            if ($this->getUser()->getType() == $typeUserRepository->findOneBy(['code' => 'SADM'])) {

                $clients = $this->paginationService->paginate($clientRepository->findBy(
                    ['entreprise' => $this->getUser()->getEntreprise()],
                    ['id' => 'ASC']
                ));
            } else {
                $clients = $this->paginationService->paginate($clientRepository->findBy(
                    ['surccursale' => $this->getUser()->getSurccursale()],
                    ['id' => 'ASC']
                ));
            }


            $response =  $this->responseData($clients, 'group1', ['Content-Type' => 'application/json']);
        } catch (\Exception $exception) {
            $this->setMessage("");
            $response = $this->response('[]');
        }

        // On envoie la réponse
        return $response;
    }


    #[Route('/get/one/{id}', methods: ['GET'])]
    /**
     * Affiche un(e) client en offrant un identifiant.
     */
    #[OA\Response(
        response: 200,
        description: 'Affiche un(e) client en offrant un identifiant',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Client::class, groups: ['full']))
        )
    )]
    #[OA\Parameter(
        name: 'code',
        in: 'query',
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Tag(name: 'client')]
    //#[Security(name: 'Bearer')]
    public function getOne(?Client $client)
    {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }
        try {
            if ($client) {
                $response = $this->response($client);
            } else {
                $this->setMessage('Cette ressource est inexistante');
                $this->setStatusCode(300);
                $response = $this->response($client);
            }
        } catch (\Exception $exception) {
            $this->setMessage($exception->getMessage());
            $response = $this->response('[]');
        }


        return $response;
    }


    #[Route('/create', methods: ['POST'])]
    /**
     * Permet de créer un(e) client.
     */
    #[OA\Post(
        summary: "Permet de créer un(e) client pour un surccursale",
        description: "Permet de créer un(e) client.",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "multipart/form-data",
                schema: new OA\Schema(
                    type: "object",
                    properties: [
                        new OA\Property(property: "nom", type: "string"),
                        new OA\Property(property: "prenoms", type: "string"),
                        new OA\Property(property: "numero", type: "string"),
                        new OA\Property(property: "surccursale", type: "string"),
                        new OA\Property(property: "photo", type: "string", format: "binary"),
                    ],

                )
            )
        ),
        responses: [
            new OA\Response(response: 401, description: "Invalid credentials")
        ]
    )]
    #[OA\Tag(name: 'client')]
    public function create(Request $request, ClientRepository $clientRepository, SurccursaleRepository $surccursaleRepository): Response
    {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }
        $names = 'document_' . '01';
        $filePrefix  = str_slug($names);
        $filePath = $this->getUploadDir(self::UPLOAD_PATH, true);
        $data = json_decode($request->getContent(), true);

        $uploadedFile = $request->files->get('photo');

        $client = new Client();
        $client->setEntreprise($this->getUser()->getEntreprise());
        $client->setPrenom($request->get('prenoms'));
        $client->setNom($request->get('nom'));
        $client->setNumero($request->get('numero'));
        $client->setSurccursale($surccursaleRepository->find($request->get('surccursale')));

        if ($uploadedFile) {
            if ($fichier = $this->utils->sauvegardeFichier($filePath, $filePrefix, $uploadedFile, self::UPLOAD_PATH)) {
                $client->setPhoto($fichier);
            }
        }

        $client->setCreatedBy($this->getUser());
        $client->setUpdatedBy($this->getUser());
        $errorResponse = $this->errorResponse($client);
        if ($errorResponse !== null) {
            return $errorResponse; 
        } else {

            $clientRepository->add($client, true);
        }

        return $this->responseData($client, 'group1', ['Content-Type' => 'application/json']);
    }
    #[Route('/create/boutique', methods: ['POST'])]
    /**
     * Permet de créer un(e) client boutique.
     */
    #[OA\Post(
        summary: "Permet de créer un(e) client pour une boutique",
        description: "Permet de créer un(e) client.",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "multipart/form-data",
                schema: new OA\Schema(
                    type: "object",
                    properties: [
                        new OA\Property(property: "nom", type: "string"),
                        new OA\Property(property: "prenom", type: "string"),
                        new OA\Property(property: "numero", type: "string"),
                        new OA\Property(property: "boutique", type: "string"),
                        new OA\Property(property: "photo", type: "string", format: "binary"),
                    ],

                )
            )
        ),
        responses: [
            new OA\Response(response: 401, description: "Invalid credentials")
        ]
    )]
    #[OA\Tag(name: 'client')]
    public function createBoutique(Request $request, ClientRepository $clientRepository,BoutiqueRepository $boutiqueRepository): Response
    {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }
        $names = 'document_' . '01';
        $filePrefix  = str_slug($names);
        $filePath = $this->getUploadDir(self::UPLOAD_PATH, true);
        $data = json_decode($request->getContent(), true);

        $uploadedFile = $request->files->get('photo');

        $client = new Client();
        $client->setEntreprise($this->getUser()->getEntreprise());
        $client->setNom($request->get('nom'));
        $client->setPrenom($request->get('prenoms'));
        $client->setNumero($request->get('numero'));
        $client->setBoutique($boutiqueRepository->find($request->get('boutique')));

        if ($uploadedFile) {
            if ($fichier = $this->utils->sauvegardeFichier($filePath, $filePrefix, $uploadedFile, self::UPLOAD_PATH)) {
                $client->setPhoto($fichier);
            }
        }

        $client->setCreatedBy($this->getUser());
        $client->setUpdatedBy($this->getUser());
        $errorResponse = $this->errorResponse($client);
        if ($errorResponse !== null) {
            return $errorResponse; 
        } else {

            $clientRepository->add($client, true);
        }

        return $this->responseData($client, 'group1', ['Content-Type' => 'application/json']);
    }

    #[Route('/update/{id}', methods: ['PUT', 'POST'])]
    #[OA\Post(
        summary: "Permet de mettre a jour un client.",
        description: "Permet de mettre a jour un client.",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "multipart/form-data",
                schema: new OA\Schema(
                    type: "object",
                    properties: [
                        new OA\Property(property: "nom", type: "string"),
                        new OA\Property(property: "numero", type: "string"),
                        new OA\Property(property: "surccursale", type: "string"),
                        new OA\Property(property: "photo", type: "string", format: "binary"),
                    ],

                )
            )
        ),
        responses: [
            new OA\Response(response: 401, description: "Invalid credentials")
        ]
    )]
    #[OA\Tag(name: 'client')]
    public function update(Request $request, Client $client, ClientRepository $clientRepository): Response
    {
       if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }
        try {
            $data = json_decode($request->getContent());
            $names = 'document_' . '01';
            $filePrefix  = str_slug($names);
            $filePath = $this->getUploadDir(self::UPLOAD_PATH, true);
            if ($client != null) {

                $client->setNom($request->get('nom'));
                $client->setNumero($request->get('numero'));
                $client->setSurccursale($request->get('surccursale'));
                $uploadedFile = $request->files->get('photo');

                if ($uploadedFile) {
                    if ($fichier = $this->utils->sauvegardeFichier($filePath, $filePrefix, $uploadedFile, self::UPLOAD_PATH)) {
                        $client->setPhoto($fichier);
                    }
                }
                $client->setUpdatedBy($this->getUser());
                $client->setUpdatedAt(new \DateTime());
                $errorResponse = $this->errorResponse($client);

                if ($errorResponse !== null) {
                    return $errorResponse; 
                } else {
                    $clientRepository->add($client, true);
                }



                // On retourne la confirmation
                $response = $this->responseData($client, 'group1', ['Content-Type' => 'application/json']);
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
     * permet de supprimer un(e) client.
     */
    #[OA\Response(
        response: 200,
        description: 'permet de supprimer un(e) client',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Client::class, groups: ['full']))
        )
    )]
    #[OA\Tag(name: 'client')]
    //#[Security(name: 'Bearer')]
    public function delete(Request $request, Client $client, ClientRepository $villeRepository): Response
    {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }
        try {

            if ($client != null) {

                $villeRepository->remove($client, true);

                // On retourne la confirmation
                $this->setMessage("Operation effectuées avec success");
                $response = $this->response($client);
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
     * Permet de supprimer plusieurs client.
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
    #[OA\Tag(name: 'client')]
    public function deleteAll(Request $request, ClientRepository $villeRepository): Response
    {

        try {
            $data = json_decode($request->getContent());

            foreach ($data['ids'] as $id) {
                $client = $villeRepository->find($id);

                if ($client != null) {
                    $villeRepository->remove($client);
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
