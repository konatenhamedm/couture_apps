<?php

namespace  App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use App\DTO\PaiementFactureDTO;
use App\Entity\Abonnement;
use App\Entity\Boutique;
use App\Entity\CaisseSuccursale;
use App\Entity\Facture;
use App\Entity\Modele;
use App\Entity\ModuleAbonnement;
use App\Entity\Paiement;
use App\Entity\PaiementAbonnement;
use App\Entity\PaiementBoutique;
use App\Entity\PaiementBoutiqueLigne;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Entity\PaiementFacture;
use App\Repository\AbonnementRepository;
use App\Repository\BoutiqueRepository;
use App\Repository\CaisseBoutiqueRepository;
use App\Repository\CaisseSuccursaleRepository;
use App\Repository\ClientRepository;
use App\Repository\FactureRepository;
use App\Repository\ModeleBoutiqueRepository;
use App\Repository\PaiementBoutiqueLigneRepository;
use App\Repository\PaiementFactureRepository;
use App\Repository\TypeUserRepository;
use App\Repository\UserRepository;
use App\Service\PaiementService;
use App\Service\Utils;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Attribute\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

#[Route('/api/paiement')]
class ApiPaiementController extends ApiInterface
{

    #[Route('/', methods: ['GET'])]
    /**
     * Retourne la liste de tout les paiements.
     * 
     */
    #[OA\Response(
        response: 200,
        description: 'Returns the rewards of an user',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: PaiementFacture::class, groups: ['full']))
        )
    )]
    #[OA\Tag(name: 'paiement')]
    // #[Security(name: 'Bearer')]
    public function index(PaiementFactureRepository $paiementRepository): Response
    {
        try {

            $paiements = $paiementRepository->findAll();

            $response =  $this->responseData($paiements, 'group1', ['Content-Type' => 'application/json']);
        } catch (\Exception $exception) {
            $this->setMessage("");
            $response = $this->response('[]');
        }

        // On envoie la réponse
        return $response;
    }


    #[Route('/entreprise', methods: ['GET'])]
    /**
     * Retourne la liste des paiements d'une entreprise.
     * 
     */
    #[OA\Response(
        response: 200,
        description: 'Returns the rewards of an user',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: PaiementFacture::class, groups: ['full']))
        )
    )]
    #[OA\Tag(name: 'paiement')]
    // #[Security(name: 'Bearer')]
    public function indexAll(PaiementFactureRepository $paiementRepository, TypeUserRepository $typeUserRepository): Response
    {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        try {
            if ($this->getUser()->getType() == $typeUserRepository->findOneBy(['code' => 'SADM'])) {
                $paiements = $paiementRepository->findBy(
                    ['entreprise' => $this->getUser()->getEntreprise()],
                    ['id' => 'ASC']
                );
            } else {
                $paiements = $paiementRepository->findBy(
                    ['surccursale' => $this->getUser()->getSurccursale()],
                    ['id' => 'ASC']
                );
            }


            $response =  $this->responseDataWith_([
                'data' => $paiements,
                // 'inactiveSubscriptions' => $inactiveSubscriptions
            ], 'group1', ['Content-Type' => 'application/json']);
        } catch (\Exception $exception) {
            $this->setMessage("");
            $response = $this->response('[]');
        }

        // On envoie la réponse
        return $response;
    }




    #[Route('/get/one/{id}', methods: ['GET'])]
    /**
     * Affiche un(e) paiement en offrant un identifiant.
     */
    #[OA\Response(
        response: 200,
        description: 'Affiche un(e) paiement en offrant un identifiant',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: PaiementFacture::class, groups: ['full']))
        )
    )]
    #[OA\Parameter(
        name: 'code',
        in: 'query',
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Tag(name: 'paiement')]
    //#[Security(name: 'Bearer')]
    public function getOne(?PaiementFacture $paiement)
    {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        try {
            if ($paiement) {
                $response =  $this->responseData($paiement, 'group1', ['Content-Type' => 'application/json']);
            } else {
                $this->setMessage('Cette ressource est inexistante');
                $this->setStatusCode(300);
                $response = $this->response($paiement);
            }
        } catch (\Exception $exception) {
            $this->setMessage($exception->getMessage());
            $response = $this->response('[]');
        }


        return $response;
    }


    #[Route('/facture/{id}',  methods: ['POST'])]
    /**
     * Permet de créer un(e) paiement.
     */
    #[OA\Post(
        summary: "Permet de créer un(e) paiement",
        description: "Permet de créer un(e) paiement.",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "montant", type: "string"),
                    new OA\Property(property: "factureId", type: "string"),
                    new OA\Property(property: "succursaleId", type: "string"),

                ],
                type: "object"
            )
        ),
        responses: [
            new OA\Response(response: 401, description: "Invalid credentials")
        ]
    )]
    #[OA\Tag(name: 'paiement')]
    public function create(Request $request, UserRepository $userRepository, Utils $utils, CaisseSuccursaleRepository $caisseSuccursaleRepository, Facture $facture, FactureRepository $factureRepository, PaiementFactureRepository $paiementRepository): Response
    {

        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }
        $data = json_decode($request->getContent(), true);
        $admin = $userRepository->getUserByCodeType($this->getUser()->getEntreprise());

        $paiement = new PaiementFacture();
        $paiement->setMontant($data['montant']);
        $paiement->setFacture($facture);
        $paiement->setType(Paiement::TYPE["paiementFacture"]);
        $paiement->setReference($utils->generateReference('PMT'));
        $paiement->setCreatedBy($this->getUser());
        $paiement->setUpdatedBy($this->getUser());

        $facture->setResteArgent((int)$facture->getResteArgent() - (int)$data['montant']);

        $caisse = $data['succursaleId'] != null ? $caisseSuccursaleRepository->findOneBy(['surccursale' => $data['succursaleId']]) : $caisseSuccursaleRepository->findOneBy(['surccursale' => $this->getUser()->getSurccursale()]);

        $caisse->setMontant((int)$caisse->getMontant() + (int)$data['montant']);
        $caisse->setType('caisse_succursale');


        $errorResponse = $this->errorResponse($paiement);
        if ($errorResponse !== null) {
            return $errorResponse;
        } else {

            $paiementRepository->add($paiement, true);
            $factureRepository->add($facture, true);
            $caisseSuccursaleRepository->add($caisse, true);

            $this->sendMailService->sendNotification([
                'entreprise' => $this->getUser()->getEntreprise(),
                "user" => $admin,
                "libelle" => sprintf(
                    "Bonjour %s,\n\n" .
                        "Nous vous informons qu'un nouveau paiement vient d'être enregistrée dans le surccursale **%s**.\n\n" .
                        "- Montant : %s\n" .
                        "- Effectuée par : %s\n" .
                        "- Date : %s\n\n" .
                        "Cordialement,\nVotre application de gestion.",
                    $admin->getLogin(),
                    $this->getUser()->getSurccursale(),
                    $data['montant'] ?? "Non spécifié",
                    $this->getUser()->getNom() && $this->getUser()->getPrenoms() ? $this->getUser()->getNom() . " " . $this->getUser()->getPrenoms() : $this->getUser()->getLogin(),

                    (new \DateTime())->format('d/m/Y H:i')
                ),
                "titre" => "Paiemnet facture - " . $this->getUser()->getSurccursale(),

            ]);

            $this->sendMailService->send(
                $this->sendMail,
                $this->superAdmin,
                "Paiement facture - " . $this->getUser()->getEntreprise(),
                "paiement_email",
                [
                    "boutique_libelle" => $this->getUser()->getEntreprise(),
                    "montant" => $data['montant'],
                    "date" => (new \DateTime())->format('d/m/Y H:i'),
                ]
            );
        }

        return  $this->responseDataWith_([
            'data' => $facture,
            //'inactiveSubscriptions' => $inactiveSubscriptions
        ], 'group1', ['Content-Type' => 'application/json']);;
    }

    #[Route('/boutique/{id}',  methods: ['POST'])]
    /**
     * Permet de créer un(e) paiement simple dun modele d'une boutique. 
     */
    #[OA\Post(
        summary: "Permet de créer un(e) paiement simple dun modele d'une boutique",
        description: "Permet de créer un(e) paiement simple dun modele d'une boutique.",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "montant", type: "string"),
                    new OA\Property(property: "client", type: "string"),
                    new OA\Property(property: "modeleBoutiqueId", type: "string"),
                    new OA\Property(property: "quantite", type: "string"),

                ],
                type: "object"
            )
        ),
        responses: [
            new OA\Response(response: 401, description: "Invalid credentials")
        ]
    )]
    #[OA\Tag(name: 'paiement')]
    public function paiementBoutiqueModele(Request $request, ClientRepository $clientRepository, PaiementBoutiqueLigneRepository $paiementBoutiqueLigneRepository, Boutique $boutique, UserRepository $userRepository, Utils $utils, ModeleBoutiqueRepository $modeleBoutiqueRepository, CaisseBoutiqueRepository $caisseBoutiqueRepository, BoutiqueRepository $boutiqueRepository,  FactureRepository $factureRepository, PaiementFactureRepository $paiementRepository): Response
    {

        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }
        $admin = $userRepository->getUserByCodeType($this->getUser()->getEntreprise());
        $data = json_decode($request->getContent(), true);
        // $boutique = $boutiqueRepository->findOneBy(['id' => $data['boutiqueId']]);
        $paiement = new PaiementBoutique();
        $paiement->setMontant($data['montant']);
        $paiement->setClient($clientRepository->findOneBy(['id' => $data['client']]));
        $paiement->setType(Paiement::TYPE["paiementBoutique"]);
        $paiement->setBoutique($boutique);
        $paiement->setReference($utils->generateReference('PMT'));
        $paiement->setQuantite($data['quantite']);
        $paiement->setCreatedBy($this->getUser());
        $paiement->setUpdatedBy($this->getUser());

        $caisse =  $caisseBoutiqueRepository->findOneBy(['boutique' => $boutique->getId()]);

        $caisse->setMontant((int)$caisse->getMontant() + (int)$data['montant']);
       
        $errorResponse = $this->errorResponse($paiement);
        if ($errorResponse !== null) {
            return $errorResponse;
        } else {
            $ligne = new PaiementBoutiqueLigne();
            $ligne->setPaiementBoutique($paiement);
            $ligne->setModeleBoutique($modeleBoutiqueRepository->findOneBy(['id' => $data['modeleBoutiqueId']]));
            $ligne->setQuantite($data['quantite']);
            $ligne->setMontant($data['montant']);
            $paiementBoutiqueLigneRepository->add($ligne, true);


            $modeleBoutique = $modeleBoutiqueRepository->findOneBy(['id' => $data['modeleBoutiqueId']]);
            $modeleBoutique->setQuantite((int)$modeleBoutique->getQuantite() - (int)$data['quantite']);
            $modeleBoutiqueRepository->add($modeleBoutique, true);
            $paiementRepository->add($paiement, true);

            $caisseBoutiqueRepository->add($caisse, true);

            $this->sendMailService->sendNotification([
                'entreprise' => $this->getUser()->getEntreprise(),
                "user" => $admin,
                "libelle" => sprintf(
                    "Bonjour %s,\n\n" .
                        "Nous vous informons qu'une nouvelle vente vient d'être enregistrée dans la boutique **%s**.\n\n" .
                        "- Montant : %s\n" .
                        "- Effectuée par : %s\n" .
                        "- Date : %s\n\n" .
                        "Cordialement,\nVotre application de gestion.",
                    $admin->getLogin(),
                    $boutique->getLibelle(),
                    $data['montant'] ?? "Non spécifié",
                    $this->getUser()->getNom() && $this->getUser()->getPrenoms() ? $this->getUser()->getNom() . " " . $this->getUser()->getPrenoms() : $this->getUser()->getLogin(),
                    (new \DateTime())->format('d/m/Y H:i')
                ),
                "titre" => "Vente - " . $boutique->getLibelle(),

            ]);

            $this->sendMailService->send(
                $this->sendMail,
                $this->superAdmin,
                "Vente - " . $this->getUser()->getEntreprise(),
                "vente_email",
                [
                    "boutique_libelle" => $this->getUser()->getEntreprise(),
                    "montant" => $data['montant'],
                    "date" => (new \DateTime())->format('d/m/Y H:i'),
                ]
            );
        }

        return  $this->responseDataWith_([
            'data' => $paiement,
            /*  'inactiveSubscriptions' => $inactiveSubscriptions */
        ], 'group1', ['Content-Type' => 'application/json']);
    }



    #[Route('/boutique/multiple/{id}', methods: ['POST'])]
    /**
     * Permet de faire une sortie de stock avec ses lignes.
     */
    #[OA\Post(
        summary: "Permet de faire un(e) paiement dans une boutique avec ses lignes.",
        description: "Permet de faire un(e) paiement dans une boutique avec ses lignes.",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "client", type: "string"),

                    new OA\Property(
                        property: "lignes",
                        type: "array",
                        items: new OA\Items(
                            type: "object",
                            properties: [
                                new OA\Property(property: "montant", type: "string"),
                                new OA\Property(property: "modeleBoutiqueId", type: "string"),
                                new OA\Property(property: "quantite", type: "string"),
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
    #[OA\Tag(name: 'paiement')]
    public function paiementBoutiqueModeleSeveralLigne(Request $request, ClientRepository $clientRepository, PaiementBoutiqueLigneRepository $paiementBoutiqueLigneRepository, Boutique $boutique, UserRepository $userRepository, Utils $utils, ModeleBoutiqueRepository $modeleBoutiqueRepository, CaisseBoutiqueRepository $caisseBoutiqueRepository, BoutiqueRepository $boutiqueRepository,  FactureRepository $factureRepository, PaiementFactureRepository $paiementRepository): Response
    {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        $data = json_decode($request->getContent(), true);

        $admin = $userRepository->getUserByCodeType($this->getUser()->getEntreprise());

        $paiement = new PaiementBoutique();

        $paiement->setType(Paiement::TYPE["paiementBoutique"]);
        $paiement->setBoutique($boutique);
        $paiement->setReference($utils->generateReference('PMT'));
        $paiement->setClient($clientRepository->findOneBy(['id' => $data['client']]));
        $paiement->setCreatedBy($this->getUser());
        $paiement->setUpdatedBy($this->getUser());

        $caisse =  $caisseBoutiqueRepository->findOneBy(['boutique' => $boutique->getId()]);

        $sommeMontant = 0;
        $sommeQuantite = 0;


        foreach ($data['lignes'] as $ligne) {
            $ligne = new PaiementBoutiqueLigne();
            $ligne->setPaiementBoutique($paiement);
            $ligne->setModeleBoutique($modeleBoutiqueRepository->findOneBy(['id' => $ligne['modeleBoutiqueId']]));
            $ligne->setQuantite($ligne['quantite']);
            $ligne->setMontant($ligne['montant']);
            $sommeMontant += $ligne['montant'];
            $sommeQuantite += $ligne['quantite'];

            $modeleBoutique = $modeleBoutiqueRepository->findOneBy(['id' => $ligne['modeleBoutiqueId']]);
            $modeleBoutique->setQuantite((int)$modeleBoutique->getQuantite() - (int)$ligne['quantite']);
            $modeleBoutiqueRepository->add($modeleBoutique, true);
            $paiementBoutiqueLigneRepository->add($ligne, true);
        }

        $caisse->setMontant((int)$caisse->getMontant() + (int)$sommeMontant);
        $caisseBoutiqueRepository->add($caisse, true);

        $paiement->setMontant($sommeMontant);
        $paiement->setQuantite($sommeQuantite);
        $paiementRepository->add($paiement, true);

        $this->sendMailService->sendNotification([
            'entreprise' => $this->getUser()->getEntreprise(),
            "user" => $admin,
            "libelle" => sprintf(
                "Bonjour %s,\n\n" .
                    "Nous vous informons qu'une nouvelle vente vient d'être enregistrée dans la boutique **%s**.\n\n" .
                    "- Montant : %s\n" .
                    "- Effectuée par : %s\n" .
                    "- Date : %s\n\n" .
                    "Cordialement,\nVotre application de gestion.",
                $admin->getLogin(),
                $boutique->getLibelle(),
                $data['montant'] ?? "Non spécifié",
                $this->getUser()->getNom() && $this->getUser()->getPrenoms() ? $this->getUser()->getNom() . " " . $this->getUser()->getPrenoms() : $this->getUser()->getLogin(),
                (new \DateTime())->format('d/m/Y H:i')
            ),
            "titre" => "Vente - " . $boutique->getLibelle(),

        ]);

        $this->sendMailService->send(
            $this->sendMail,
            $this->superAdmin,
            "Vente - " . $this->getUser()->getEntreprise(),
            "vente_email",
            [
                "boutique_libelle" => $this->getUser()->getEntreprise(),
                "montant" => $data['montant'],
                "date" => (new \DateTime())->format('d/m/Y H:i'),
            ]
        );


        return $this->responseDataWith_([
            'data' => $paiement,
            /*  'inactiveSubscriptions' => $inactiveSubscriptions */
        ], 'group1', ['Content-Type' => 'application/json']);
    }

    #[Route('/webhook', name: 'webhook_paiement', methods: ['GET', 'POST'])]
    public function webHook(Request $request, PaiementService $paiementService): Response
    {

        /* $merchantId = $request->query->get('merchantId');
        $sessionId = $request->query->get('sessionId');
        $payId = $request->query->get('payId');
        $channel = $request->query->get('channel');
        $countryCurrencyCode = $request->query->get('countryCurrencyCode');
        $referenceNumber = $request->query->get('referenceNumber');
        $transactiondt = $request->query->get('transactiondt');
        $customerId = $request->query->get('customerId');
        $returnContext = $request->query->get('returnContext');
        $responsecode = $request->query->get('responsecode');
        $hashcode = $request->query->get('hashcode'); */

        $all = $request->query->all();

        $response = $paiementService->methodeWebHook($all);

        return $this->responseData(
            $response,
            'group1',
            ['Content-Type' => 'application/json']
        );
    }


    #[Route('/delete/{id}',  methods: ['DELETE'])]
    /**
     * permet de supprimer un(e) paiement.
     */
    #[OA\Response(
        response: 200,
        description: 'permet de supprimer un(e) paiement',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: PaiementFacture::class, groups: ['full']))
        )
    )]
    #[OA\Tag(name: 'paiement')]
    //#[Security(name: 'Bearer')]
    public function delete(Request $request, PaiementFacture $paiement, PaiementFactureRepository $villeRepository): Response
    {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        try {

            if ($paiement != null) {
                $villeRepository->remove($paiement, true);
                // On retourne la confirmation
                $this->setMessage("Operation effectuées avec success");
                $response = $this->response($paiement);
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
     * Permet de supprimer plusieurs paiement.
     */
    #[OA\Response(
        response: 200,
        description: 'Returns the rewards of an user',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: PaiementFacture::class, groups: ['full']))
        )
    )]
    #[OA\Tag(name: 'paiement')]
    public function deleteAll(Request $request, PaiementFactureRepository $villeRepository): Response
    {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        try {
            $data = json_decode($request->getContent());

            foreach ($data->ids as $key => $value) {
                $paiement = $villeRepository->find($value['id']);

                if ($paiement != null) {
                    $villeRepository->remove($paiement);
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
