<?php

namespace App\Controller\Apis;

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

/**
 * Contrôleur pour la gestion des réservations de vêtements
 * Permet aux clients de réserver des articles avec acompte et retrait ultérieur
 */
#[Route('/api/reservation', name: 'api_reservation')]
#[OA\Tag(name: 'reservation', description: 'Gestion des réservations de vêtements avec acomptes et retraits programmés')]
class ApiReservationController extends ApiInterface
{
    /**
     * Liste toutes les réservations du système
     */
    #[Route('/', methods: ['GET'])]
    #[OA\Get(
        path: "/api/reservation/",
        summary: "Lister toutes les réservations",
        description: "Retourne la liste paginée de toutes les réservations du système, incluant les détails des clients, montants, acomptes et dates de retrait.",
        tags: ['reservation']
    )]
    #[OA\Response(
        response: 200,
        description: "Liste des réservations récupérée avec succès",
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(
                type: "object",
                properties: [
                    new OA\Property(property: "id", type: "integer", example: 1, description: "Identifiant unique de la réservation"),
                    new OA\Property(property: "montant", type: "number", format: "float", example: 50000, description: "Montant total de la réservation en FCFA"),
                    new OA\Property(property: "avance", type: "number", format: "float", example: 20000, description: "Acompte versé en FCFA"),
                    new OA\Property(property: "reste", type: "number", format: "float", example: 30000, description: "Reste à payer en FCFA"),
                    new OA\Property(property: "dateRetrait", type: "string", format: "date-time", example: "2025-02-15T10:00:00+00:00", description: "Date prévue de retrait"),
                    new OA\Property(
                        property: "client",
                        type: "object",
                        description: "Client ayant effectué la réservation",
                        properties: [
                            new OA\Property(property: "id", type: "integer", example: 5),
                            new OA\Property(property: "nom", type: "string", example: "Kouassi"),
                            new OA\Property(property: "prenoms", type: "string", example: "Jean"),
                            new OA\Property(property: "telephone", type: "string", example: "+225 07 12 34 56 78")
                        ]
                    ),
                    new OA\Property(property: "boutique", type: "object", description: "Boutique où récupérer la réservation"),
                    new OA\Property(
                        property: "ligneReservations",
                        type: "array",
                        description: "Liste des articles réservés",
                        items: new OA\Items(
                            type: "object",
                            properties: [
                                new OA\Property(property: "id", type: "integer", example: 1),
                                new OA\Property(property: "quantite", type: "integer", example: 2),
                                new OA\Property(property: "modele", type: "object", description: "Modèle réservé")
                            ]
                        )
                    ),
                    new OA\Property(property: "createdAt", type: "string", format: "date-time", example: "2025-01-30T14:30:00+00:00")
                ]
            )
        )
    )]
    #[OA\Response(response: 500, description: "Erreur serveur lors de la récupération")]
    public function index(ReservationRepository $reservationRepository): Response
    {
        try {
            $reservations = $this->paginationService->paginate($reservationRepository->findAll());
            $response = $this->responseData($reservations, 'group1', ['Content-Type' => 'application/json']);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            $this->setMessage("Erreur lors de la récupération des réservations");
            $response = $this->response([]);
        }

        return $response;
    }

    /**
     * Liste les réservations selon les droits de l'utilisateur (entreprise ou boutique)
     */
    #[Route('/entreprise', methods: ['GET'])]
    #[OA\Get(
        path: "/api/reservation/entreprise",
        summary: "Lister les réservations selon les droits utilisateur",
        description: "Retourne la liste des réservations filtrée selon le type d'utilisateur : Super-admin voit toutes les réservations de l'entreprise, autres utilisateurs voient uniquement les réservations de leur boutique.",
        tags: ['reservation']
    )]
    #[OA\Response(
        response: 200,
        description: "Liste des réservations récupérée avec succès",
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(
                type: "object",
                properties: [
                    new OA\Property(property: "id", type: "integer", example: 1),
                    new OA\Property(property: "montant", type: "number", example: 50000),
                    new OA\Property(property: "avance", type: "number", example: 20000),
                    new OA\Property(property: "reste", type: "number", example: 30000),
                    new OA\Property(property: "dateRetrait", type: "string", format: "date-time"),
                    new OA\Property(property: "client", type: "object"),
                    new OA\Property(property: "boutique", type: "object"),
                    new OA\Property(property: "entreprise", type: "object")
                ]
            )
        )
    )]
    #[OA\Response(response: 401, description: "Non authentifié")]
    #[OA\Response(response: 500, description: "Erreur lors de la récupération")]
    public function indexAll(ReservationRepository $reservationRepository, TypeUserRepository $typeUserRepository): Response
    {
        try {
            if ($this->getUser()->getType() == $typeUserRepository->findOneBy(['code' => 'SADM'])) {
                $reservations = $this->paginationService->paginate($reservationRepository->findBy(
                    ['entreprise' => $this->getUser()->getEntreprise()],
                    ['id' => 'DESC']
                ));
            } else {
                $reservations = $this->paginationService->paginate($reservationRepository->findBy(
                    ['boutique' => $this->getUser()->getBoutique()],
                    ['id' => 'DESC']
                ));
            }
            $response = $this->responseData($reservations, 'group1', ['Content-Type' => 'application/json']);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            $this->setMessage("Erreur lors de la récupération des réservations");
            $response = $this->response([]);
        }

        return $response;
    }

    /**
     * Récupère les détails d'une réservation spécifique
     */
    #[Route('/get/one/{id}', methods: ['GET'])]
    #[OA\Get(
        path: "/api/reservation/get/one/{id}",
        summary: "Détails d'une réservation",
        description: "Affiche les informations détaillées d'une réservation spécifique, incluant tous les articles réservés, les montants (total, acompte, reste), la date de retrait et les informations du client.",
        tags: ['reservation']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: "Identifiant unique de la réservation",
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: "Réservation trouvée avec succès",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "id", type: "integer", example: 1),
                new OA\Property(property: "montant", type: "number", format: "float", example: 50000, description: "Montant total"),
                new OA\Property(property: "avance", type: "number", format: "float", example: 20000, description: "Acompte versé"),
                new OA\Property(property: "reste", type: "number", format: "float", example: 30000, description: "Reste à payer lors du retrait"),
                new OA\Property(property: "dateRetrait", type: "string", format: "date-time", example: "2025-02-15T10:00:00+00:00"),
                new OA\Property(property: "client", type: "object", description: "Informations complètes du client"),
                new OA\Property(property: "boutique", type: "object", description: "Boutique de retrait"),
                new OA\Property(property: "entreprise", type: "object"),
                new OA\Property(
                    property: "ligneReservations",
                    type: "array",
                    description: "Détail de tous les articles réservés",
                    items: new OA\Items(type: "object")
                ),
                new OA\Property(property: "paiements", type: "array", description: "Liste des paiements effectués", items: new OA\Items(type: "object")),
                new OA\Property(property: "createdAt", type: "string", format: "date-time"),
                new OA\Property(property: "updatedAt", type: "string", format: "date-time")
            ]
        )
    )]
    #[OA\Response(response: 404, description: "Réservation non trouvée")]
    public function getOne(?Reservation $reservation): Response
    {
        try {
            if ($reservation) {
                $response = $this->response($reservation);
            } else {
                $this->setMessage('Cette ressource est inexistante');
                $this->setStatusCode(404);
                $response = $this->response(null);
            }
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            $this->setMessage($exception->getMessage());
            $response = $this->response([]);
        }

        return $response;
    }

    /**
     * Crée une nouvelle réservation avec acompte
     */
    #[Route('/create', methods: ['POST'])]
    #[OA\Post(
        path: "/api/reservation/create",
        summary: "Créer une réservation",
        description: "Permet de créer une nouvelle réservation de vêtements avec un acompte. Enregistre automatiquement le paiement de l'acompte, met à jour la caisse de la boutique, et programme la date de retrait. Nécessite un abonnement actif.",
        tags: ['reservation']
    )]
    #[OA\RequestBody(
        required: true,
        description: "Données de la réservation à créer",
        content: new OA\JsonContent(
            type: "object",
            required: ["montant", "avance", "reste", "dateRetrait", "client", "boutique", "ligne"],
            properties: [
                new OA\Property(
                    property: "montant",
                    type: "number",
                    format: "float",
                    example: 50000,
                    description: "Montant total de la réservation en FCFA (obligatoire)"
                ),
                new OA\Property(
                    property: "avance",
                    type: "number",
                    format: "float",
                    example: 20000,
                    description: "Montant de l'acompte versé en FCFA (obligatoire, généralement 30-50% du total)"
                ),
                new OA\Property(
                    property: "reste",
                    type: "number",
                    format: "float",
                    example: 30000,
                    description: "Reste à payer lors du retrait en FCFA (obligatoire, = montant - avance)"
                ),
                new OA\Property(
                    property: "dateRetrait",
                    type: "string",
                    format: "date-time",
                    example: "2025-02-15T10:00:00",
                    description: "Date prévue de retrait des articles (obligatoire)"
                ),
                new OA\Property(
                    property: "client",
                    type: "integer",
                    example: 5,
                    description: "ID du client effectuant la réservation (obligatoire)"
                ),
                new OA\Property(
                    property: "boutique",
                    type: "integer",
                    example: 1,
                    description: "ID de la boutique où retirer les articles (obligatoire)"
                ),
                new OA\Property(
                    property: "ligne",
                    type: "array",
                    description: "Liste des articles à réserver (obligatoire, minimum 1 article)",
                    items: new OA\Items(
                        type: "object",
                        required: ["modele", "quantite"],
                        properties: [
                            new OA\Property(
                                property: "modele",
                                type: "integer",
                                example: 3,
                                description: "ID du modèle à réserver (obligatoire)"
                            ),
                            new OA\Property(
                                property: "quantite",
                                type: "integer",
                                example: 2,
                                description: "Quantité à réserver (obligatoire)"
                            )
                        ]
                    ),
                    minItems: 1,
                    example: [
                        ["modele" => 3, "quantite" => 2],
                        ["modele" => 5, "quantite" => 1]
                    ]
                )
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: "Réservation créée avec succès",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "id", type: "integer", example: 25),
                new OA\Property(property: "montant", type: "number", example: 50000),
                new OA\Property(property: "avance", type: "number", example: 20000),
                new OA\Property(property: "reste", type: "number", example: 30000),
                new OA\Property(property: "dateRetrait", type: "string", format: "date-time"),
                new OA\Property(property: "client", type: "object"),
                new OA\Property(property: "boutique", type: "object"),
                new OA\Property(property: "ligneReservations", type: "array", description: "Articles réservés", items: new OA\Items(type: "object")),
                new OA\Property(property: "paiements", type: "array", description: "Paiement de l'acompte enregistré", items: new OA\Items(type: "object")),
                new OA\Property(property: "createdAt", type: "string", format: "date-time")
            ]
        )
    )]
    #[OA\Response(response: 400, description: "Données invalides")]
    #[OA\Response(response: 401, description: "Non authentifié")]
    #[OA\Response(response: 403, description: "Abonnement requis pour cette fonctionnalité")]
    #[OA\Response(response: 404, description: "Client, boutique ou modèle non trouvé")]
    public function create(
        Request $request,
        ModeleBoutiqueRepository $modeleBoutiqueRepository,
        CaisseBoutiqueRepository $caisseBoutiqueRepository,
        PaiementReservationRepository $paiementReservationRepository,
        ModeleRepository $modeleRepository,
        ClientRepository $clientRepository,
        BoutiqueRepository $boutiqueRepository,
        Utils $utils,
        ReservationRepository $reservationRepository
    ): Response {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        $data = json_decode($request->getContent(), true);

        // Création de la réservation
        $reservation = new Reservation();
        $reservation->setAvance($data['avance']);
        $reservation->setDateRetrait(new \DateTime($data['dateRetrait']));

        $client = $clientRepository->find($data['client']);
        if (!$client) {
            $this->setMessage("Client non trouvé");
            return $this->response('[]', 404);
        }
        $reservation->setClient($client);

        $boutique = $boutiqueRepository->find($data['boutique']);
        if (!$boutique) {
            $this->setMessage("Boutique non trouvée");
            return $this->response('[]', 404);
        }
        $reservation->setBoutique($boutique);

        $reservation->setEntreprise($this->getUser()->getEntreprise());
        $reservation->setMontant($data['montant']);
        $reservation->setReste($data['reste']);
        $reservation->setCreatedAtValue(new \DateTime());
        $reservation->setUpdatedAt(new \DateTime());
        $reservation->setCreatedBy($this->getUser());
        $reservation->setUpdatedBy($this->getUser());

        // Ajout des lignes de réservation
        foreach ($data['ligne'] as $key => $value) {
            $modeleBoutique = $modeleBoutiqueRepository->find($value['modele']);
            if (!$modeleBoutique) {
                $this->setMessage("Modèle de boutique non trouvé avec ID: " . $value['modele']);
                return $this->response('[]', 404);
            }

            $ligne = new LigneReservation();
            $ligne->setQuantite($value['quantite']);
            $ligne->setModele($modeleBoutique);
            $ligne->setCreatedAtValue(new \DateTime());
            $ligne->setUpdatedAt(new \DateTime());
            $ligne->setCreatedBy($this->getUser());
            $ligne->setUpdatedBy($this->getUser());
            $reservation->addLigneReservation($ligne);
        }

        $errorResponse = $this->errorResponse($reservation);
        if ($errorResponse !== null) {
            return $errorResponse;
        } else {
            // Enregistrement du paiement de l'acompte
            $paiementReservation = new PaiementReservation();
            $paiementReservation->setReservation($reservation);
            $paiementReservation->setType(Paiement::TYPE["paiementReservation"]);
            $paiementReservation->setMontant($data['avance'] ?? 0);
            $paiementReservation->setReference($utils->generateReference('PMT'));
            $paiementReservation->setCreatedAtValue(new \DateTime());
            $paiementReservation->setUpdatedAt(new \DateTime());
            $paiementReservation->setCreatedBy($this->getUser());
            $paiementReservation->setUpdatedBy($this->getUser());
            $paiementReservationRepository->add($paiementReservation, true);

            // Mise à jour de la caisse boutique
            $caisseBoutique = $caisseBoutiqueRepository->findOneBy(['boutique' => $boutique->getId()]);
            if ($caisseBoutique) {
                $caisseBoutique->setMontant((int)$caisseBoutique->getMontant() + (int)$data['avance']);
                $caisseBoutique->setUpdatedBy($this->getUser());
                $caisseBoutique->setUpdatedAt(new \DateTime());
                $caisseBoutiqueRepository->add($caisseBoutique, true);
            }

            $reservationRepository->add($reservation, true);
        }

        return $this->responseData($reservation, 'group1', ['Content-Type' => 'application/json']);
    }

    /**
     * Met à jour une réservation existante
     */
    #[Route('/update/{id}', methods: ['PUT', 'POST'])]
    #[OA\Put(
        path: "/api/reservation/update/{id}",
        summary: "Mettre à jour une réservation",
        description: "Permet de modifier les informations d'une réservation existante, incluant les montants, la date de retrait et les articles réservés. Met à jour la caisse en conséquence. Nécessite un abonnement actif.",
        tags: ['reservation']
    )]
    #[OA\Parameter(
        name: "id",
        in: "path",
        required: true,
        description: "Identifiant unique de la réservation à mettre à jour",
        schema: new OA\Schema(type: "integer", example: 1)
    )]
    #[OA\RequestBody(
        required: true,
        description: "Nouvelles données de la réservation",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "montant", type: "number", example: 55000),
                new OA\Property(property: "avance", type: "number", example: 25000),
                new OA\Property(property: "reste", type: "number", example: 30000),
                new OA\Property(property: "dateRetrait", type: "string", format: "date-time", example: "2025-02-20T14:00:00"),
                new OA\Property(property: "client", type: "integer", example: 5),
                new OA\Property(property: "boutique", type: "integer", example: 1),
                new OA\Property(
                    property: "ligne",
                    type: "array",
                    description: "Nouvelle liste complète des articles (remplace l'ancienne)",
                    items: new OA\Items(
                        type: "object",
                        properties: [
                            new OA\Property(property: "modele", type: "integer", example: 3),
                            new OA\Property(property: "quantite", type: "integer", example: 3)
                        ]
                    )
                )
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: "Réservation mise à jour avec succès",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "id", type: "integer", example: 1),
                new OA\Property(property: "montant", type: "number", example: 55000),
                new OA\Property(property: "updatedAt", type: "string", format: "date-time")
            ]
        )
    )]
    #[OA\Response(response: 400, description: "Données invalides")]
    #[OA\Response(response: 401, description: "Non authentifié")]
    #[OA\Response(response: 403, description: "Abonnement requis pour cette fonctionnalité")]
    #[OA\Response(response: 404, description: "Réservation non trouvée")]
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
                if (isset($data['avance'])) {
                    $reservation->setAvance($data['avance']);
                }
                if (isset($data['reste'])) {
                    $reservation->setReste($data['reste']);
                }
                if (isset($data['dateRetrait'])) {
                    $reservation->setDateRetrait(new \DateTime($data['dateRetrait']));
                }
                if (isset($data['client'])) {
                    $client = $clientRepository->find($data['client']);
                    if ($client) {
                        $reservation->setClient($client);
                    }
                }
                if (isset($data['boutique'])) {
                    $boutique = $boutiqueRepository->find($data['boutique']);
                    if ($boutique) {
                        $reservation->setBoutique($boutique);
                    }
                }
                if (isset($data['montant'])) {
                    $reservation->setMontant($data['montant']);
                }

                $reservation->setUpdatedBy($this->getUser());
                $reservation->setUpdatedAt(new \DateTime());

                // Mise à jour des lignes de réservation si fournies
                if (isset($data['ligne']) && is_array($data['ligne'])) {
                    // Supprimer les anciennes lignes
                    foreach ($reservation->getLigneReservations() as $ligne) {
                        $reservation->removeLigneReservation($ligne);
                    }

                    // Ajouter les nouvelles lignes
                    foreach ($data['ligne'] as $value) {
                        $modeleBoutique = $modeleBoutiqueRepository->find($value['modele']);
                        if ($modeleBoutique) {
                            $ligne = new LigneReservation();
                            $ligne->setQuantite($value['quantite']);
                            $ligne->setModele($modeleBoutique);
                            $ligne->setIsActive(true);
                            $ligne->setCreatedAtValue(new \DateTime());
                            $ligne->setUpdatedAt(new \DateTime());
                            $ligne->setCreatedBy($this->getUser());
                            $ligne->setUpdatedBy($this->getUser());
                            $reservation->addLigneReservation($ligne);
                        }
                    }
                }

                $errorResponse = $this->errorResponse($reservation);
                if ($errorResponse !== null) {
                    return $errorResponse;
                }

                $reservationRepository->add($reservation, true);

                $response = $this->responseData($reservation, 'group1', ['Content-Type' => 'application/json']);
            } else {
                $this->setMessage("Cette ressource est inexistante");
                $this->setStatusCode(404);
                $response = $this->response([]);
            }
        } catch (\Exception $e) {
            $this->setStatusCode(500);
            $this->setMessage("Erreur lors de la mise à jour de la réservation");
            $response = $this->response([]);
        }

        return $response;
    }

    /**
     * Supprime une réservation
     */
    #[Route('/delete/{id}', methods: ['DELETE'])]
    #[OA\Delete(
        path: "/api/reservation/delete/{id}",
        summary: "Supprimer une réservation",
        description: "Permet de supprimer définitivement une réservation par son identifiant. Attention : cette action supprime également toutes les lignes de réservation et les paiements associés. Nécessite un abonnement actif.",
        tags: ['reservation']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: "Identifiant unique de la réservation à supprimer",
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: "Réservation supprimée avec succès",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "message", type: "string", example: "Operation effectuées avec succès"),
                new OA\Property(property: "deleted", type: "boolean", example: true)
            ]
        )
    )]
    #[OA\Response(response: 401, description: "Non authentifié")]
    #[OA\Response(response: 403, description: "Abonnement requis pour cette fonctionnalité")]
    #[OA\Response(response: 404, description: "Réservation non trouvée")]
    #[OA\Response(response: 500, description: "Erreur lors de la suppression")]
    public function delete(Request $request, Reservation $reservation, ReservationRepository $villeRepository): Response
    {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        try {
            if ($reservation != null) {
                $villeRepository->remove($reservation, true);
                $this->setMessage("Operation effectuées avec succès");
                $response = $this->response($reservation);
            } else {
                $this->setMessage("Cette ressource est inexistante");
                $this->setStatusCode(404);
                $response = $this->response([]);
            }
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            $this->setMessage("Erreur lors de la suppression de la réservation");
            $response = $this->response([]);
        }
        return $response;
    }

    /**
     * Supprime plusieurs réservations en masse
     */
    #[Route('/delete/all/items', methods: ['DELETE'])]
    #[OA\Delete(
        path: "/api/reservation/delete/all/items",
        summary: "Supprimer plusieurs réservations",
        description: "Permet de supprimer plusieurs réservations en une seule opération en fournissant un tableau d'identifiants. Toutes les lignes de réservation et paiements associés seront également supprimés. Nécessite un abonnement actif.",
        tags: ['reservation']
    )]
    #[OA\RequestBody(
        required: true,
        description: "Tableau des identifiants des réservations à supprimer",
        content: new OA\JsonContent(
            type: "object",
            required: ["ids"],
            properties: [
                new OA\Property(
                    property: 'ids',
                    type: 'array',
                    description: "Liste des identifiants des réservations à supprimer",
                    items: new OA\Items(type: 'integer', example: 1),
                    example: [1, 2, 3, 5, 8]
                )
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: "Réservations supprimées avec succès",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "message", type: "string", example: "Operation effectuées avec succès"),
                new OA\Property(property: "deletedCount", type: "integer", example: 5, description: "Nombre de réservations supprimées")
            ]
        )
    )]
    #[OA\Response(response: 400, description: "Données invalides")]
    #[OA\Response(response: 401, description: "Non authentifié")]
    #[OA\Response(response: 403, description: "Abonnement requis pour cette fonctionnalité")]
    #[OA\Response(response: 500, description: "Erreur lors de la suppression")]
    public function deleteAll(Request $request, ReservationRepository $villeRepository): Response
    {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        try {
            $data = json_decode($request->getContent(), true);

            $count = 0;
            foreach ($data['ids'] as $id) {
                $reservation = $villeRepository->find($id);

                if ($reservation != null) {
                    $villeRepository->remove($reservation);
                    $count++;
                }
            }
            $this->setMessage("Operation effectuées avec succès");
            $response = $this->json(['message' => 'Operation effectuées avec succès', 'deletedCount' => $count]);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            $this->setMessage("Erreur lors de la suppression des réservations");
            $response = $this->response([]);
        }
        return $response;
    }
}
