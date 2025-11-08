<?php

namespace App\Controller\Apis;

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

/**
 * Contrôleur pour la gestion des paiements
 * Gère les paiements de factures, les ventes en boutique avec mise à jour automatique des caisses et stocks
 */
#[Route('/api/paiement')]
#[OA\Tag(name: 'paiement', description: 'Gestion des paiements (factures, ventes boutiques) avec mise à jour automatique des caisses et stocks')]
class ApiPaiementController extends ApiInterface
{
    /**
     * Liste tous les paiements du système
     */
    #[Route('/', methods: ['GET'])]
    #[OA\Get(
        path: "/api/paiement/",
        summary: "Lister tous les paiements",
        description: "Retourne la liste paginée de tous les paiements du système (factures et ventes boutiques).",
        tags: ['paiement']
    )]
    #[OA\Response(
        response: 200,
        description: "Liste des paiements récupérée avec succès",
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(
                type: "object",
                properties: [
                    new OA\Property(property: "id", type: "integer", example: 1, description: "Identifiant unique du paiement"),
                    new OA\Property(property: "montant", type: "number", format: "float", example: 50000, description: "Montant du paiement en FCFA"),
                    new OA\Property(property: "reference", type: "string", example: "PMT-2025-001", description: "Référence unique du paiement"),
                    new OA\Property(property: "type", type: "string", example: "paiementFacture", description: "Type: paiementFacture ou paiementBoutique"),
                    new OA\Property(property: "facture", type: "object", nullable: true, description: "Facture associée (si paiementFacture)"),
                    new OA\Property(property: "boutique", type: "object", nullable: true, description: "Boutique associée (si paiementBoutique)"),
                    new OA\Property(property: "client", type: "object", nullable: true, description: "Client"),
                    new OA\Property(property: "createdAt", type: "string", format: "date-time", example: "2025-01-30T14:30:00+00:00"),
                    new OA\Property(property: "createdBy", type: "object", description: "Utilisateur ayant créé le paiement")
                ]
            )
        )
    )]
    #[OA\Response(response: 500, description: "Erreur serveur lors de la récupération")]
    public function index(PaiementFactureRepository $paiementRepository): Response
    {
        try {
            $paiements = $this->paginationService->paginate($paiementRepository->findAll());
            $response = $this->responseData($paiements, 'group1', ['Content-Type' => 'application/json']);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            $this->setMessage("Erreur lors de la récupération des paiements");
            $response = $this->response([]);
        }

        return $response;
    }

    /**
     * Liste les paiements selon les droits de l'utilisateur (entreprise ou succursale)
     */
    #[Route('/entreprise', methods: ['GET'])]
    #[OA\Get(
        path: "/api/paiement/entreprise",
        summary: "Lister les paiements selon les droits utilisateur",
        description: "Retourne la liste des paiements filtrée selon le type d'utilisateur : Super-admin voit tous les paiements de l'entreprise, autres utilisateurs voient uniquement les paiements de leur succursale. Nécessite un abonnement actif.",
        tags: ['paiement']
    )]
    #[OA\Response(
        response: 200,
        description: "Liste des paiements récupérée avec succès",
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(
                type: "object",
                properties: [
                    new OA\Property(property: "id", type: "integer", example: 1),
                    new OA\Property(property: "montant", type: "number", format: "float", example: 50000),
                    new OA\Property(property: "reference", type: "string", example: "PMT-2025-001"),
                    new OA\Property(property: "type", type: "string", example: "paiementFacture"),
                    new OA\Property(property: "facture", type: "object", nullable: true),
                    new OA\Property(property: "entreprise", type: "object"),
                    new OA\Property(property: "succursale", type: "object", nullable: true)
                ]
            )
        )
    )]
    #[OA\Response(response: 401, description: "Non authentifié")]
    #[OA\Response(response: 403, description: "Abonnement requis pour cette fonctionnalité")]
    #[OA\Response(response: 500, description: "Erreur lors de la récupération")]
    public function indexAll(PaiementFactureRepository $paiementRepository, TypeUserRepository $typeUserRepository): Response
    {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        try {
            if ($this->getUser()->getType() == $typeUserRepository->findOneBy(['code' => 'SADM'])) {
                $paiements = $this->paginationService->paginate($paiementRepository->findBy(
                    ['entreprise' => $this->getUser()->getEntreprise()],
                    ['id' => 'DESC']
                ));
            } else {
                $paiements = $this->paginationService->paginate($paiementRepository->findBy(
                    ['surccursale' => $this->getUser()->getSurccursale()],
                    ['id' => 'DESC']
                ));
            }

            $response = $this->responseDataWith_([
                'data' => $paiements,
            ], 'group1', ['Content-Type' => 'application/json']);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            $this->setMessage("Erreur lors de la récupération des paiements");
            $response = $this->response([]);
        }

        return $response;
    }

    /**
     * Récupère les détails d'un paiement spécifique
     */
    #[Route('/get/one/{id}', methods: ['GET'])]
    #[OA\Get(
        path: "/api/paiement/get/one/{id}",
        summary: "Détails d'un paiement",
        description: "Affiche les informations détaillées d'un paiement spécifique, incluant la facture ou la vente associée. Nécessite un abonnement actif.",
        tags: ['paiement']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: "Identifiant unique du paiement",
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: "Paiement trouvé avec succès",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "id", type: "integer", example: 1),
                new OA\Property(property: "montant", type: "number", format: "float", example: 50000),
                new OA\Property(property: "reference", type: "string", example: "PMT-2025-001"),
                new OA\Property(property: "type", type: "string", example: "paiementFacture"),
                new OA\Property(property: "facture", type: "object", nullable: true, description: "Facture payée"),
                new OA\Property(property: "boutique", type: "object", nullable: true, description: "Boutique (si vente)"),
                new OA\Property(property: "client", type: "object", nullable: true),
                new OA\Property(property: "createdAt", type: "string", format: "date-time"),
                new OA\Property(property: "createdBy", type: "object")
            ]
        )
    )]
    #[OA\Response(response: 401, description: "Non authentifié")]
    #[OA\Response(response: 403, description: "Abonnement requis pour cette fonctionnalité")]
    #[OA\Response(response: 404, description: "Paiement non trouvé")]
    public function getOne(?PaiementFacture $paiement): Response
    {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        try {
            if ($paiement) {
                $response = $this->responseData($paiement, 'group1', ['Content-Type' => 'application/json']);
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
     * Crée un paiement pour une facture (acompte ou solde)
     */
    #[Route('/facture/{id}', methods: ['POST'])]
    #[OA\Post(
        path: "/api/paiement/facture/{id}",
        summary: "Créer un paiement de facture",
        description: "Permet d'enregistrer un paiement (acompte ou solde) pour une facture existante. Met automatiquement à jour le reste à payer de la facture, la caisse de la succursale, et envoie des notifications. Nécessite un abonnement actif.",
        tags: ['paiement']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: "Identifiant de la facture à payer",
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\RequestBody(
        required: true,
        description: "Données du paiement à enregistrer",
        content: new OA\JsonContent(
            type: "object",
            required: ["montant"],
            properties: [
                new OA\Property(
                    property: "montant",
                    type: "number",
                    format: "float",
                    example: 20000,
                    description: "Montant du paiement en FCFA (obligatoire, doit être ≤ reste à payer)"
                ),
                new OA\Property(
                    property: "succursaleId",
                    type: "integer",
                    nullable: true,
                    example: 1,
                    description: "ID de la succursale (optionnel, par défaut celle de l'utilisateur)"
                )
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: "Paiement créé avec succès",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(
                    property: "data",
                    type: "object",
                    description: "Facture mise à jour",
                    properties: [
                        new OA\Property(property: "id", type: "integer", example: 1),
                        new OA\Property(property: "reference", type: "string", example: "FACT-2025-001"),
                        new OA\Property(property: "montantTotal", type: "number", example: 50000),
                        new OA\Property(property: "resteArgent", type: "number", example: 30000, description: "Reste à payer mis à jour"),
                        new OA\Property(property: "paiements", type: "array", description: "Liste des paiements incluant le nouveau", items: new OA\Items(type: "object"))
                    ]
                )
            ]
        )
    )]
    #[OA\Response(response: 400, description: "Montant invalide ou supérieur au reste à payer")]
    #[OA\Response(response: 401, description: "Non authentifié")]
    #[OA\Response(response: 403, description: "Abonnement requis pour cette fonctionnalité")]
    #[OA\Response(response: 404, description: "Facture non trouvée")]
    public function create(
        Request $request,
        UserRepository $userRepository,
        Utils $utils,
        CaisseSuccursaleRepository $caisseSuccursaleRepository,
        Facture $facture,
        FactureRepository $factureRepository,
        PaiementFactureRepository $paiementRepository
    ): Response {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        $data = json_decode($request->getContent(), true);
        $admin = $userRepository->getUserByCodeType($this->getUser()->getEntreprise());

        // Création du paiement
        $paiement = new PaiementFacture();
        $paiement->setMontant($data['montant']);
        $paiement->setFacture($facture);
        $paiement->setIsActive(true);
        $paiement->setType(Paiement::TYPE["paiementFacture"]);
        $paiement->setReference($utils->generateReference('PMT'));
        $paiement->setCreatedBy($this->getUser());
        $paiement->setUpdatedBy($this->getUser());
        $paiement->setCreatedAtValue(new \DateTime());
        $paiement->setUpdatedAt(new \DateTime());

        // Mise à jour du reste à payer de la facture
        $facture->setResteArgent((int)$facture->getResteArgent() - (int)$data['montant']);

        // Mise à jour de la caisse succursale
        $caisse = $data['succursaleId'] != null
            ? $caisseSuccursaleRepository->findOneBy(['surccursale' => $data['succursaleId']])
            : $caisseSuccursaleRepository->findOneBy(['surccursale' => $this->getUser()->getSurccursale()]);

        $caisse->setMontant((int)$caisse->getMontant() + (int)$data['montant']);
        $caisse->setType('caisse_succursale');

        $errorResponse = $this->errorResponse($paiement);
        if ($errorResponse !== null) {
            return $errorResponse;
        } else {
            $paiementRepository->add($paiement, true);
            $factureRepository->add($facture, true);
            $caisseSuccursaleRepository->add($caisse, true);

            // Envoi des notifications
            if ($admin) {
                $this->sendMailService->sendNotification([
                    'entreprise' => $this->getUser()->getEntreprise(),
                    "user" => $admin,
                    "libelle" => sprintf(
                        "Bonjour %s,\n\n" .
                            "Nous vous informons qu'un nouveau paiement vient d'être enregistré dans la succursale **%s**.\n\n" .
                            "- Montant : %s FCFA\n" .
                            "- Effectué par : %s\n" .
                            "- Date : %s\n\n" .
                            "Cordialement,\nVotre application de gestion.",
                        $admin->getLogin(),
                        $this->getUser()->getSurccursale() ? $this->getUser()->getSurccursale()->getLibelle() : "N/A",
                        number_format($data['montant'], 0, ',', ' '),
                        $this->getUser()->getNom() && $this->getUser()->getPrenoms()
                            ? $this->getUser()->getNom() . " " . $this->getUser()->getPrenoms()
                            : $this->getUser()->getLogin(),
                        (new \DateTime())->format('d/m/Y H:i')
                    ),
                    "titre" => "Paiement facture - " . ($this->getUser()->getSurccursale() ? $this->getUser()->getSurccursale()->getLibelle() : ""),
                ]);

                $this->sendMailService->send(
                    $this->sendMail,
                    $this->superAdmin,
                    "Paiement facture - " . $this->getUser()->getEntreprise()->getNom(),
                    "paiement_email",
                    [
                        "boutique_libelle" => $this->getUser()->getEntreprise()->getNom(),
                        "montant" => number_format($data['montant'], 0, ',', ' ') . " FCFA",
                        "date" => (new \DateTime())->format('d/m/Y H:i'),
                    ]
                );
            }
        }

        return $this->responseDataWith_([
            'data' => $facture,
        ], 'group1', ['Content-Type' => 'application/json']);
    }

    /**
     * Crée une vente boutique simple (un seul produit)
     */
    #[Route('/boutique/{id}', methods: ['POST'])]
    #[OA\Post(
        path: "/api/paiement/boutique/{id}",
        summary: "Créer une vente boutique simple",
        description: "Permet d'enregistrer une vente simple d'un seul produit dans une boutique. Met automatiquement à jour le stock du produit, la caisse de la boutique, et envoie des notifications. Nécessite un abonnement actif.",
        tags: ['paiement']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: "Identifiant de la boutique",
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\RequestBody(
        required: true,
        description: "Données de la vente à enregistrer",
        content: new OA\JsonContent(
            type: "object",
            required: ["montant", "modeleBoutiqueId", "quantite"],
            properties: [
                new OA\Property(
                    property: "montant",
                    type: "number",
                    format: "float",
                    example: 15000,
                    description: "Montant total de la vente en FCFA (obligatoire)"
                ),
                new OA\Property(
                    property: "client",
                    type: "integer",
                    nullable: true,
                    example: 5,
                    description: "ID du client (optionnel)"
                ),
                new OA\Property(
                    property: "modeleBoutiqueId",
                    type: "integer",
                    example: 3,
                    description: "ID du modèle de boutique vendu (obligatoire)"
                ),
                new OA\Property(
                    property: "quantite",
                    type: "integer",
                    example: 2,
                    description: "Quantité vendue (obligatoire, doit être ≤ stock disponible)"
                )
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: "Vente enregistrée avec succès",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(
                    property: "data",
                    type: "object",
                    description: "Paiement créé",
                    properties: [
                        new OA\Property(property: "id", type: "integer", example: 25),
                        new OA\Property(property: "montant", type: "number", example: 15000),
                        new OA\Property(property: "reference", type: "string", example: "PMT-2025-025"),
                        new OA\Property(property: "type", type: "string", example: "paiementBoutique"),
                        new OA\Property(property: "quantite", type: "integer", example: 2),
                        new OA\Property(property: "boutique", type: "object"),
                        new OA\Property(property: "client", type: "object", nullable: true)
                    ]
                )
            ]
        )
    )]
    #[OA\Response(response: 400, description: "Stock insuffisant ou données invalides")]
    #[OA\Response(response: 401, description: "Non authentifié")]
    #[OA\Response(response: 403, description: "Abonnement requis pour cette fonctionnalité")]
    #[OA\Response(response: 404, description: "Boutique ou modèle non trouvé")]
    public function paiementBoutiqueModele(
        Request $request,
        ClientRepository $clientRepository,
        PaiementBoutiqueLigneRepository $paiementBoutiqueLigneRepository,
        Boutique $boutique,
        UserRepository $userRepository,
        Utils $utils,
        ModeleBoutiqueRepository $modeleBoutiqueRepository,
        CaisseBoutiqueRepository $caisseBoutiqueRepository,
        BoutiqueRepository $boutiqueRepository,
        FactureRepository $factureRepository,
        PaiementFactureRepository $paiementRepository
    ): Response {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        $admin = $userRepository->getUserByCodeType($this->getUser()->getEntreprise());
        $data = json_decode($request->getContent(), true);

        // Création du paiement boutique
        $paiement = new PaiementBoutique();
        $paiement->setMontant($data['montant']);

        if (isset($data['client']) && $data['client']) {
            $client = $clientRepository->find($data['client']);
            if ($client) {
                $paiement->setClient($client);
            }
        }

        $paiement->setType(Paiement::TYPE["paiementBoutique"]);
        $paiement->setBoutique($boutique);
        $paiement->setReference($utils->generateReference('PMT'));
        $paiement->setQuantite($data['quantite']);
        $paiement->setCreatedBy($this->getUser());
        $paiement->setUpdatedBy($this->getUser());
        $paiement->setIsActive(true);
        $paiement->setCreatedAtValue(new \DateTime());
        $paiement->setUpdatedAt(new \DateTime());

        // Mise à jour de la caisse boutique
        $caisse = $caisseBoutiqueRepository->findOneBy(['boutique' => $boutique->getId()]);
        $caisse->setMontant((int)$caisse->getMontant() + (int)$data['montant']);

        $errorResponse = $this->errorResponse($paiement);
        if ($errorResponse !== null) {
            return $errorResponse;
        } else {
            // Création de la ligne de paiement
            $ligne = new PaiementBoutiqueLigne();
            $ligne->setPaiementBoutique($paiement);

            $modeleBoutique = $modeleBoutiqueRepository->find($data['modeleBoutiqueId']);
            if (!$modeleBoutique) {
                $this->setMessage("Modèle de boutique non trouvé");
                return $this->response('[]', 404);
            }

            // Vérification du stock disponible
            if ($modeleBoutique->getQuantite() < $data['quantite']) {
                return $this->json([
                    'status' => 'ERROR',
                    'message' => "Stock insuffisant pour ce modèle (disponible: {$modeleBoutique->getQuantite()}, demandé: {$data['quantite']})"
                ], 400);
            }

            $ligne->setModeleBoutique($modeleBoutique);
            $ligne->setQuantite($data['quantite']);
            $ligne->setMontant($data['montant']);
            $paiementBoutiqueLigneRepository->add($ligne, true);

            // Mise à jour du stock
            $modeleBoutique->setQuantite((int)$modeleBoutique->getQuantite() - (int)$data['quantite']);
            $modeleBoutiqueRepository->add($modeleBoutique, true);

            $paiementRepository->add($paiement, true);
            $caisseBoutiqueRepository->add($caisse, true);

            // Envoi des notifications
            if ($admin) {
                $this->sendMailService->sendNotification([
                    'entreprise' => $this->getUser()->getEntreprise(),
                    "user" => $admin,
                    "libelle" => sprintf(
                        "Bonjour %s,\n\n" .
                            "Nous vous informons qu'une nouvelle vente vient d'être enregistrée dans la boutique **%s**.\n\n" .
                            "- Montant : %s FCFA\n" .
                            "- Effectué par : %s\n" .
                            "- Date : %s\n\n" .
                            "Cordialement,\nVotre application de gestion.",
                        $admin->getLogin(),
                        $boutique->getLibelle(),
                        number_format($data['montant'], 0, ',', ' '),
                        $this->getUser()->getNom() && $this->getUser()->getPrenoms()
                            ? $this->getUser()->getNom() . " " . $this->getUser()->getPrenoms()
                            : $this->getUser()->getLogin(),
                        (new \DateTime())->format('d/m/Y H:i')
                    ),
                    "titre" => "Vente - " . $boutique->getLibelle(),
                ]);

                $this->sendMailService->send(
                    $this->sendMail,
                    $this->superAdmin,
                    "Vente - " . $this->getUser()->getEntreprise()->getNom(),
                    "vente_email",
                    [
                        "boutique_libelle" => $this->getUser()->getEntreprise()->getNom(),
                        "montant" => number_format($data['montant'], 0, ',', ' ') . " FCFA",
                        "date" => (new \DateTime())->format('d/m/Y H:i'),
                    ]
                );
            }
        }

        return $this->responseDataWith_([
            'data' => $paiement,
        ], 'group1', ['Content-Type' => 'application/json']);
    }

    /**
     * Crée une vente boutique multiple (plusieurs produits en une transaction)
     */
    #[Route('/boutique/multiple/{id}', methods: ['POST'])]
    #[OA\Post(
        path: "/api/paiement/boutique/multiple/{id}",
        summary: "Créer une vente boutique multiple",
        description: "Permet d'enregistrer une vente de plusieurs produits en une seule transaction dans une boutique. Met automatiquement à jour les stocks de tous les produits, la caisse de la boutique, et envoie des notifications. Nécessite un abonnement actif.",
        tags: ['paiement']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: "Identifiant de la boutique",
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\RequestBody(
        required: true,
        description: "Données de la vente multiple à enregistrer",
        content: new OA\JsonContent(
            type: "object",
            required: ["lignes"],
            properties: [
                new OA\Property(
                    property: "client",
                    type: "integer",
                    nullable: true,
                    example: 5,
                    description: "ID du client (optionnel)"
                ),
                new OA\Property(
                    property: "lignes",
                    type: "array",
                    description: "Liste des produits vendus (obligatoire, minimum 1 ligne)",
                    items: new OA\Items(
                        type: "object",
                        required: ["montant", "modeleBoutiqueId", "quantite"],
                        properties: [
                            new OA\Property(
                                property: "montant",
                                type: "number",
                                format: "float",
                                example: 15000,
                                description: "Montant de cette ligne (obligatoire)"
                            ),
                            new OA\Property(
                                property: "modeleBoutiqueId",
                                type: "integer",
                                example: 3,
                                description: "ID du modèle vendu (obligatoire)"
                            ),
                            new OA\Property(
                                property: "quantite",
                                type: "integer",
                                example: 2,
                                description: "Quantité vendue (obligatoire)"
                            )
                        ]
                    ),
                    minItems: 1,
                    example: [
                        ["montant" => 15000, "modeleBoutiqueId" => 3, "quantite" => 2],
                        ["montant" => 25000, "modeleBoutiqueId" => 5, "quantite" => 1],
                        ["montant" => 10000, "modeleBoutiqueId" => 8, "quantite" => 3]
                    ]
                )
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: "Vente multiple enregistrée avec succès",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(
                    property: "data",
                    type: "object",
                    description: "Paiement créé",
                    properties: [
                        new OA\Property(property: "id", type: "integer", example: 30),
                        new OA\Property(property: "montant", type: "number", example: 50000, description: "Montant total de la vente"),
                        new OA\Property(property: "reference", type: "string", example: "PMT-2025-030"),
                        new OA\Property(property: "type", type: "string", example: "paiementBoutique"),
                        new OA\Property(property: "quantite", type: "integer", example: 6, description: "Quantité totale vendue"),
                        new OA\Property(property: "boutique", type: "object"),
                        new OA\Property(property: "lignes", type: "array", description: "Lignes de vente détaillées", items: new OA\Items(type: "object"))
                    ]
                )
            ]
        )
    )]
    #[OA\Response(response: 400, description: "Stock insuffisant pour un ou plusieurs produits")]
    #[OA\Response(response: 401, description: "Non authentifié")]
    #[OA\Response(response: 403, description: "Abonnement requis pour cette fonctionnalité")]
    #[OA\Response(response: 404, description: "Boutique ou modèle non trouvé")]
    public function paiementBoutiqueModeleSeveralLigne(
        Request $request,
        ClientRepository $clientRepository,
        PaiementBoutiqueLigneRepository $paiementBoutiqueLigneRepository,
        Boutique $boutique,
        UserRepository $userRepository,
        Utils $utils,
        ModeleBoutiqueRepository $modeleBoutiqueRepository,
        CaisseBoutiqueRepository $caisseBoutiqueRepository,
        BoutiqueRepository $boutiqueRepository,
        FactureRepository $factureRepository,
        PaiementFactureRepository $paiementRepository
    ): Response {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        $data = json_decode($request->getContent(), true);
        $admin = $userRepository->getUserByCodeType($this->getUser()->getEntreprise());

        // Création du paiement boutique
        $paiement = new PaiementBoutique();
        $paiement->setType(Paiement::TYPE["paiementBoutique"]);
        $paiement->setBoutique($boutique);
        $paiement->setIsActive(true);
        $paiement->setReference($utils->generateReference('PMT'));

        if (isset($data['client']) && $data['client']) {
            $client = $clientRepository->find($data['client']);
            if ($client) {
                $paiement->setClient($client);
            }
        }

        $paiement->setCreatedBy($this->getUser());
        $paiement->setUpdatedBy($this->getUser());
        $paiement->setCreatedAtValue(new \DateTime());
        $paiement->setUpdatedAt(new \DateTime());

        $caisse = $caisseBoutiqueRepository->findOneBy(['boutique' => $boutique->getId()]);

        $sommeMontant = 0;
        $sommeQuantite = 0;

        // Traitement de toutes les lignes de vente
        foreach ($data['lignes'] as $ligneData) {
            $modeleBoutique = $modeleBoutiqueRepository->find($ligneData['modeleBoutiqueId']);

            if (!$modeleBoutique) {
                return $this->json([
                    'status' => 'ERROR',
                    'message' => "Modèle de boutique non trouvé avec ID: " . $ligneData['modeleBoutiqueId']
                ], 400);
            }

            // Vérification du stock disponible
            if ($modeleBoutique->getQuantite() < $ligneData['quantite']) {
                return $this->json([
                    'status' => 'ERROR',
                    'message' => "Stock insuffisant pour le modèle ID {$modeleBoutique->getId()} " .
                        "(disponible: {$modeleBoutique->getQuantite()}, demandé: {$ligneData['quantite']})"
                ], 400);
            }

            $ligne = new PaiementBoutiqueLigne();
            $ligne->setPaiementBoutique($paiement);
            $ligne->setModeleBoutique($modeleBoutique);
            $ligne->setQuantite($ligneData['quantite']);
            $ligne->setMontant($ligneData['montant']);
            //$ligne->setIsActive(true);

            $sommeMontant += $ligneData['montant'];
            $sommeQuantite += $ligneData['quantite'];

            // Mise à jour du stock
            $modeleBoutique->setQuantite((int)$modeleBoutique->getQuantite() - (int)$ligneData['quantite']);
            $modeleBoutiqueRepository->add($modeleBoutique, true);
            $paiementBoutiqueLigneRepository->add($ligne, true);
        }

        // Mise à jour de la caisse
        $caisse->setMontant((int)$caisse->getMontant() + (int)$sommeMontant);
        $caisseBoutiqueRepository->add($caisse, true);

        $paiement->setMontant($sommeMontant);
        $paiement->setQuantite($sommeQuantite);
        $paiementRepository->add($paiement, true);

        // Envoi des notifications
        if ($admin) {
            $this->sendMailService->sendNotification([
                'entreprise' => $this->getUser()->getEntreprise(),
                "user" => $admin,
                "libelle" => sprintf(
                    "Bonjour %s,\n\n" .
                        "Nous vous informons qu'une nouvelle vente vient d'être enregistrée dans la boutique **%s**.\n\n" .
                        "- Montant : %s FCFA\n" .
                        "- Effectué par : %s\n" .
                        "- Date : %s\n\n" .
                        "Cordialement,\nVotre application de gestion.",
                    $admin->getLogin(),
                    $boutique->getLibelle(),
                    number_format($sommeMontant, 0, ',', ' '),
                    $this->getUser()->getNom() && $this->getUser()->getPrenoms()
                        ? $this->getUser()->getNom() . " " . $this->getUser()->getPrenoms()
                        : $this->getUser()->getLogin(),
                    (new \DateTime())->format('d/m/Y H:i')
                ),
                "titre" => "Vente - " . $boutique->getLibelle(),
            ]);

            $this->sendMailService->send(
                $this->sendMail,
                $this->superAdmin,
                "Vente - " . $this->getUser()->getEntreprise()->getNom(),
                "vente_email",
                [
                    "boutique_libelle" => $this->getUser()->getEntreprise()->getNom(),
                    "montant" => number_format($sommeMontant, 0, ',', ' ') . " FCFA",
                    "date" => (new \DateTime())->format('d/m/Y H:i'),
                ]
            );
        }

        return $this->responseDataWith_([
            'data' => $paiement,
        ], 'group1', ['Content-Type' => 'application/json']);
    }

    /**
     * Webhook pour les notifications de paiement externes
     */
    #[Route('/webhook', name: 'webhook_paiement', methods: ['GET', 'POST'])]
    #[OA\Get(
        path: "/api/paiement/webhook",
        summary: "Webhook paiement externe",
        description: "Endpoint de callback pour les notifications de paiement provenant de services externes (passerelles de paiement, mobile money, etc.). Traite automatiquement les confirmations de paiement.",
        tags: ['paiement']
    )]
    #[OA\Parameter(
        name: 'merchantId',
        in: 'query',
        schema: new OA\Schema(type: 'string'),
        description: "Identifiant du marchand"
    )]
    #[OA\Parameter(
        name: 'sessionId',
        in: 'query',
        schema: new OA\Schema(type: 'string'),
        description: "Identifiant de session"
    )]
    #[OA\Parameter(
        name: 'responsecode',
        in: 'query',
        schema: new OA\Schema(type: 'string'),
        description: "Code de réponse du paiement"
    )]
    #[OA\Response(
        response: 200,
        description: "Webhook traité avec succès",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "status", type: "string", example: "success"),
                new OA\Property(property: "message", type: "string", example: "Paiement confirmé")
            ]
        )
    )]
    public function webHook(Request $request, PaiementService $paiementService): Response
    {
        $all = $request->query->all();
        $response = $paiementService->methodeWebHook($all);

        return $this->responseData(
            $response,
            'group1',
            ['Content-Type' => 'application/json']
        );
    }

    /**
     * Supprime un paiement
     */
    #[Route('/delete/{id}', methods: ['DELETE'])]
    #[OA\Delete(
        path: "/api/paiement/delete/{id}",
        summary: "Supprimer un paiement",
        description: "Permet de supprimer définitivement un paiement par son identifiant. Attention : cette action ne recalcule pas automatiquement les caisses et les stocks. Nécessite un abonnement actif.",
        tags: ['paiement']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: "Identifiant unique du paiement à supprimer",
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: "Paiement supprimé avec succès",
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
    #[OA\Response(response: 404, description: "Paiement non trouvé")]
    #[OA\Response(response: 500, description: "Erreur lors de la suppression")]
    public function delete(Request $request, PaiementFacture $paiement, PaiementFactureRepository $villeRepository): Response
    {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        try {
            if ($paiement != null) {
                $villeRepository->remove($paiement, true);
                $this->setMessage("Operation effectuées avec succès");
                $response = $this->response($paiement);
            } else {
                $this->setMessage("Cette ressource est inexistante");
                $this->setStatusCode(404);
                $response = $this->response([]);
            }
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            $this->setMessage("Erreur lors de la suppression du paiement");
            $response = $this->response([]);
        }
        return $response;
    }

    /**
     * Supprime plusieurs paiements en masse
     */
    #[Route('/delete/all/items', methods: ['DELETE'])]
    #[OA\Delete(
        path: "/api/paiement/delete/all/items",
        summary: "Supprimer plusieurs paiements",
        description: "Permet de supprimer plusieurs paiements en une seule opération en fournissant un tableau d'identifiants. Attention : cette action ne recalcule pas automatiquement les caisses et les stocks. Nécessite un abonnement actif.",
        tags: ['paiement']
    )]
    #[OA\RequestBody(
        required: true,
        description: "Tableau des identifiants des paiements à supprimer",
        content: new OA\JsonContent(
            type: "object",
            required: ["ids"],
            properties: [
                new OA\Property(
                    property: 'ids',
                    type: 'array',
                    description: "Liste des identifiants des paiements à supprimer",
                    items: new OA\Items(type: 'integer', example: 1),
                    example: [1, 2, 3, 5, 8]
                )
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: "Paiements supprimés avec succès",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "message", type: "string", example: "Operation effectuées avec succès"),
                new OA\Property(property: "deletedCount", type: "integer", example: 5, description: "Nombre de paiements supprimés")
            ]
        )
    )]
    #[OA\Response(response: 400, description: "Données invalides")]
    #[OA\Response(response: 401, description: "Non authentifié")]
    #[OA\Response(response: 403, description: "Abonnement requis pour cette fonctionnalité")]
    #[OA\Response(response: 500, description: "Erreur lors de la suppression")]
    public function deleteAll(Request $request, PaiementFactureRepository $villeRepository): Response
    {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        try {
            $data = json_decode($request->getContent(), true);

            $count = 0;
            foreach ($data['ids'] as $id) {
                $paiement = $villeRepository->find($id);

                if ($paiement != null) {
                    $villeRepository->remove($paiement);
                    $count++;
                }
            }
            $this->setMessage("Operation effectuées avec succès");
            $response = $this->json(['message' => 'Operation effectuées avec succès', 'deletedCount' => $count]);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            $this->setMessage("Erreur lors de la suppression des paiements");
            $response = $this->response([]);
        }
        return $response;
    }
}
