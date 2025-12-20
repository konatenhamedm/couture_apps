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
use App\Repository\PaiementReservationRepository;
use App\Repository\TypeUserRepository;
use App\Repository\UserRepository;
use App\Service\PaiementService;
use App\Service\Utils;
use Doctrine\ORM\EntityManagerInterface;
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
            $paiements = $this->paginationService->paginate($paiementRepository->findAllInEnvironment());
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
            if ($this->getUser()->getType() == $typeUserRepository->findOneByInEnvironment(['code' => 'SADM'])) {
                $paiements = $this->paginationService->paginate($paiementRepository->findByInEnvironment(
                    ['entreprise' => $this->getUser()->getEntreprise()],
                    ['id' => 'DESC']
                ));
            } else {
                $paiements = $this->paginationService->paginate($paiementRepository->findByInEnvironment(
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
    public function getOne(int $id, PaiementFactureRepository $paiementFactureRepository): Response
    {
        
        $paiement = $paiementFactureRepository->findInEnvironment($id);
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
        summary: "Faire un paiement sur une facture ",
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
        $paiement->setCreatedBy($this->getManagedUser());
        $paiement->setUpdatedBy($this->getManagedUser());
        $paiement->setCreatedAtValue();
        $paiement->setUpdatedAt();

        // Mise à jour du reste à payer de la facture
        $facture->setResteArgent((int)$facture->getResteArgent() - (int)$data['montant']);

        // Mise à jour de la caisse succursale
        $caisse = $caisseSuccursaleRepository->findOneByInEnvironment(['succursale' => $facture->getSuccursale()->getId()]);

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
                        $this->getUser()->getSurccursale() ? $this->getUser()->getSurccursale()->getLibelle() : $facture->getSuccursale()->getLibelle(),
                        number_format($data['montant'], 0, ',', ' '),
                        $this->getUser()->getNom() && $this->getUser()->getPrenoms()
                            ? $this->getUser()->getNom() . " " . $this->getUser()->getPrenoms()
                            : $this->getUser()->getLogin(),
                        (new \DateTime())->format('d/m/Y H:i')
                    ),
                    "titre" => "Paiement facture - " . ($this->getUser()->getSurccursale() ? $this->getUser()->getSurccursale()->getLibelle() : $facture->getSuccursale()->getLibelle()),
                ]);

                $this->sendMailService->send(
                    $this->sendMail,
                    $this->superAdmin,
                    "Paiement facture - " . $this->getUser()->getEntreprise()->getLibelle(),
                    "paiement_email",
                    [
                        "boutique_libelle" => $this->getUser()->getEntreprise()->getLibelle(),
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
        PaiementFactureRepository $paiementRepository,
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        $admin = $userRepository->getUserByCodeType($this->getUser()->getEntreprise());
        $data = json_decode($request->getContent(), true);

        // ✅ Validation des données
        if (!isset($data['montant']) || !isset($data['quantite']) || !isset($data['modeleBoutiqueId'])) {
            return $this->json([
                'status' => 'ERROR',
                'message' => 'Données manquantes (montant, quantite ou modeleBoutiqueId requis)'
            ], 400);
        }

        $quantite = (int)$data['quantite'];
        $montant = (int)$data['montant'];

        if ($quantite <= 0) {
            return $this->json([
                'status' => 'ERROR',
                'message' => 'La quantité doit être supérieure à 0'
            ], 400);
        }

        if ($montant <= 0) {
            return $this->json([
                'status' => 'ERROR',
                'message' => 'Le montant doit être supérieur à 0'
            ], 400);
        }

        // Récupérer le modèle boutique
        $modeleBoutique = $modeleBoutiqueRepository->findInEnvironment($data['modeleBoutiqueId']);
        if (!$modeleBoutique) {
            return $this->json([
                'status' => 'ERROR',
                'message' => 'Modèle de boutique non trouvé'
            ], 404);
        }

        // ✅ Vérification du stock AVANT toute modification
        if ($modeleBoutique->getQuantite() < $quantite) {
            return $this->json([
                'status' => 'ERROR',
                'message' => "Stock insuffisant pour ce modèle (disponible: {$modeleBoutique->getQuantite()}, demandé: {$quantite})"
            ], 400);
        }

        // Vérifier que le modèle appartient à la bonne boutique
        if ($modeleBoutique->getBoutique()->getId() !== $boutique->getId()) {
            return $this->json([
                'status' => 'ERROR',
                'message' => 'Ce modèle n\'appartient pas à cette boutique'
            ], 400);
        }

        // Récupérer la caisse
        $caisse = $caisseBoutiqueRepository->findOneByInEnvironment(['boutique' => $boutique->getId()]);
        if (!$caisse) {
            return $this->json([
                'status' => 'ERROR',
                'message' => 'Caisse de boutique introuvable'
            ], 404);
        }

        // Créer le paiement boutique
        $paiement = new PaiementBoutique();
        $paiement->setMontant($montant);

        if (isset($data['client']) && $data['client']) {
            $client = $clientRepository->findInEnvironment($data['client']);
            if ($client) {
                $paiement->setClient($this->getManagedEntityFromEnvironment($client));
            }
        }

        $paiement->setType(Paiement::TYPE["paiementBoutique"]);
        $paiement->setBoutique($boutique);
        $paiement->setReference($utils->generateReference('PMT'));
        $paiement->setQuantite($quantite);
        $paiement->setCreatedBy($this->getManagedUser());
        $paiement->setUpdatedBy($this->getManagedUser());
        $paiement->setIsActive(true);
        $paiement->setCreatedAtValue();
        $paiement->setUpdatedAt();

        $errorResponse = $this->errorResponse($paiement);
        if ($errorResponse !== null) {
            return $errorResponse;
        }

        // 🔒 Transaction pour garantir la cohérence
        $entityManager->beginTransaction();

        try {
            // ✅ 1. Persister le paiement AVANT la ligne (résout l'erreur de cascade)
            $entityManager->persist($paiement);

            // 2. Créer la ligne de paiement
            $ligne = new PaiementBoutiqueLigne();
            $ligne->setPaiementBoutique($paiement);
            $ligne->setModeleBoutique($modeleBoutique);
            $ligne->setQuantite($quantite);
            $ligne->setMontant($montant);
            $entityManager->persist($ligne);

            // 3. Mise à jour du stock
            $modeleBoutique->setQuantite($modeleBoutique->getQuantite() - $quantite);

            // 4. Mise à jour de la quantité globale si nécessaire
            $modele = $modeleBoutique->getModele();
            if ($modele && $modele->getQuantiteGlobale() >= $quantite) {
                $modele->setQuantiteGlobale($modele->getQuantiteGlobale() - $quantite);
            }

            // 5. Mise à jour de la caisse
            $caisse->setMontant($caisse->getMontant() + $montant);

            // ✅ Un seul flush pour tout
            $entityManager->flush();
            $entityManager->commit();

            // Envoi des notifications (après la transaction réussie)
            if ($admin) {
                try {
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
                            number_format($montant, 0, ',', ' '),
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
                        "Vente - " . $this->getUser()->getEntreprise()->getLibelle(),
                        "vente_email",
                        [
                            "boutique_libelle" => $this->getUser()->getEntreprise()->getLibelle(),
                            "montant" => number_format($montant, 0, ',', ' ') . " FCFA",
                            "date" => (new \DateTime())->format('d/m/Y H:i'),
                        ]
                    );
                } catch (\Exception $e) {
                    // Ne pas bloquer la vente si l'envoi d'email échoue
                    // Vous pouvez logger l'erreur ici
                }
            }

            return $this->responseDataWith_([
                'data' => $paiement,
            ], 'group1', ['Content-Type' => 'application/json']);
        } catch (\Exception $e) {
            $entityManager->rollback();
            return $this->json([
                'status' => 'ERROR',
                'message' => 'Erreur lors de la création du paiement: ' . $e->getMessage()
            ], 500);
        }
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
        PaiementFactureRepository $paiementRepository,
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        $data = json_decode($request->getContent(), true);
        $lignes = $data['lignes'] ?? [];

        // ✅ Validation préalable
        if (empty($lignes) || !is_array($lignes)) {
            return $this->json([
                'status' => 'ERROR',
                'message' => 'Aucune ligne de vente à traiter'
            ], 400);
        }

        // Récupérer tous les ModeleBoutique en une seule requête
        $modeleBoutiqueIds = array_column($lignes, 'modeleBoutiqueId');
        $modeleBoutiques = $modeleBoutiqueRepository->findByInEnvironment(['id' => $modeleBoutiqueIds]);

        // Indexer par ID pour un accès rapide
        $modeleBoutiquesMap = [];
        foreach ($modeleBoutiques as $mb) {
            $modeleBoutiquesMap[$mb->getId()] = $mb;
        }

        // ✅ VALIDATION COMPLÈTE DES STOCKS AVANT TOUTE MODIFICATION
        foreach ($lignes as $index => $ligneData) {
            $modeleBoutiqueId = $ligneData['modeleBoutiqueId'] ?? null;
            $quantite = $ligneData['quantite'] ?? null;
            $montant = $ligneData['montant'] ?? null;

            // Vérifier que les données sont présentes
            if ($modeleBoutiqueId === null) {
                return $this->json([
                    'status' => 'ERROR',
                    'message' => "modeleBoutiqueId manquant à la ligne " . ($index + 1)
                ], 400);
            }

            if ($quantite === null || $montant === null) {
                return $this->json([
                    'status' => 'ERROR',
                    'message' => "quantite ou montant manquant à la ligne " . ($index + 1)
                ], 400);
            }

            $quantite = (int)$quantite;
            $montant = (int)$montant;

            // Vérifier que les valeurs sont positives
            if ($quantite <= 0) {
                return $this->json([
                    'status' => 'ERROR',
                    'message' => "La quantité doit être supérieure à 0 à la ligne " . ($index + 1)
                ], 400);
            }

            if ($montant <= 0) {
                return $this->json([
                    'status' => 'ERROR',
                    'message' => "Le montant doit être supérieur à 0 à la ligne " . ($index + 1)
                ], 400);
            }

            // Vérifier que le ModeleBoutique existe
            if (!isset($modeleBoutiquesMap[$modeleBoutiqueId])) {
                return $this->json([
                    'status' => 'ERROR',
                    'message' => "Modèle de boutique non trouvé avec ID: {$modeleBoutiqueId}"
                ], 400);
            }

            $modeleBoutique = $modeleBoutiquesMap[$modeleBoutiqueId];

            // Vérifier que le modèle appartient à la bonne boutique
            if ($modeleBoutique->getBoutique()->getId() !== $boutique->getId()) {
                return $this->json([
                    'status' => 'ERROR',
                    'message' => "Le modèle ID {$modeleBoutiqueId} n'appartient pas à cette boutique"
                ], 400);
            }

            // ✅ Vérification CRITIQUE du stock disponible
            if ($modeleBoutique->getQuantite() < $quantite) {
                return $this->json([
                    'status' => 'ERROR',
                    'message' => "Stock insuffisant pour le modèle '{$modeleBoutique->getModele()->getNom()}' " .
                        "(disponible: {$modeleBoutique->getQuantite()}, demandé: {$quantite})"
                ], 400);
            }
        }

        // Récupérer la caisse
        $caisse = $caisseBoutiqueRepository->findOneByInEnvironment(['boutique' => $boutique->getId()]);
        if (!$caisse) {
            return $this->json([
                'status' => 'ERROR',
                'message' => 'Caisse de boutique introuvable'
            ], 404);
        }

        // Récupérer l'admin pour les notifications
        $admin = $userRepository->getUserByCodeType($this->getUser()->getEntreprise());

        // Créer le paiement boutique
        $paiement = new PaiementBoutique();
        $paiement->setType(Paiement::TYPE["paiementBoutique"]);
        $paiement->setBoutique($boutique);
        $paiement->setIsActive(true);
        $paiement->setReference($utils->generateReference('PMT'));

        if (isset($data['client']) && $data['client']) {
            $client = $clientRepository->findInEnvironment($data['client']);
            if ($client) {
                $paiement->setClient($this->getManagedEntityFromEnvironment($client));
            }
        }

        $paiement->setCreatedBy($this->getManagedUser());
        $paiement->setUpdatedBy($this->getManagedUser());
        $paiement->setCreatedAtValue();
        $paiement->setUpdatedAt();

        // 🔒 Transaction pour garantir la cohérence atomique
        $entityManager->beginTransaction();

        try {
            $sommeMontant = 0;
            $sommeQuantite = 0;

            // ✅ Persister le paiement AVANT les lignes (résout l'erreur de cascade)
            $entityManager->persist($paiement);

            // Traiter toutes les lignes sans flush intermédiaire
            foreach ($lignes as $ligneData) {
                $modeleBoutique = $modeleBoutiquesMap[$ligneData['modeleBoutiqueId']];
                $modele = $modeleBoutique->getModele();
                $quantite = (int)$ligneData['quantite'];
                $montant = (int)$ligneData['montant'];

                // Créer la ligne de paiement
                $ligne = new PaiementBoutiqueLigne();
                $ligne->setPaiementBoutique($paiement);
                $ligne->setModeleBoutique($modeleBoutique);
                $ligne->setQuantite($quantite);
                $ligne->setMontant($montant);

                $entityManager->persist($ligne);

                // Mise à jour du stock boutique
                $modeleBoutique->setQuantite($modeleBoutique->getQuantite() - $quantite);

                // Mise à jour de la quantité globale
                if ($modele && $modele->getQuantiteGlobale() >= $quantite) {
                    $modele->setQuantiteGlobale($modele->getQuantiteGlobale() - $quantite);
                }

                $sommeMontant += $montant;
                $sommeQuantite += $quantite;
            }

            // Mise à jour du paiement avec les totaux
            $paiement->setMontant($sommeMontant);
            $paiement->setQuantite($sommeQuantite);

            // Mise à jour de la caisse
            $caisse->setMontant($caisse->getMontant() + $sommeMontant);

            // ✅ Un seul flush pour tout
            $entityManager->flush();
            $entityManager->commit();

            // Envoi des notifications (après la transaction réussie)
            if ($admin) {
                try {
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

                  
                } catch (\Exception $e) {
                    // Ne pas bloquer la vente si l'envoi d'email échoue
                    // Vous pouvez logger l'erreur ici
                }
            }
            
            $this->sendMailService->send(
                $this->sendMail,
                $this->superAdmin,
                "Vente - " . $this->getUser()->getEntreprise()->getLibelle(),
                "vente_email",
                [
                    "boutique_libelle" => $this->getUser()->getEntreprise()->getLibelle(),
                    "montant" => number_format($sommeMontant, 0, ',', ' ') . " FCFA",
                    "date" => (new \DateTime())->format('d/m/Y H:i'),
                ]
            );

            return $this->responseDataWith_([
                'data' => $paiement,
            ], 'group1', ['Content-Type' => 'application/json']);
        } catch (\Exception $e) {
            $entityManager->rollback();
            return $this->json([
                'status' => 'ERROR',
                'message' => 'Erreur lors de la création du paiement: ' . $e->getMessage()
            ], 500);
        }
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
    public function delete(Request $request, int $id, PaiementFactureRepository $villeRepository): Response
    {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        try {
            $paiement = $villeRepository->findInEnvironment($id);
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
    public function deleteAll(Request $request, PaiementFactureRepository $paiementRepository): Response
    {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        try {
            $data = json_decode($request->getContent(), true);

            $count = 0;
            foreach ($data['ids'] as $id) {
                $paiement = $paiementRepository->findInEnvironment($id);

                if ($paiement != null) {
                    $paiementRepository->remove($paiement);
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