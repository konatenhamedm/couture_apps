<?php

namespace  App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use App\Entity\Boutique;
use App\Entity\EntreStock;
use App\Entity\LigneEntre;
use App\Entity\ModeleBoutique;
use App\Repository\BoutiqueRepository;
use App\Repository\EntreStockRepository;
use App\Repository\LigneEntreRepository;
use App\Repository\ModeleBoutiqueRepository;
use App\Repository\ModeleRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Attribute\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

#[Route('/api/stock')]
class ApiGestionStockController extends ApiInterface
{

    #[Route('/{id}', methods: ['GET'])]
    /**
     * Retourne la liste des entreés et sorties de stock d'une boutique.
     * 
     */
    #[OA\Response(
        response: 200,
        description: "Retourne la liste des entreés et sorties de stock d'une boutique",
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: EntreStock::class, groups: ['full']))
        )
    )]
    #[OA\Tag(name: 'modele')]
    // #[Security(name: 'Bearer')]
    public function index(ModeleRepository $modeleRepository, EntreStockRepository $entreStockRepository, Boutique $boutique): Response
    {
        try {
            $entrees = $this->paginationService->paginate($entreStockRepository->findBy(
                ['boutique' => $boutique->getId()],
                ['id' => 'ASC']
            ));

            $response =  $this->responseData($entrees, 'group1', ['Content-Type' => 'application/json']);
        } catch (\Exception $exception) {
            $this->setMessage("");
            $response = $this->response('[]');
        }

        // On envoie la réponse
        return $response;
    }

    #[Route('/modeleBoutique/{id}', methods: ['GET'])]
    /**
     * Retourne la liste des lignes entrées et sorties de stock d'un modeleBoutique.
     * 
     */
    #[OA\Response(
        response: 200,
        description: 'Returns the rewards of an user',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: LigneEntre::class, groups: ['full']))
        )
    )]
    #[OA\Tag(name: 'stock')]
    // #[Security(name: 'Bearer')]
    public function indexModeleBoutique(ModeleRepository $modeleRepository, LigneEntreRepository $ligneEntreRepository,  ModeleBoutique $modeleBoutique): Response
    {
        try {


            $entrees =  $this->paginationService->paginate($ligneEntreRepository->findBy(
                ['modele' => $modeleBoutique->getId()],
                ['id' => 'ASC']
            ));

            $response =  $this->responseData($entrees, 'group_ligne', ['Content-Type' => 'application/json']);
        } catch (\Exception $exception) {
            $this->setMessage("");
            $response = $this->response('[]');
        }

        // On envoie la réponse
        return $response;
    }


    #[Route('/entree',  methods: ['POST'])]
    /**
     * Permet de faire un(e) entree de stock avec ses lignes.
     */
    #[OA\Post(
        summary: "Permet de faire un(e) entree de stock avec ses lignes",
        description: "Permet de faire un(e) entree de stock avec ses lignes.",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "boutiqueId", type: "string"),
                    new OA\Property(
                        property: "lignes",
                        type: "array",
                        items: new OA\Items(
                            type: "object",
                            properties: [
                                new OA\Property(property: "quantite", type: "string"),
                                new OA\Property(property: "modeleBoutiqueId", type: "string"),
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
    #[OA\Tag(name: 'stock')]
    public function create(Request $request, LigneEntreRepository $ligneEntreRepository, ModeleRepository $modeleRepository, BoutiqueRepository $boutiqueRepository, EntreStockRepository $entreStockRepository, ModeleBoutiqueRepository $modeleBoutiqueRepository): Response
    {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        $totalQantite = 0;

        $data = json_decode($request->getContent(), true);
        $entreStock = new EntreStock();
        $entreStock->setBoutique($boutiqueRepository->find($data['boutiqueId']));
        $entreStock->setType('Entree');
        $entreStock->setEntreprise($this->getUser()->getEntreprise());
        $entreStock->setCreatedBy($this->getUser());
        $entreStock->setUpdatedBy($this->getUser());
        $errorResponse = $this->errorResponse($entreStock);
        $lignes = $data['lignes'];

        if (isset($lignes) && is_array($lignes)) {
            foreach ($lignes as $ligne) {
                $modeleBoutique = $modeleBoutiqueRepository->find($ligne['modeleBoutiqueId']);
                $modele = $modeleRepository->find($modeleBoutique->getModele()->getId());
                $totalQantite += (int)$ligne['quantite'];
                $ligneEntre = new LigneEntre();
                $ligneEntre->setQuantite($ligne['quantite']);
                $ligneEntre->setModele($modeleBoutique);
                $errorResponse = $this->errorResponse($ligneEntre);
                $totalQantite += $ligne['quantite'];
                $ligneEntreRepository->add($ligneEntre, true);
                $modeleBoutique->setQuantite($modeleBoutique->getQuantite() + (int)$ligne['quantite']);
                $modeleBoutiqueRepository->add($modeleBoutique, true);

                $modele->setQuantiteGlobale($modele->getQuantiteGlobale() + (int)$ligne['quantite']);
                $modeleRepository->add($modele, true);
            }
        }

        if ($errorResponse !== null) {
            return $errorResponse;
        } else {
            $entreStock->setQuantite($totalQantite);
            $entreStockRepository->add($entreStock, true);
        }

        return $this->responseData($entreStock, 'group1', ['Content-Type' => 'application/json']);
    }

    #[Route('/entree/{id}', methods: ['PUT'])]
    /**
     * Permet de mettre à jour une entrée de stock avec ses lignes.
     */
    #[OA\Put(
        summary: "Permet de mettre à jour une entrée de stock avec ses lignes",
        description: "Met à jour une entrée de stock existante et ses lignes associées.",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "boutiqueId", type: "string"),
                    new OA\Property(
                        property: "lignes",
                        type: "array",
                        items: new OA\Items(
                            type: "object",
                            properties: [
                                new OA\Property(property: "quantite", type: "string"),
                                new OA\Property(property: "modeleBoutiqueId", type: "string"),
                            ]
                        ),
                    ),
                ],
                type: "object"
            )
        ),
        responses: [
            new OA\Response(response: 401, description: "Invalid credentials"),
            new OA\Response(response: 404, description: "Entrée introuvable")
        ]
    )]
    #[OA\Tag(name: 'stock')]
    public function update(
        int $id,
        Request $request,
        ModeleRepository $modeleRepository,
        LigneEntreRepository $ligneEntreRepository,
        BoutiqueRepository $boutiqueRepository,
        EntreStockRepository $entreStockRepository,
        ModeleBoutiqueRepository $modeleBoutiqueRepository
    ): Response {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        $data = json_decode($request->getContent(), true);

        $entreStock = $entreStockRepository->find($id);
        if (!$entreStock) {
            return $this->json(['status' => 'ERROR', 'message' => 'Entrée introuvable'], 404);
        }
        $totalQuantite = 0;
        if (isset($data['boutiqueId'])) {
            $entreStock->setBoutique($boutiqueRepository->find($data['boutiqueId']));
        }

        $entreStock->setUpdatedBy($this->getUser());
        $entreStock->setUpdatedAt(new \DateTimeImmutable());

        foreach ($entreStock->getLigneEntres() as $oldLigne) {
            $entreStock->removeLigneEntre($oldLigne);
        }
        if (isset($data['lignes']) && is_array($data['lignes'])) {
            foreach ($data['lignes'] as $ligne) {
                $modeleBoutique = $modeleBoutiqueRepository->find($ligne['modeleBoutiqueId']);
                $modele = $modeleRepository->find($modeleBoutique->getModele()->getId());

                if (!$modeleBoutique) {
                    return $this->json(['status' => 'ERROR', 'message' => 'Modèle introuvable'], 400);
                }

                $quantite = (int)$ligne['quantite'];
                $totalQuantite += $quantite;

                $ligneEntre = new LigneEntre();
                $ligneEntre->setQuantite($quantite);
                $ligneEntre->setModele($modeleBoutique);

                $ligneEntreRepository->add($ligneEntre, true);
                $modeleBoutique->setQuantite($modeleBoutique->getQuantite() + $quantite);
                $modeleBoutiqueRepository->add($modeleBoutique, true);

                $modele->setQuantiteGlobale($modele->getQuantiteGlobale() + (int)$ligne['quantite']);
                $modeleRepository->add($modele, true);
            }
        }

        $entreStock->setQuantite($totalQuantite);

        $errorResponse = $this->errorResponse($entreStock);
        if ($errorResponse !== null) {
            return $errorResponse;
        }
        $entreStockRepository->add($entreStock, true);

        return $this->responseData($entreStock, 'group1', ['Content-Type' => 'application/json']);
    }

    #[Route('/sortie', methods: ['POST'])]
    /**
     * Permet de faire une sortie de stock avec ses lignes.
     */
    #[OA\Post(
        summary: "Permet de faire une sortie de stock avec ses lignes",
        description: "Permet de faire une sortie de stock avec ses lignes.",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "boutiqueId", type: "string"),
                    new OA\Property(
                        property: "lignes",
                        type: "array",
                        items: new OA\Items(
                            type: "object",
                            properties: [
                                new OA\Property(property: "quantite", type: "string"),
                                new OA\Property(property: "modeleBoutiqueId", type: "string"),
                            ]
                        ),
                    ),
                ],
                type: "object"
            )
        ),
        responses: [
            new OA\Response(response: 401, description: "Invalid credentials"),
            new OA\Response(response: 400, description: "Stock insuffisant")
        ]
    )]
    #[OA\Tag(name: 'stock')]
    public function sortie(
        Request $request,
        ModeleRepository $modeleRepository,
        LigneEntreRepository $ligneEntreRepository,
        BoutiqueRepository $boutiqueRepository,
        EntreStockRepository $entreStockRepository,
        ModeleBoutiqueRepository $modeleBoutiqueRepository
    ): Response {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        $data = json_decode($request->getContent(), true);
        $totalQuantite = 0;

        $entreStock = new EntreStock();
        $entreStock->setBoutique($boutiqueRepository->find($data['boutiqueId']));
        $entreStock->setType('Sortie');
        $entreStock->setEntreprise($this->getUser()->getEntreprise());
        $entreStock->setCreatedBy($this->getUser());
        $entreStock->setUpdatedBy($this->getUser());

        $errorResponse = $this->errorResponse($entreStock);
        if ($errorResponse !== null) {
            return $errorResponse;
        }

        $lignes = $data['lignes'] ?? [];
        if (is_array($lignes)) {
            foreach ($lignes as $ligne) {
                $modeleBoutique = $modeleBoutiqueRepository->find($ligne['modeleBoutiqueId']);
                $modele = $modeleRepository->find($modeleBoutique->getModele()->getId());

                if (!$modeleBoutique) {
                    return $this->json(['status' => 'ERROR', 'message' => 'Modèle introuvable'], 400);
                }

                $quantite = (int)$ligne['quantite'];

                if ($modeleBoutique->getQuantite() < $quantite) {
                    return $this->json([
                        'status' => 'ERROR',
                        'message' => "Stock insuffisant pour le modèle ID {$modeleBoutique->getId()} (dispo: {$modeleBoutique->getQuantite()}, demandé: $quantite)"
                    ], 400);
                }

                $modeleBoutique->setQuantite($modeleBoutique->getQuantite() - $quantite);
                $totalQuantite += $quantite;

                $ligneEntre = new LigneEntre();
                $ligneEntre->setQuantite($quantite);
                $ligneEntre->setModele($modeleBoutique);

                $ligneEntreRepository->add($ligneEntre, true);
                $modeleBoutiqueRepository->add($modeleBoutique, true);

                if ($modele->getQuantiteGlobale() >= (int)$ligne['quantite'])
                    $modele->setQuantiteGlobale($modele->getQuantiteGlobale() - (int)$ligne['quantite']);

                $modeleRepository->add($modele, true);
            }
        }

        $entreStock->setQuantite($totalQuantite);

        $entreStockRepository->add($entreStock, true);

        return $this->responseData($entreStock, 'group1', ['Content-Type' => 'application/json']);
    }
}
