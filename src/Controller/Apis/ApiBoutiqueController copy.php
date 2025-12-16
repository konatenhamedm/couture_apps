<?php

namespace  App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use App\DTO\BoutiqueDTO;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Entity\Boutique;
use App\Entity\Caisse;
use App\Entity\CaisseBoutique;
use App\Repository\BoutiqueRepository;
use App\Repository\CaisseBoutiqueRepository;
use App\Repository\TypeUserRepository;
use App\Repository\UserRepository;
use App\Service\Utils;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Attribute\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

#[Route('/api/boutique')]
class ApiBoutiqueController extends ApiInterface
{



    #[Route('/', methods: ['GET'])]
    /**
     * Retourne la liste des boutiques.
     * 
     */
    #[OA\Response(
        response: 200,
        description: 'Returns the rewards of an user',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Boutique::class, groups: ['full']))
        )
    )]
    #[OA\Tag(name: 'boutique')]
    // #[Security(name: 'Bearer')]
    public function index(): Response {
        try {

            $boutiques = $this->findAllInEnvironment(ENTITY_TO_FIX::class);

            $response =  $this->responseData($boutiques, 'group1', ['Content-Type' => 'application/json']);
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
            items: new OA\Items(ref: new Model(type: Boutique::class, groups: ['full']))
        )
    )]
    #[OA\Tag(name: 'boutique')]
    // #[Security(name: 'Bearer')]
    public function indexAll(): Response {
        try {
            if ($this->getUser()->getType() == $this->getRepository(ENTITY_TO_FIX::class)->findOneByInEnvironment(['code' => 'SADM'])) {
                $boutiques = $this->findByInEnvironment(ENTITY_TO_FIX::class, 
                    ['entreprise' => $this->getUser()->getEntreprise(), 'active' => true],
                    ['id' => 'ASC']
                );
            } else {
                $boutiques = $this->findByInEnvironment(ENTITY_TO_FIX::class, 
                    ['surccursale' => $this->getUser()->getSurccursale(), 'active' => true],
                    ['id' => 'ASC']
                );
            }
            $response =  $this->responseData($boutiques, 'group1', ['Content-Type' => 'application/json']);
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
     * Affiche un(e) boutique en offrant un identifiant.
     */
    #[OA\Response(
        response: 200,
        description: 'Affiche un(e) boutique en offrant un identifiant',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Boutique::class, groups: ['full']))
        )
    )]
    #[OA\Parameter(
        name: 'code',
        in: 'query',
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Tag(name: 'boutique')]
    //#[Security(name: 'Bearer')]
    public function getOne(?Boutique $boutique)
    {
        try {
            if ($boutique) {
                $response = $this->response($boutique);
            } else {
                $this->setMessage('Cette ressource est inexistante');
                $this->setStatusCode(300);
                $response = $this->response($boutique);
            }
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            $this->setMessage($exception->getMessage());
            $response = $this->response([]);
        }


        return $response;
    }


    #[Route('/create', methods: ['POST'])]
    /**
     * Permet de créer un(e) boutique.
     */
    #[OA\Post(
        summary: "Authentification admin",
        description: "Génère un token JWT pour les administrateurs.",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "libelle", type: "string"),
                    new OA\Property(property: "situation", type: "string"),
                    new OA\Property(property: "contact", type: "string"),

                ],
                type: "object"
            )
        ),
        responses: [
            new OA\Response(response: 401, description: "Invalid credentials")
        ]
    )]
    #[OA\Tag(name: 'boutique')]
    public function create(): Response {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        $this->allParametres('boutique');
        $data = json_decode($request->getContent(), true);


        $boutique = new Boutique();
        $boutique->setLibelle($data['libelle']);
        $boutique->setSituation($data['situation']);
        $boutique->setContact($data['contact']);
        $boutique->setIsActive(true);

        $boutique->setCreatedBy($this->getUser());
        $boutique->setUpdatedBy($this->getUser());
        $errorResponse = $this->errorResponse($boutique);
        if ($errorResponse !== null) {
            return $errorResponse;
        } else {

            // Utiliser le trait pour sauvegarder dans le bon environnement
            $this->save($boutique);

            $caisse = new CaisseBoutique();
            $caisse->setMontant("0");
            $caisse->setBoutique($boutique);
            $caisse->setIsActive(true);
            $caisse->setReference($utils->generateReference('CAIS'));
            $caisse->setType(Caisse::TYPE['boutique']);
            $caisse->setEntreprise($this->getUser()->getEntreprise());
            $caisse->setCreatedBy($this->getUser());
            $caisse->setUpdatedBy($this->getUser());

            // Utiliser le trait pour sauvegarder dans le bon environnement
            $this->save($caisse);
        }

        return $this->responseData($boutique, 'group1', ['Content-Type' => 'application/json']);
    }

    #[Route('/update/{id}', methods: ['PUT', 'POST'])]
    #[OA\Post(
        summary: "Authentification admin",
        description: "Génère un token JWT pour les administrateurs.",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "libelle", type: "string"),
                    new OA\Property(property: "situation", type: "string"),
                    new OA\Property(property: "contact", type: "string"),

                ],
                type: "object"
            )
        ),
        responses: [
            new OA\Response(response: 401, description: "Invalid credentials")
        ]
    )]
    #[OA\Tag(name: 'boutique')]
    public function update(): Response {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        try {
            $data = json_decode($request->getContent());

            if ($boutique != null) {

                $boutique->setLibelle($data);
                $boutique->setSituation($data);
                $boutique->setContact($data);
                $boutique->setUpdatedBy($this->getUser());
                $boutique->setUpdatedAt(new \DateTime());
                $errorResponse = $this->errorResponse($boutique);

                if ($errorResponse !== null) {
                    return $errorResponse;
                } else {
                    // Utiliser le trait pour sauvegarder dans le bon environnement
            $this->save($boutique);
                }



                // On retourne la confirmation
                $response = $this->responseData($boutique, 'group1', ['Content-Type' => 'application/json']);
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
     * permet de supprimer un(e) boutique.
     */
    #[OA\Response(
        response: 200,
        description: 'permet de supprimer un(e) boutique',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Boutique::class, groups: ['full']))
        )
    )]
    #[OA\Tag(name: 'boutique')]
    //#[Security(name: 'Bearer')]
    public function delete(): Response {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        try {

            if ($boutique != null) {

                // Utiliser le trait pour supprimer dans le bon environnement
            $this->remove($boutique);

                // On retourne la confirmation
                $this->setMessage("Operation effectuées avec success");
                $response = $this->response($boutique);
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
     * Permet de supprimer plusieurs boutique.
     */
    #[OA\Response(
        response: 200,
        description: 'Returns the rewards of an user',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Boutique::class, groups: ['full']))
        )
    )]
    #[OA\Tag(name: 'boutique')]
    public function deleteAll(): Response {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        try {
            $data = json_decode($request->getContent());

            foreach ($data->ids as $key => $value) {
                $boutique = $this->findInEnvironment(ENTITY_TO_FIX::class, $value['id']);

                if ($boutique != null) {
                    $this->getRepository(ENTITY_TO_FIX::class)->remove($boutique);
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
