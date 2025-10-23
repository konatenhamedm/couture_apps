<?php

namespace  App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use App\DTO\ModeleDTO;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Entity\Modele;
use App\Repository\ModeleRepository;
use App\Repository\TypeUserRepository;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Attribute\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

#[Route('/api/modele')]
class ApiModeleController extends ApiInterface
{



    #[Route('/', methods: ['GET'])]
    /**
     * Retourne la liste des modeles.
     * 
     */
    #[OA\Response(
        response: 200,
        description: 'Returns the rewards of an user',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Modele::class, groups: ['full']))
        )
    )]
    #[OA\Tag(name: 'modele')]
    // #[Security(name: 'Bearer')]
    public function index(ModeleRepository $modeleRepository): Response
    {
        try {

            $modeles = $modeleRepository->findAll();



            $response =  $this->responseData($modeles, 'group1', ['Content-Type' => 'application/json']);
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
            items: new OA\Items(ref: new Model(type: Modele::class, groups: ['full']))
        )
    )]
    #[OA\Tag(name: 'modele')]
    // #[Security(name: 'Bearer')]
    public function indexAll(ModeleRepository $modeleRepository, TypeUserRepository $typeUserRepository): Response
    {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        try {
            if ($this->getUser()->getType() == $typeUserRepository->findOneBy(['code' => 'SADM'])) {


                $modeles = $modeleRepository->findBy(
                    ['entreprise' => $this->getUser()->getEntreprise()],
                    ['id' => 'ASC']
                );
            } else {
                $modeles = $modeleRepository->findBy(
                    ['surccursale' => $this->getUser()->getSurccursale()],
                    ['id' => 'ASC']
                );
            }


            $response =  $this->responseData($modeles, 'group1', ['Content-Type' => 'application/json']);
        } catch (\Exception $exception) {
            $this->setMessage("");
            $response = $this->response('[]');
        }

        // On envoie la réponse
        return $response;
    }


    #[Route('/get/one/{id}', methods: ['GET'])]
    /**
     * Affiche un(e) modele en offrant un identifiant.
     */
    #[OA\Response(
        response: 200,
        description: 'Affiche un(e) modele en offrant un identifiant',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Modele::class, groups: ['full']))
        )
    )]
    #[OA\Parameter(
        name: 'code',
        in: 'query',
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Tag(name: 'modele')]
    //#[Security(name: 'Bearer')]
    public function getOne(?Modele $modele)
    {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        try {
            if ($modele) {
                $response = $this->response($modele);
            } else {
                $this->setMessage('Cette ressource est inexistante');
                $this->setStatusCode(300);
                $response = $this->response($modele);
            }
        } catch (\Exception $exception) {
            $this->setMessage($exception->getMessage());
            $response = $this->response('[]');
        }


        return $response;
    }


    #[Route('/create', methods: ['POST'])]
    /**
     * Permet de créer un(e) modele.
     */
    #[OA\Post(
        summary: "Permet de créer un(e) modele.",
        description: "Permet de créer un(e) modele.",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "multipart/form-data",
                schema: new OA\Schema(
                    type: "object",
                    properties: [
                        new OA\Property(property: "libelle", type: "string"),
                        new OA\Property(property: "quantite", type: "string"),
                        new OA\Property(property: "photo", type: "string", format: "binary"),
                    ],

                )
            )
        ),
        responses: [
            new OA\Response(response: 401, description: "Invalid credentials")
        ]
    )]
    #[OA\Tag(name: 'modele')]
    public function create(Request $request, ModeleRepository $modeleRepository): Response
    {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        $names = 'document_' . '01';
        $filePrefix  = str_slug($names);
        $filePath = $this->getUploadDir(self::UPLOAD_PATH, true);
        $data = json_decode($request->getContent(), true);


        $modele = new Modele();
        $modele->setLibelle($request->get('libelle'));
        $modele->setQuantiteGlobale($request->get('quantite'));

        $uploadedFile = $request->files->get('photo');

        if ($uploadedFile) {
            if ($fichier = $this->utils->sauvegardeFichier($filePath, $filePrefix, $uploadedFile, self::UPLOAD_PATH)) {
                $modele->setPhoto($fichier);
            }
        }

        $modele->setCreatedBy($this->getUser());
        $modele->setUpdatedBy($this->getUser());
        $errorResponse = $this->errorResponse($modele);
        if ($errorResponse !== null) {
            return $errorResponse;
        } else {

            $modeleRepository->add($modele, true);
        }

        return $this->responseData($modele, 'group1', ['Content-Type' => 'application/json']);
    }

    #[Route('/update/{id}', methods: ['PUT', 'POST'])]
    #[OA\Post(
        summary: "Permet de mettre à jour un(e) modele.",
        description: "Permet de mettre à jour un(e) modele.",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "multipart/form-data",
                schema: new OA\Schema(
                    type: "object",
                    properties: [
                        new OA\Property(property: "libelle", type: "string"),
                        new OA\Property(property: "quantite", type: "string"),
                        new OA\Property(property: "photo", type: "string", format: "binary"),
                    ],

                )
            )
        ),
        responses: [
            new OA\Response(response: 401, description: "Invalid credentials")
        ]
    )]
    #[OA\Tag(name: 'modele')]
    public function update(Request $request, Modele $modele, ModeleRepository $modeleRepository): Response
    {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        try {
            $data = json_decode($request->getContent());
            $names = 'document_' . '01';
            $filePrefix  = str_slug($names);
            $filePath = $this->getUploadDir(self::UPLOAD_PATH, true);
            if ($modele != null) {

                $modele->setLibelle($request->get('libelle'));
                $modele->setQuantiteGlobale($request->get('quantite'));

                $uploadedFile = $request->files->get('photo');

                if ($uploadedFile) {
                    if ($fichier = $this->utils->sauvegardeFichier($filePath, $filePrefix, $uploadedFile, self::UPLOAD_PATH)) {
                        $modele->setPhoto($fichier);
                    }
                }
                $modele->setUpdatedBy($this->getUser());
                $modele->setUpdatedAt(new \DateTime());
                $errorResponse = $this->errorResponse($modele);

                if ($errorResponse !== null) {
                    return $errorResponse;
                } else {
                    $modeleRepository->add($modele, true);
                }

                // On retourne la confirmation
                $response = $this->responseData($modele, 'group1', ['Content-Type' => 'application/json']);
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
     * permet de supprimer un(e) modele.
     */
    #[OA\Response(
        response: 200,
        description: 'permet de supprimer un(e) modele',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Modele::class, groups: ['full']))
        )
    )]
    #[OA\Tag(name: 'modele')]
    //#[Security(name: 'Bearer')]
    public function delete(Request $request, Modele $modele, ModeleRepository $villeRepository): Response
    {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        try {

            if ($modele != null) {

                $villeRepository->remove($modele, true);

                // On retourne la confirmation
                $this->setMessage("Operation effectuées avec success");
                $response = $this->response($modele);
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

    #[Route('/delete/all', methods: ['DELETE'])]
    /**
     * Supprime plusieurs modèles à partir d’un tableau d’IDs.
     * @param Request $request
     * @param ModeleRepository $modeleRepository
     * @return Response
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
    #[OA\Response(
        response: 200,
        description: 'Suppression effectuée avec succès',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Opération effectuée avec succès')
            ]
        )
    )]
    #[OA\Response(response: 500, description: 'Erreur interne lors de la suppression')]
    #[OA\Tag(name: 'modele')]
    public function deleteAll(Request $request, ModeleRepository $villeRepository): Response
    {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        try {
            $data = json_decode($request->getContent());

            foreach ($data->ids as $key => $value) {
                $modele = $villeRepository->find($value['id']);

                if ($modele != null) {
                    $villeRepository->remove($modele);
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
