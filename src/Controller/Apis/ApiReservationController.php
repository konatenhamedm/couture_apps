<?php

namespace  App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use App\DTO\ReservationDTO;
use App\Entity\Boutique;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Entity\Reservation;
use App\Entity\Caisse;
use App\Entity\CaisseBoutique;
use App\Entity\CaisseReservation;
use App\Entity\Client;
use App\Entity\LigneReservation;
use App\Entity\Paiement;
use App\Entity\PaiementReservation;
use App\Repository\BoutiqueRepository;
use App\Repository\CaisseBoutiqueRepository;
use App\Repository\CaisseRepository;
use App\Repository\ReservationRepository;
use App\Repository\CaisseReservationRepository;
use App\Repository\ClientRepository;
use App\Repository\ModeleBoutiqueRepository;
use App\Repository\ModeleRepository;
use App\Repository\PaiementReservationRepository;
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

#[Route('/api/reservation', name: 'api_reservation')]
class ApiReservationController extends ApiInterface
{



    #[Route('/', methods: ['GET'])]
    /**
     * Retourne la liste des reservations.
     * 
     */
    #[OA\Response(
        response: 200,
        description: 'Returns the rewards of an user',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Reservation::class, groups: ['full']))
        )
    )]
    #[OA\Tag(name: 'reservation')]
    // #[Security(name: 'Bearer')]
    public function index(ReservationRepository $reservationRepository): Response
    {
        try {

            $reservations = $this->paginationService->paginate($reservationRepository->findAll());

            $response =  $this->responseData($reservations, 'group1', ['Content-Type' => 'application/json']);
        } catch (\Exception $exception) {
            $this->setMessage("");
            $response = $this->response('[]');
        }

        // On envoie la réponse
        return $response;
    }


    #[Route('/entreprise', methods: ['GET'])]
    /**
     * Retourne la liste des reservations d'une entreprise.
     * 
     */
    #[OA\Response(
        response: 200,
        description: 'Returns the rewards of an user',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Reservation::class, groups: ['full']))
        )
    )]
    #[OA\Tag(name: 'reservation')]
    // #[Security(name: 'Bearer')]
    public function indexAll(ReservationRepository $reservationRepository, TypeUserRepository $typeUserRepository): Response
    {
        try {
            if ($this->getUser()->getType() == $typeUserRepository->findOneBy(['code' => 'SADM'])) {
                $reservations = $this->paginationService->paginate($reservationRepository->findBy(
                    ['entreprise' => $this->getUser()->getEntreprise()],
                    ['id' => 'ASC']
                ));
            } else {
                $reservations = $this->paginationService->paginate($reservationRepository->findBy(
                    ['boutique' => $this->getUser()->getBoutique()],
                    ['id' => 'ASC']
                ));
            }
            $response =  $this->responseData($reservations, 'group1', ['Content-Type' => 'application/json']);
        } catch (\Exception $exception) {
            $this->setMessage("");
            $response = $this->response('[]');
        }

        // On envoie la réponse
        return $response;
    }


    #[Route('/get/one/{id}', methods: ['GET'])]
    /**
     * Affiche un(e) reservation en offrant un identifiant.
     */
    #[OA\Response(
        response: 200,
        description: 'Affiche un(e) reservation en offrant un identifiant',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Reservation::class, groups: ['full']))
        )
    )]
    #[OA\Parameter(
        name: 'code',
        in: 'query',
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Tag(name: 'reservation')]
    //#[Security(name: 'Bearer')]
    public function getOne(?Reservation $reservation)
    {
        try {
            if ($reservation) {
                $response = $this->response($reservation);
            } else {
                $this->setMessage('Cette ressource est inexistante');
                $this->setStatusCode(300);
                $response = $this->response($reservation);
            }
        } catch (\Exception $exception) {
            $this->setMessage($exception->getMessage());
            $response = $this->response('[]');
        }


        return $response;
    }


    #[Route('/create', methods: ['POST'])]
    /**
     * Permet de créer un(e) reservation.
     */
    #[OA\Post(
        summary: "Permet de créer un(e) reservation",
        description: "Permet de créer un(e) reservation.",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "avance", type: "string"),
                    new OA\Property(property: "dateRetrait", type: "string"),
                    new OA\Property(property: "client", type: "string"),
                    new OA\Property(property: "boutique", type: "string"),
                    new OA\Property(property: "entreprise", type: "string"),
                    new OA\Property(property: "montant", type: "string"),

                    new OA\Property(
                        property: "ligne",
                        type: "array",
                        items: new OA\Items(
                            type: "object",
                            properties: [
                                new OA\Property(property: "modele", type: "string"),
                                new OA\Property(property: "quantite", type: "string"),

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
    #[OA\Tag(name: 'reservation')]
    public function create(Request $request,ModeleBoutiqueRepository $modeleBoutiqueRepository, CaisseBoutiqueRepository $caisseBoutiqueRepository, PaiementReservationRepository $paiementReservationRepository, ModeleRepository $modeleRepository, ClientRepository $clientRepository, BoutiqueRepository $boutiqueRepository, Utils $utils, ReservationRepository $reservationRepository): Response
    {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }
        $data = json_decode($request->getContent(), true);


        $reservation = new Reservation();
        $reservation->setAvance($data['avance']);
        $reservation->setDateRetrait(new \DateTime($data['dateRetrait']));
        $reservation->setClient($clientRepository->findOneBy(['id' => $data['client']]));
        $reservation->setBoutique($boutiqueRepository->findOneBy(['id' => $data['boutique']]));
        $reservation->setEntreprise($this->getUser()->getEntreprise());
        $reservation->setMontant($data['montant']);
        $reservation->setReste($data['reste']);

        $reservation->setCreatedAtValue(new \DateTime());
        $reservation->setCreatedBy($this->getUser());
        $reservation->setUpdatedBy($this->getUser());

        foreach ($data['ligne'] as $key => $value) {
            $ligne = new LigneReservation();
            $ligne->setQuantite($value['quantite']);
            $ligne->setModele($modeleBoutiqueRepository->findOneBy(['id' => $value['modele']]));
            $ligne->setCreatedAtValue(new \DateTime());
            $ligne->setCreatedBy($this->getUser());
            $ligne->setUpdatedBy($this->getUser());
            $reservation->addLigneReservation($ligne);
        }
        $errorResponse = $this->errorResponse($reservation);
        if ($errorResponse !== null) {
            return $errorResponse; 
        } else {


            $paiementReservation = new PaiementReservation();
            $paiementReservation->setReservation($reservation);
            $paiementReservation->setType(Paiement::TYPE["paiementReservation"]);
            $paiementReservation->setMontant($data['avance'] ?? null);
            $paiementReservation->setReference($utils->generateReference('PMT'));
            $paiementReservation->setCreatedAtValue(new \DateTime());
            $paiementReservation->setCreatedBy($this->getUser());
            $paiementReservation->setUpdatedBy($this->getUser());

            $paiementReservationRepository->add($paiementReservation, true);



            $caisseBoutique = $caisseBoutiqueRepository->find($data['boutique']);
            $caisseBoutique->setMontant((int)$caisseBoutique->getMontant() + (int)$reservation->getMontant());
            $caisseBoutique->setUpdatedBy($this->getUser());
            $caisseBoutiqueRepository->add($caisseBoutique, true);


            $reservationRepository->add($reservation, true);
        }

        return $this->responseData($reservation, 'group1', ['Content-Type' => 'application/json']);
    }

    #[Route('/update/{id}', methods: ['PUT', 'POST'])]
    #[OA\Put(
        path: "/api/reservations/{id}",
        summary: "Met à jour une réservation",
        description: "Met à jour les données d'une réservation existante.",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(property: "avance", type: "string"),
                    new OA\Property(property: "reste", type: "string"),
                    new OA\Property(property: "dateRetrait", type: "string", format: "date-time"),
                    new OA\Property(property: "client", type: "string"),
                    new OA\Property(property: "boutique", type: "string"),
                    new OA\Property(property: "montant", type: "string"),
                    new OA\Property(
                        property: "ligne",
                        type: "array",
                        items: new OA\Items(
                            type: "object",
                            properties: [
                                new OA\Property(property: "modele", type: "string"),
                                new OA\Property(property: "quantite", type: "string")
                            ]
                        )
                    )
                ]
            )
        ),
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "ID de la réservation à mettre à jour",
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(response: 200, description: "Réservation mise à jour"),
            new OA\Response(response: 300, description: "Ressource inexistante"),
            new OA\Response(response: 400, description: "Données invalides"),
            new OA\Response(response: 401, description: "Non autorisé")
        ]
    )]
    #[OA\Tag(name: 'reservation')]
    public function update(
        Request $request,
        Reservation $reservation,
        ReservationRepository $reservationRepository,
        ClientRepository $clientRepository,
        BoutiqueRepository $boutiqueRepository,
        CaisseBoutiqueRepository $caisseBoutiqueRepository,
        ModeleRepository $modeleRepository,
        ModeleBoutiqueRepository $modeleBoutiqueRepository,
        PaiementReservationRepository $paiementReservationRepository,
        Utils $utils
    ): Response {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        try {
            $data = json_decode($request->getContent(), true);

            if ($reservation) {
                $reservation->setAvance($data['avance'] ?? null);
                $reservation->setReste($data['reste'] ?? null);
                $reservation->setDateRetrait(new \DateTime($data['dateRetrait'] ?? 'now'));
                $reservation->setClient($clientRepository->find($data['client']));
                $reservation->setBoutique($boutiqueRepository->find($data['boutique']));
                $reservation->setMontant($data['montant'] ?? null);
                $reservation->setUpdatedBy($this->getUser());
                $reservation->setUpdatedAt(new \DateTime());

                // Supprimer les anciennes lignes
                foreach ($reservation->getLigneReservations() as $ligne) {
                    $reservation->removeLigneReservation($ligne);
                }

                // Ajouter les nouvelles lignes
                foreach ($data['ligne'] as $value) {
                    $ligne = new LigneReservation();
                    $ligne->setQuantite($value['quantite']);
                    $ligne->setModele($modeleBoutiqueRepository->find($value['modele']));
                    $ligne->setCreatedAtValue(new \DateTime());
                    $ligne->setCreatedBy($this->getUser());
                    $ligne->setUpdatedBy($this->getUser());
                    $reservation->addLigneReservation($ligne);
                }

                $errorResponse = $this->errorResponse($reservation);
                if ($errorResponse !== null) {
                    return $errorResponse;
                }
                $reservationRepository->add($reservation, true);

                $paiementReservation = $paiementReservationRepository->findOneBy(['reservation' => $reservation]);
                $paiementReservation->setReservation($reservation);
                $paiementReservation->setType(Paiement::TYPE["paiementReservation"]);
                $paiementReservation->setMontant($data['montant'] ?? null);
                $paiementReservation->setReference($utils->generateReference('PMT'));
                $paiementReservation->setCreatedAtValue(new \DateTime());
                $paiementReservation->setCreatedBy($this->getUser());
                $paiementReservation->setUpdatedBy($this->getUser());

                $paiementReservationRepository->add($paiementReservation, true);

                $caisseBoutique = $caisseBoutiqueRepository->find($data['boutique']);
                $caisseBoutique->setMontant((int)$caisseBoutique->getMontant() + (int)$reservation->getMontant());
                $caisseBoutique->setUpdatedBy($this->getUser());
                $caisseBoutiqueRepository->add($caisseBoutique, true);


                $response = $this->responseData($reservation, 'group1', ['Content-Type' => 'application/json']);
            } else {
                $this->setMessage("Cette ressource est inexistante");
                $this->setStatusCode(300);
                $response = $this->response('[]');
            }
        } catch (\Exception $e) {
            $this->setMessage("Erreur inattendue");
            $response = $this->response('[]');
        }

        return $response;
    }


    //const TAB_ID = 'parametre-tabs';

    #[Route('/delete/{id}',  methods: ['DELETE'])]
    /**
     * permet de supprimer un(e) reservation.
     */
    #[OA\Response(
        response: 200,
        description: 'permet de supprimer un(e) reservation',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Reservation::class, groups: ['full']))
        )
    )]
    #[OA\Tag(name: 'reservation')]
    //#[Security(name: 'Bearer')]
    public function delete(Request $request, Reservation $reservation, ReservationRepository $villeRepository): Response
    {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        try {

            if ($reservation != null) {

                $villeRepository->remove($reservation, true);

                // On retourne la confirmation
                $this->setMessage("Operation effectuées avec success");
                $response = $this->response($reservation);
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
     * Permet de supprimer plusieurs reservation.
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
    #[OA\Tag(name: 'reservation')]
    public function deleteAll(Request $request, ReservationRepository $villeRepository): Response
    {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        try {
            $data = json_decode($request->getContent());

            foreach ($data['ids'] as $id) {
                $reservation = $villeRepository->find($id);

                if ($reservation != null) {
                    $villeRepository->remove($reservation);
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
