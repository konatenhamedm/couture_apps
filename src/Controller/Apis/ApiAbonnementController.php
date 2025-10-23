<?php

namespace  App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Entity\Abonnement;
use App\Entity\ModuleAbonnement;
use App\Repository\AbonnementRepository;
use App\Repository\FactureRepository;
use App\Repository\PaiementFactureRepository;
use App\Repository\UserRepository;
use App\Service\PaiementService;
use App\Service\Utils;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Annotation\Model as Model;
use Nelmio\ApiDocBundle\Attribute\Security;
use Nelmio\ApiDocBundle\Attribute\Model as AttributeModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

#[Route('/api/abonnement')]
class ApiAbonnementController extends ApiInterface
{

    #[Route('/', methods: ['GET'])]
    /**
     * Retourne la liste de tout les  abonnements.
     * 
     */
    #[OA\Response(
        response: 200,
        description: 'Returns the rewards of an user',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new AttributeModel(type: Abonnement::class, groups: ['full']))
        )
    )]
    #[OA\Tag(name: 'abonnement')]
    // #[Security(name: 'Bearer')]
    public function index(AbonnementRepository $moduleRepository): Response
    {
        try {

            $categories = $this->paginationService->paginate($moduleRepository->findAll());

            $response =  $this->responseData($categories, 'group1', ['Content-Type' => 'application/json']);
        } catch (\Exception $exception) {
            $this->setMessage("");
            $response = $this->response('[]');
        }

        // On envoie la réponse
        return $response;
    }
    #[Route('/info', methods: ['GET'])]
    /**
     * Retourne la liste des abonnements.
     * 
     */
    #[OA\Response(
        response: 200,
        description: 'Returns the rewards of an user',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new AttributeModel(type: Abonnement::class, groups: ['full']))
        )
    )]
    //#[OA\Tag(name: 'abonnement')]
    // #[Security(name: 'Bearer')]
    /*     public function showActiveInfoAbonnement(AbonnementRepository $moduleRepository): Response
    {
        try {

            $categories = $moduleRepository->findAll();

            $response =  $this->responseData($categories, 'group1', ['Content-Type' => 'application/json']);
        } catch (\Exception $exception) {
            $this->setMessage("");
            $response = $this->response('[]');
        }

        // On envoie la réponse
        return $response;
    } */

    #[Route('/abonnement/{id}',  methods: ['POST'])]
    /**
     * Permet de crtéer un(e) abonnement.
     */
    #[OA\Post(
        summary: "Permet de crtéer un(e) abonnement",
        description: "Permet de crtéer un(e) abonnement.",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: "dataUser",
                        type: "array",
                        items: new OA\Items(type: "integer") // ou "integer", "object", etc.
                    ),
                    new OA\Property(
                        property: "dataBoutique",
                        type: "array",
                        items: new OA\Items(type: "integer") // ou le type approprié
                    ),
                    new OA\Property(
                        property: "dataSuccursale",
                        type: "array",
                        items: new OA\Items(type: "integer") // ou le type approprié
                    ),

                    new OA\Property(property: "email", type: "string"),
                    new OA\Property(property: "entrepriseId", type: "string"),
                    new OA\Property(property: "numero", type: "string"),
                    new OA\Property(property: "operateur", type: "string"),
                ],
                type: "object"
            )
        ),
        responses: [
            new OA\Response(response: 401, description: "Invalid credentials")
        ]
    )]
    #[OA\Tag(name: 'abonnement')]
    public function createAbonnement(Request $request, UserRepository $userRepository, PaiementService $paiementService, AbonnementRepository $abonnementRepository, Utils $utils, ModuleAbonnement $moduleAbonnement, FactureRepository $factureRepository, PaiementFactureRepository $paiementRepository)
    {

        $data = json_decode($request->getContent(), true);
        /* dd($data); */
        $createTransactionData = $paiementService->traiterPaiement([
            'dataUser' => $data['dataUser'],
            'dataBoutique' => $data['dataBoutique'],
            'dataSuccursale' => $data['dataSuccursale'],
            'email' => $data['email'],
            'entrepriseId' => $data['entrepriseId'],
            'numero' => $data['numero'],
            'operateur' => $data['operateur'],
        ], $this->getUser(), $moduleAbonnement);
        // dd($createTransactionData);
        return   $this->response($createTransactionData);
    }



    #[Route('/entreprise', methods: ['GET'])]
    /**
     * Retourne la liste les abonnements d'une entreprise.
     * 
     */
    #[OA\Response(
        response: 200,
        description: 'Returns the rewards of an user',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new AttributeModel(type: Abonnement::class, groups: ['full']))
        )
    )]
    #[OA\Tag(name: 'abonnement')]
    // #[Security(name: 'Bearer')]
    public function indexAll(AbonnementRepository $abonnementRepository): Response
    {
        try {

            $typeMesures = $this->paginationService->paginate($abonnementRepository->findBy(
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

    #[Route('/entreprise/actif', methods: ['GET'])]
    /**
     * Retourne la liste des abonnements actifs d'une entreprise.
     * 
     */
    #[OA\Response(
        response: 200,
        description: 'Returns the rewards of an user',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new AttributeModel(type: Abonnement::class, groups: ['full']))
        )
    )]
    #[OA\Tag(name: 'abonnement')]
    // #[Security(name: 'Bearer')]
    public function indexActif(AbonnementRepository $moduleRepository): Response
    {
        try {

            $typeMesures = $this->paginationService->paginate($moduleRepository->findBy(
                ['entreprise' => $this->getUser()->getEntreprise(), 'etat' => 'actif'],
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
}
