<?php

namespace  App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use App\DTO\ModeleBoutiqueDTO;
use App\Entity\Boutique;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Entity\ModeleBoutique;
use App\Repository\BoutiqueRepository;
use App\Repository\ModeleBoutiqueRepository;
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

#[Route('/api/modeleBoutique')]
class ApiModeleBoutiqueController extends ApiInterface
{



    #[Route('/', methods: ['GET'])]
    /**
     * Retourne la liste des modeleBoutiques.
     * 
     */
    #[OA\Response(
        response: 200,
        description: 'Returns the rewards of an user',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: ModeleBoutique::class, groups: ['full']))
        )
    )]
    #[OA\Tag(name: 'modeleBoutique')]
    // #[Security(name: 'Bearer')]
    public function index(ModeleBoutiqueRepository $modeleBoutiqueRepository): Response
    {
        try {

            $modeleBoutiques =  $this->paginationService->paginate($modeleBoutiqueRepository->findAll());



            $response =  $this->responseData($modeleBoutiques, 'group1', ['Content-Type' => 'application/json']);
        } catch (\Exception $exception) {
            $this->setMessage("");
            $response = $this->response('[]');
        }

        // On envoie la réponse
        return $response;
    }
    #[Route('/modele/by/boutique/{id}', methods: ['GET'])]
    /**
     * Retourne la liste des modeles d'une boutique.
     * 
     */
    #[OA\Response(
        response: 200,
        description: 'Returns the rewards of an user',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: ModeleBoutique::class, groups: ['full']))
        )
    )]
    #[OA\Tag(name: 'modele')]
    // #[Security(name: 'Bearer')]
    public function indexByBoutique(ModeleRepository $modeleRepository, Boutique $boutique, BoutiqueRepository $boutiqueRepository): Response
    {
        try {
             if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

            $modeles = $this->paginationService->paginate($modeleRepository->findBy(
                ['boutique' => $boutique->getId()],
                ['id' => 'ASC']
            ));

            $response =  $this->responseData($modeles, 'group1', ['Content-Type' => 'application/json']);
        } catch (\Exception $exception) {
            $this->setMessage("");
            $response = $this->response('[]');
        }

        // On envoie la réponse
        return $response;
    }


    #[Route('/entreprise/{boutique}', methods: ['GET'])]
    /**
     * Retourne la liste des ModeleBoutiques d'une entreprise.
     * 
     */
    #[OA\Response(
        response: 200,
        description: 'Returns the rewards of an user',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: ModeleBoutique::class, groups: ['full']))
        )
    )]
    #[OA\Tag(name: 'modeleBoutique')]
    // #[Security(name: 'Bearer')]
    public function indexAll(ModeleBoutiqueRepository $modeleBoutiqueRepository, TypeUserRepository $typeUserRepository,$boutique): Response
    {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        try {
            if ($this->getUser()->getType() == $typeUserRepository->findOneBy(['code' => 'SADM'])) {

                $modeleBoutiques = $this->paginationService->paginate($modeleBoutiqueRepository->findBy(
                    ['boutique' => $boutique],
                    ['id' => 'ASC']
                ));
            } else {
                $modeleBoutiques = $this->paginationService->paginate($modeleBoutiqueRepository->findBy(
                    ['boutique' => $this->getUser()->getBoutique()],
                    ['id' => 'ASC']
                ));
            }


            $response =  $this->responseData($modeleBoutiques, 'group1', ['Content-Type' => 'application/json']);
        } catch (\Exception $exception) {
            $this->setMessage("");
            $response = $this->response('[]');
        }

        // On envoie la réponse
        return $response;
    }


    #[Route('/get/one/{id}', methods: ['GET'])]
    /**
     * Affiche un(e) modeleBoutique en offrant un identifiant.
     */
    #[OA\Response(
        response: 200,
        description: 'Affiche un(e) modeleBoutique en offrant un identifiant',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: ModeleBoutique::class, groups: ['full']))
        )
    )]
    #[OA\Parameter(
        name: 'code',
        in: 'query',
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Tag(name: 'modeleBoutique')]
    //#[Security(name: 'Bearer')]
    public function getOne(?ModeleBoutique $modeleBoutique)
    {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        try {
            if ($modeleBoutique) {
                $response = $this->response($modeleBoutique);
            } else {
                $this->setMessage('Cette ressource est inexistante');
                $this->setStatusCode(300);
                $response = $this->response($modeleBoutique);
            }
        } catch (\Exception $exception) {
            $this->setMessage($exception->getMessage());
            $response = $this->response('[]');
        }


        return $response;
    }


    #[Route('/create', methods: ['POST'])]
    /**
     * Permet de créer un(e) modeleBoutique.
     */
    #[OA\Post(
        summary: "Authentification admin",
        description: "Génère un token JWT pour les administrateurs.",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "prix", type: "string"),
                    new OA\Property(property: "qauntite", type: "string"),
                    new OA\Property(property: "modele", type: "string"),
                    new OA\Property(property: "boutique", type: "string"),

                ],
                type: "object"
            )
        ),
        responses: [
            new OA\Response(response: 401, description: "Invalid credentials")
        ]
    )]
    #[OA\Tag(name: 'modeleBoutique')]
    public function create(Request $request, ModeleRepository $modeleRepository, BoutiqueRepository $boutiqueRepository, ModeleBoutiqueRepository $modeleBoutiqueRepository): Response
    {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        $data = json_decode($request->getContent(), true);

        $modele = $modeleRepository->find($data['modele']);
        $modeleBoutique = new ModeleBoutique();
        $modeleBoutique->setPrix($data['prix']);
        $modeleBoutique->setQuantite($data['quantite']);
        $modeleBoutique->setBoutique($boutiqueRepository->find($data['boutique']));
        $modeleBoutique->setModele($modele);

        $modeleBoutique->setCreatedBy($this->getUser());
        $modeleBoutique->setUpdatedBy($this->getUser());
        $errorResponse = $this->errorResponse($modeleBoutique);
        if ($errorResponse !== null) {
            return $errorResponse; 
        } else {

            $modeleBoutiqueRepository->add($modeleBoutique, true);
            $modele->setQuantiteGlobale($modele->getQuantiteGlobale() + $modeleBoutique->getQuantite());
            $modeleRepository->add($modele, true);
        }

        return $this->responseData($modeleBoutique, 'group1', ['Content-Type' => 'application/json']);
    }

    #[Route('/update/{id}', methods: ['PUT', 'POST'])]
    #[OA\Post(
        summary: "Permet de modifier un(e) modeleBoutique.",
        description: "Permet de modifier un(e) modeleBoutique.",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "prix", type: "string"),
                   /*  new OA\Property(property: "qauntite", type: "string"), */
                    new OA\Property(property: "modele", type: "string"),
                    new OA\Property(property: "boutique", type: "string"),

                ],
                type: "object"
            )
        ),
        responses: [
            new OA\Response(response: 401, description: "Invalid credentials")
        ]
    )]
    #[OA\Tag(name: 'modeleBoutique')]
    public function update(Request $request, ModeleBoutique $modeleBoutique, ModeleRepository $modeleRepository, BoutiqueRepository $boutiqueRepository, ModeleBoutiqueRepository $modeleBoutiqueRepository): Response
    {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        try {
            $data = json_decode($request->getContent());

            if ($modeleBoutique != null) {

                $modele = $modeleRepository->find($data->modele);
                $modeleBoutique->setPrix($data->prix);
                /* $modeleBoutique->setQuantite($data->quantite); */
                $modeleBoutique->setBoutique($boutiqueRepository->find($data->boutique));
                $modeleBoutique->setModele($modele);
                $modeleBoutique->setUpdatedBy($this->getUser());
                $modeleBoutique->setUpdatedAt(new \DateTime());
                $errorResponse = $this->errorResponse($modeleBoutique);

                if ($errorResponse !== null) {
                    return $errorResponse; 
                } else {
                    $modeleBoutiqueRepository->add($modeleBoutique, true);
                }
                // On retourne la confirmation
                $response = $this->responseData($modeleBoutique, 'group1', ['Content-Type' => 'application/json']);
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
     * permet de supprimer un(e) modeleBoutique.
     */
    #[OA\Response(
        response: 200,
        description: 'permet de supprimer un(e) modeleBoutique',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: ModeleBoutique::class, groups: ['full']))
        )
    )]
    #[OA\Tag(name: 'modeleBoutique')]
    //#[Security(name: 'Bearer')]
    public function delete(Request $request, ModeleBoutique $modeleBoutique, ModeleBoutiqueRepository $villeRepository): Response
    {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        try {

            if ($modeleBoutique != null) {

                $villeRepository->remove($modeleBoutique, true);

                // On retourne la confirmation
                $this->setMessage("Operation effectuées avec success");
                $response = $this->response($modeleBoutique);
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
     * Permet de supprimer plusieurs modeleBoutique.
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
    #[OA\Tag(name: 'modeleBoutique')]
    public function deleteAll(Request $request, ModeleBoutiqueRepository $villeRepository): Response
    {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        try {
            $data = json_decode($request->getContent());

            foreach ($data['ids'] as $id) {
                $modeleBoutique = $villeRepository->find($id);

                if ($modeleBoutique != null) {
                    $villeRepository->remove($modeleBoutique);
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
