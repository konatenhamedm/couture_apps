<?php

namespace App\Controller\Apis;

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
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Attribute\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

/**
 * Contrôleur pour la gestion des stocks
 * Permet de gérer les entrées et sorties de stock des modèles de vêtements dans les boutiques
 */
#[Route('/api/stock')]
class ApiGestionStockController extends ApiInterface
{
    /**
     * Liste tous les mouvements de stock (entrées et sorties) d'une boutique
     */
    #[Route('/{id}', methods: ['GET'])]
    #[OA\Get(
        path: "/api/stock/{id}",
        summary: "📊 Historique des mouvements de stock d'une boutique",
        description: "Retourne la liste paginée et triée de tous les mouvements de stock (entrées et sorties) d'une boutique spécifique. Permet de suivre l'historique complet des variations de stock avec les détails de chaque ligne de mouvement. Inclut les informations sur les modèles concernés, les quantités et les utilisateurs responsables.",
        tags: ['Gestion des Stocks']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: "Identifiant unique de la boutique dont on veut consulter l'historique des stocks",
        schema: new OA\Schema(type: 'integer', minimum: 1, example: 1)
    )]
    #[OA\Parameter(
        name: 'page',
        in: 'query',
        required: false,
        description: "Numéro de page pour la pagination (défaut: 1)",
        schema: new OA\Schema(type: 'integer', minimum: 1, default: 1, example: 1)
    )]
    #[OA\Parameter(
        name: 'limit',
        in: 'query',
        required: false,
        description: "Nombre d'éléments par page (défaut: 20, max: 100)",
        schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100, default: 20, example: 20)
    )]
    #[OA\Response(
        response: 200,
        description: "✅ Historique des mouvements de stock récupéré avec succès",
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: "status", type: "string", example: "SUCCESS"),
                new OA\Property(property: "message", type: "string", example: "Historique des mouvements récupéré avec succès"),
                new OA\Property(
                    property: "data",
                    type: "array",
                    description: "Liste des mouvements de stock",
                    items: new OA\Items(
                        type: "object",
                        properties: [
                            new OA\Property(property: "id", type: "integer", example: 15, description: "Identifiant unique du mouvement de stock"),
                            new OA\Property(property: "type", type: "string", enum: ["Entree", "Sortie"], example: "Entree", description: "Type de mouvement (Entree pour ajout, Sortie pour retrait)"),
                            new OA\Property(property: "quantite", type: "integer", example: 75, description: "Quantité totale concernée par ce mouvement"),
                            new OA\Property(property: "date", type: "string", format: "date-time", nullable: true, example: "2025-01-15T14:30:00+00:00", description: "Date du mouvement (peut être null)"),
                            new OA\Property(
                                property: "boutique",
                                type: "object",
                                description: "Informations de la boutique concernée",
                                properties: [
                                    new OA\Property(property: "id", type: "integer", example: 1),
                                    new OA\Property(property: "libelle", type: "string", example: "Boutique Centre-Ville"),
                                    new OA\Property(property: "adresse", type: "string", example: "123 Rue de la Mode, Paris")
                                ]
                            ),
                            new OA\Property(
                                property: "entreprise",
                                type: "object",
                                description: "Entreprise propriétaire",
                                properties: [
                                    new OA\Property(property: "id", type: "integer", example: 1),
                                    new OA\Property(property: "nom", type: "string", example: "Atelier Couture Pro")
                                ]
                            ),
                            new OA\Property(
                                property: "ligneEntres",
                                type: "array",
                                description: "Détails des lignes de ce mouvement de stock",
                                items: new OA\Items(
                                    type: "object",
                                    properties: [
                                        new OA\Property(property: "id", type: "integer", example: 25, description: "ID de la ligne"),
                                        new OA\Property(property: "quantite", type: "integer", example: 25, description: "Quantité pour cette ligne"),
                                        new OA\Property(
                                            property: "modele",
                                            type: "object",
                                            description: "Modèle de boutique concerné",
                                            properties: [
                                                new OA\Property(property: "id", type: "integer", example: 8),
                                                new OA\Property(property: "quantite", type: "integer", example: 150, description: "Stock actuel"),
                                                new OA\Property(property: "prix", type: "string", example: "45.99"),
                                                new OA\Property(property: "taille", type: "string", example: "M"),
                                                new OA\Property(
                                                    property: "modele",
                                                    type: "object",
                                                    description: "Modèle parent",
                                                    properties: [
                                                        new OA\Property(property: "id", type: "integer", example: 3),
                                                        new OA\Property(property: "libelle", type: "string", example: "Robe d'été fleurie"),
                                                        new OA\Property(property: "description", type: "string", example: "Belle robe légère pour l'été")
                                                    ]
                                                )
                                            ]
                                        )
                                    ]
                                )
                            ),
                            new OA\Property(property: "createdAt", type: "string", format: "date-time", example: "2025-01-15T10:30:00+00:00", description: "Date de création du mouvement"),
                            new OA\Property(property: "updatedAt", type: "string", format: "date-time", example: "2025-01-15T10:30:00+00:00", description: "Date de dernière modification"),
                            new OA\Property(
                                property: "createdBy",
                                type: "object",
                                description: "Utilisateur ayant créé le mouvement",
                                properties: [
                                    new OA\Property(property: "id", type: "integer", example: 5),
                                    new OA\Property(property: "nom", type: "string", example: "Dupont"),
                                    new OA\Property(property: "prenom", type: "string", example: "Marie")
                                ]
                            )
                        ]
                    )
                ),
                new OA\Property(
                    property: "pagination",
                    type: "object",
                    description: "Informations de pagination",
                    properties: [
                        new OA\Property(property: "current_page", type: "integer", example: 1),
                        new OA\Property(property: "per_page", type: "integer", example: 20),
                        new OA\Property(property: "total", type: "integer", example: 45),
                        new OA\Property(property: "total_pages", type: "integer", example: 3)
                    ]
                )
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: "🔒 Non authentifié - Token JWT manquant ou invalide",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "status", type: "string", example: "ERROR"),
                new OA\Property(property: "message", type: "string", example: "Token JWT manquant ou invalide")
            ]
        )
    )]
    #[OA\Response(
        response: 403,
        description: "🚫 Abonnement requis pour cette fonctionnalité",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "status", type: "string", example: "ERROR"),
                new OA\Property(property: "message", type: "string", example: "Abonnement requis pour cette fonctionnalité")
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: "❌ Boutique non trouvée",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "status", type: "string", example: "ERROR"),
                new OA\Property(property: "message", type: "string", example: "Boutique non trouvée avec l'ID spécifié")
            ]
        )
    )]
    #[OA\Response(
        response: 500,
        description: "💥 Erreur interne du serveur",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "status", type: "string", example: "ERROR"),
                new OA\Property(property: "message", type: "string", example: "Erreur lors de la récupération de l'historique de stock")
            ]
        )
    )]
    public function index(ModeleRepository $modeleRepository, EntreStockRepository $entreStockRepository, Boutique $boutique): Response
    {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        try {
            $entrees = $this->paginationService->paginate($entreStockRepository->findBy(
                ['boutique' => $boutique->getId()],
                ['id' => 'DESC']
            ));

            $response = $this->responseData($entrees, 'group1', ['Content-Type' => 'application/json']);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            $this->setMessage("Erreur lors de la récupération de l'historique de stock");
            $response = $this->response([]);
        }

        return $response;
    }

    /**
     * Liste tous les mouvements de stock d'un modèle spécifique dans une boutique
     */
    #[Route('/modeleBoutique/{id}', methods: ['GET'])]
    #[OA\Get(
        path: "/api/stock/modeleBoutique/{id}",
        summary: "🔍 Historique détaillé des mouvements d'un modèle",
        description: "Retourne la liste paginée et chronologique de toutes les lignes d'entrées et sorties de stock pour un modèle spécifique dans une boutique. Permet de tracer précisément tous les mouvements de ce modèle avec les détails de chaque transaction, les quantités impliquées et les mouvements de stock parents. Idéal pour l'audit et le suivi détaillé d'un produit.",
        tags: ['Gestion des Stocks']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: "Identifiant unique du modèle de boutique (ModeleBoutique) dont on veut consulter l'historique",
        schema: new OA\Schema(type: 'integer', minimum: 1, example: 8)
    )]
    #[OA\Parameter(
        name: 'page',
        in: 'query',
        required: false,
        description: "Numéro de page pour la pagination (défaut: 1)",
        schema: new OA\Schema(type: 'integer', minimum: 1, default: 1, example: 1)
    )]
    #[OA\Parameter(
        name: 'limit',
        in: 'query',
        required: false,
        description: "Nombre d'éléments par page (défaut: 20, max: 100)",
        schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100, default: 20, example: 20)
    )]
    #[OA\Response(
        response: 200,
        description: "✅ Historique des mouvements du modèle récupéré avec succès",
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: "status", type: "string", example: "SUCCESS"),
                new OA\Property(property: "message", type: "string", example: "Historique du modèle récupéré avec succès"),
                new OA\Property(
                    property: "data",
                    type: "array",
                    description: "Liste des lignes de mouvements pour ce modèle",
                    items: new OA\Items(
                        type: "object",
                        properties: [
                            new OA\Property(property: "id", type: "integer", example: 42, description: "Identifiant unique de la ligne de stock"),
                            new OA\Property(property: "quantite", type: "integer", example: 15, description: "Quantité concernée par cette ligne de mouvement"),
                            new OA\Property(
                                property: "modele",
                                type: "object",
                                description: "Modèle de boutique concerné avec ses informations complètes",
                                properties: [
                                    new OA\Property(property: "id", type: "integer", example: 8, description: "ID du modèle de boutique"),
                                    new OA\Property(property: "quantite", type: "integer", example: 125, description: "Quantité totale actuelle en stock"),
                                    new OA\Property(property: "prix", type: "string", example: "89.99", description: "Prix de vente du modèle"),
                                    new OA\Property(property: "taille", type: "string", example: "L", description: "Taille du modèle"),
                                    new OA\Property(
                                        property: "modele",
                                        type: "object",
                                        description: "Modèle parent avec ses caractéristiques",
                                        properties: [
                                            new OA\Property(property: "id", type: "integer", example: 3),
                                            new OA\Property(property: "libelle", type: "string", example: "Chemise en lin"),
                                            new OA\Property(property: "description", type: "string", example: "Chemise légère en lin naturel"),
                                            new OA\Property(property: "quantiteGlobale", type: "integer", example: 450, description: "Stock global tous modèles confondus")
                                        ]
                                    ),
                                    new OA\Property(property: "createdAt", type: "string", format: "date-time", example: "2025-01-10T09:15:00+00:00"),
                                    new OA\Property(property: "updatedAt", type: "string", format: "date-time", example: "2025-01-15T14:22:00+00:00")
                                ]
                            ),
                            new OA\Property(
                                property: "entreStock",
                                type: "object",
                                description: "Mouvement de stock parent contenant cette ligne",
                                properties: [
                                    new OA\Property(property: "id", type: "integer", example: 12, description: "ID du mouvement de stock"),
                                    new OA\Property(property: "type", type: "string", enum: ["Entree", "Sortie"], example: "Entree", description: "Type de mouvement"),
                                    new OA\Property(property: "quantite", type: "integer", example: 50, description: "Quantité totale du mouvement"),
                                    new OA\Property(property: "date", type: "string", format: "date-time", nullable: true, example: "2025-01-15T14:00:00+00:00"),
                                    new OA\Property(property: "createdAt", type: "string", format: "date-time", example: "2025-01-15T14:00:00+00:00"),
                                    new OA\Property(
                                        property: "boutique",
                                        type: "object",
                                        description: "Boutique du mouvement",
                                        properties: [
                                            new OA\Property(property: "id", type: "integer", example: 1),
                                            new OA\Property(property: "libelle", type: "string", example: "Boutique Centre-Ville")
                                        ]
                                    )
                                ]
                            )
                        ]
                    )
                ),
                new OA\Property(
                    property: "pagination",
                    type: "object",
                    description: "Informations de pagination",
                    properties: [
                        new OA\Property(property: "current_page", type: "integer", example: 1),
                        new OA\Property(property: "per_page", type: "integer", example: 20),
                        new OA\Property(property: "total", type: "integer", example: 28),
                        new OA\Property(property: "total_pages", type: "integer", example: 2)
                    ]
                )
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: "🔒 Non authentifié - Token JWT manquant ou invalide",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "status", type: "string", example: "ERROR"),
                new OA\Property(property: "message", type: "string", example: "Token JWT manquant ou invalide")
            ]
        )
    )]
    #[OA\Response(
        response: 403,
        description: "🚫 Abonnement requis pour cette fonctionnalité",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "status", type: "string", example: "ERROR"),
                new OA\Property(property: "message", type: "string", example: "Abonnement requis pour cette fonctionnalité")
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: "❌ Modèle de boutique non trouvé",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "status", type: "string", example: "ERROR"),
                new OA\Property(property: "message", type: "string", example: "Modèle de boutique non trouvé avec l'ID spécifié")
            ]
        )
    )]
    #[OA\Response(
        response: 500,
        description: "💥 Erreur interne du serveur",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "status", type: "string", example: "ERROR"),
                new OA\Property(property: "message", type: "string", example: "Erreur lors de la récupération de l'historique du modèle")
            ]
        )
    )]
    public function indexModeleBoutique(ModeleRepository $modeleRepository, LigneEntreRepository $ligneEntreRepository, ModeleBoutique $modeleBoutique): Response
    {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        try {
            $entrees = $this->paginationService->paginate($ligneEntreRepository->findBy(
                ['modele' => $modeleBoutique->getId()],
                ['id' => 'DESC']
            ));

            $response = $this->responseData($entrees, 'group_ligne', ['Content-Type' => 'application/json']);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            $this->setMessage("Erreur lors de la récupération de l'historique du modèle");
            $response = $this->response([]);
        }

        return $response;
    }

    /**
     * Crée une entrée de stock (ajout de quantités) avec ses lignes détaillées
     */
    #[Route('/entree', methods: ['POST'])]
    #[OA\Post(
        path: "/api/stock/entree",
        summary: "Créer une entrée de stock",
        description: "Permet d'enregistrer une entrée de stock (réapprovisionnement) pour une boutique avec plusieurs lignes de produits. Met automatiquement à jour les quantités en stock au niveau du modèle boutique et du modèle global. Nécessite un abonnement actif.",
        tags: ['stock']
    )]
    #[OA\RequestBody(
        required: true,
        description: "Données de l'entrée de stock à créer",
        content: new OA\JsonContent(
            type: "object",
            required: ["boutiqueId", "lignes"],
            properties: [
                new OA\Property(
                    property: "boutiqueId",
                    type: "integer",
                    example: 1,
                    description: "ID de la boutique concernée par l'entrée de stock (obligatoire)"
                ),
                new OA\Property(
                    property: "lignes",
                    type: "array",
                    description: "Liste des lignes de produits à ajouter au stock (obligatoire, minimum 1 ligne)",
                    items: new OA\Items(
                        type: "object",
                        required: ["quantite", "modeleBoutiqueId"],
                        properties: [
                            new OA\Property(
                                property: "quantite",
                                type: "integer",
                                example: 50,
                                description: "Quantité à ajouter en stock pour ce modèle (obligatoire, doit être > 0)"
                            ),
                            new OA\Property(
                                property: "modeleBoutiqueId",
                                type: "integer",
                                example: 5,
                                description: "ID du modèle de boutique concerné (obligatoire)"
                            )
                        ]
                    ),
                    minItems: 1,
                    example: [
                        ["quantite" => 50, "modeleBoutiqueId" => 5],
                        ["quantite" => 30, "modeleBoutiqueId" => 8],
                        ["quantite" => 20, "modeleBoutiqueId" => 12]
                    ]
                )
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: "Entrée de stock créée avec succès",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "id", type: "integer", example: 15, description: "ID de l'entrée de stock créée"),
                new OA\Property(property: "type", type: "string", example: "Entree", description: "Type de mouvement"),
                new OA\Property(property: "quantite", type: "integer", example: 100, description: "Quantité totale de l'entrée"),
                new OA\Property(property: "boutique", type: "object", description: "Boutique concernée"),
                new OA\Property(property: "entreprise", type: "object", description: "Entreprise"),
                new OA\Property(property: "ligneEntres", type: "array", description: "Lignes détaillées", items: new OA\Items(type: "object")),
                new OA\Property(property: "createdAt", type: "string", format: "date-time"),
                new OA\Property(property: "createdBy", type: "object", description: "Utilisateur créateur")
            ]
        )
    )]
    #[OA\Response(response: 400, description: "Données invalides ou modèle non trouvé")]
    #[OA\Response(response: 401, description: "Non authentifié")]
    #[OA\Response(response: 403, description: "Abonnement requis pour cette fonctionnalité")]
    public function create(
        Request $request,
        LigneEntreRepository $ligneEntreRepository,
        ModeleRepository $modeleRepository,
        BoutiqueRepository $boutiqueRepository,
        EntreStockRepository $entreStockRepository,
        ModeleBoutiqueRepository $modeleBoutiqueRepository,
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        $data = json_decode($request->getContent(), true);
        $lignes = $data['lignes'] ?? [];

        // Validation préalable
        if (empty($lignes) || !is_array($lignes)) {
            $this->setMessage("Aucune ligne à traiter");
            return $this->response('[]', 400);
        }

        // Récupérer tous les ModeleBoutique en une seule requête
        $modeleBoutiqueIds = array_column($lignes, 'modeleBoutiqueId');
        $modeleBoutiques = $modeleBoutiqueRepository->findBy(['id' => $modeleBoutiqueIds]);

        // Indexer par ID pour un accès rapide
        $modeleBoutiquesMap = [];
        foreach ($modeleBoutiques as $mb) {
            $modeleBoutiquesMap[$mb->getId()] = $mb;
        }

        // Valider que tous les ModeleBoutique existent
        foreach ($lignes as $ligne) {
            if (!isset($modeleBoutiquesMap[$ligne['modeleBoutiqueId']])) {
                $this->setMessage("Modèle de boutique introuvable avec l'ID: " . $ligne['modeleBoutiqueId']);
                return $this->response('[]', 400);
            }
        }

        // Créer l'EntreStock
        $boutique = $boutiqueRepository->find($data['boutiqueId']);
        if (!$boutique) {
            $this->setMessage("Boutique introuvable");
            return $this->response('[]', 400);
        }

        $entreStock = new EntreStock();
        $entreStock->setBoutique($boutique);
        $entreStock->setType('Entree');
        $entreStock->setEntreprise($this->getUser()->getEntreprise());
        $entreStock->setCreatedBy($this->getUser());
        $entreStock->setUpdatedBy($this->getUser());
        $entreStock->setCreatedAtValue(new \DateTime());
        $entreStock->setUpdatedAt(new \DateTime());
        $entreStock->setQuantite(0);

        $errorResponse = $this->errorResponse($entreStock);
        if ($errorResponse !== null) {
            return $errorResponse;
        }

        // Transaction pour garantir la cohérence
        $entityManager->beginTransaction();

        try {
            $totalQuantite = 0;

            // Traiter toutes les lignes sans flush
            foreach ($lignes as $ligne) {
                $modeleBoutique = $modeleBoutiquesMap[$ligne['modeleBoutiqueId']];
                $modele = $modeleBoutique->getModele(); // Utiliser la relation au lieu d'une requête
                $quantite = (int)$ligne['quantite'];
                $totalQuantite += $quantite;

                // Création de la ligne d'entrée
                $ligneEntre = new LigneEntre();
                $ligneEntre->setQuantite($quantite);
                $ligneEntre->setModele($modeleBoutique);
                $ligneEntre->setEntreStock($entreStock);

                $entityManager->persist($ligneEntre);
                $entreStock->addLigneEntre($ligneEntre);

                // Mise à jour des quantités (pas de flush)
                $modeleBoutique->setQuantite($modeleBoutique->getQuantite() + $quantite);
                $modele->setQuantiteGlobale($modele->getQuantiteGlobale() + $quantite);
            }

            $entreStock->setQuantite($totalQuantite);
            $entityManager->persist($entreStock);

            // Un seul flush pour tout
            $entityManager->flush();
            $entityManager->commit();

            return $this->responseData($entreStock, 'group1', ['Content-Type' => 'application/json']);
        } catch (\Exception $e) {
            $entityManager->rollback();
            $this->setMessage("Erreur lors de la création: " . $e->getMessage());
            return $this->response('[]', 500);
        }
    }

    /**
     * Met à jour une entrée de stock existante avec ses lignes
     */
    #[Route('/entree/{id}', methods: ['PUT'])]
    #[OA\Put(
        path: "/api/stock/entree/{id}",
        summary: "Mettre à jour une entrée de stock",
        description: "Permet de mettre à jour une entrée de stock existante. Les anciennes lignes sont supprimées et remplacées par les nouvelles. Les quantités des modèles sont recalculées en conséquence. Nécessite un abonnement actif.",
        tags: ['stock']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: "Identifiant unique de l'entrée de stock à mettre à jour",
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\RequestBody(
        required: true,
        description: "Nouvelles données de l'entrée de stock",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "boutiqueId", type: "integer", example: 1, description: "Nouvel ID de la boutique (optionnel)"),
                new OA\Property(
                    property: "lignes",
                    type: "array",
                    description: "Nouvelles lignes de stock (remplace toutes les anciennes)",
                    items: new OA\Items(
                        type: "object",
                        required: ["quantite", "modeleBoutiqueId"],
                        properties: [
                            new OA\Property(property: "quantite", type: "integer", example: 60, description: "Nouvelle quantité"),
                            new OA\Property(property: "modeleBoutiqueId", type: "integer", example: 5, description: "ID du modèle")
                        ]
                    ),
                    example: [
                        ["quantite" => 60, "modeleBoutiqueId" => 5],
                        ["quantite" => 40, "modeleBoutiqueId" => 8]
                    ]
                )
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: "Entrée de stock mise à jour avec succès",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "id", type: "integer", example: 1),
                new OA\Property(property: "type", type: "string", example: "Entree"),
                new OA\Property(property: "quantite", type: "integer", example: 100),
                new OA\Property(property: "updatedAt", type: "string", format: "date-time")
            ]
        )
    )]
    #[OA\Response(response: 400, description: "Données invalides ou modèle non trouvé")]
    #[OA\Response(response: 401, description: "Non authentifié")]
    #[OA\Response(response: 403, description: "Abonnement requis pour cette fonctionnalité")]
    #[OA\Response(response: 404, description: "Entrée de stock non trouvée")]
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
            $this->setMessage('Entrée de stock introuvable');
            return $this->response('[]', 404);
        }

        $totalQuantite = 0;

        if (isset($data['boutiqueId'])) {
            $entreStock->setBoutique($boutiqueRepository->find($data['boutiqueId']));
        }

        $entreStock->setUpdatedBy($this->getUser());
        $entreStock->setUpdatedAt(new \DateTime());

        // Suppression des anciennes lignes
        foreach ($entreStock->getLigneEntres() as $oldLigne) {
            $entreStock->removeLigneEntre($oldLigne);
            $ligneEntreRepository->remove($oldLigne, true);
        }

        // Ajout des nouvelles lignes
        if (isset($data['lignes']) && is_array($data['lignes'])) {
            foreach ($data['lignes'] as $ligne) {
                $modeleBoutique = $modeleBoutiqueRepository->find($ligne['modeleBoutiqueId']);

                if (!$modeleBoutique) {
                    $this->setMessage('Modèle de boutique introuvable avec ID: ' . $ligne['modeleBoutiqueId']);
                    return $this->response('[]', 400);
                }

                $modele = $modeleRepository->find($modeleBoutique->getModele()->getId());
                $quantite = (int)$ligne['quantite'];
                $totalQuantite += $quantite;

                $ligneEntre = new LigneEntre();
                $ligneEntre->setQuantite($quantite);
                $ligneEntre->setModele($modeleBoutique);
                $ligneEntre->setEntreStock($entreStock);

                $ligneEntreRepository->add($ligneEntre, true);

                $modeleBoutique->setQuantite($modeleBoutique->getQuantite() + $quantite);
                $modeleBoutiqueRepository->add($modeleBoutique, true);

                $modele->setQuantiteGlobale($modele->getQuantiteGlobale() + $quantite);
                $modeleRepository->add($modele, true);

                $entreStock->addLigneEntre($ligneEntre);
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

    /**
     * Crée une sortie de stock (retrait de quantités) avec ses lignes détaillées
     */
    #[Route('/sortie', methods: ['POST'])]
    #[OA\Post(
        path: "/api/stock/sortie",
        summary: "Créer une sortie de stock",
        description: "Permet d'enregistrer une sortie de stock (vente, transfert, perte) pour une boutique avec plusieurs lignes de produits. Vérifie automatiquement la disponibilité des quantités avant de valider la sortie. Met à jour les quantités en stock au niveau du modèle boutique et du modèle global. Nécessite un abonnement actif.",
        tags: ['stock']
    )]
    #[OA\RequestBody(
        required: true,
        description: "Données de la sortie de stock à créer",
        content: new OA\JsonContent(
            type: "object",
            required: ["boutiqueId", "lignes"],
            properties: [
                new OA\Property(
                    property: "boutiqueId",
                    type: "integer",
                    example: 1,
                    description: "ID de la boutique concernée par la sortie de stock (obligatoire)"
                ),
                new OA\Property(
                    property: "lignes",
                    type: "array",
                    description: "Liste des lignes de produits à retirer du stock (obligatoire, minimum 1 ligne)",
                    items: new OA\Items(
                        type: "object",
                        required: ["quantite", "modeleBoutiqueId"],
                        properties: [
                            new OA\Property(
                                property: "quantite",
                                type: "integer",
                                example: 20,
                                description: "Quantité à retirer du stock pour ce modèle (obligatoire, doit être > 0 et ≤ quantité disponible)"
                            ),
                            new OA\Property(
                                property: "modeleBoutiqueId",
                                type: "integer",
                                example: 5,
                                description: "ID du modèle de boutique concerné (obligatoire)"
                            )
                        ]
                    ),
                    minItems: 1,
                    example: [
                        ["quantite" => 20, "modeleBoutiqueId" => 5],
                        ["quantite" => 15, "modeleBoutiqueId" => 8],
                        ["quantite" => 10, "modeleBoutiqueId" => 12]
                    ]
                )
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: "Sortie de stock créée avec succès",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "id", type: "integer", example: 20, description: "ID de la sortie de stock créée"),
                new OA\Property(property: "type", type: "string", example: "Sortie", description: "Type de mouvement"),
                new OA\Property(property: "quantite", type: "integer", example: 45, description: "Quantité totale de la sortie"),
                new OA\Property(property: "boutique", type: "object", description: "Boutique concernée"),
                new OA\Property(property: "entreprise", type: "object", description: "Entreprise"),
                new OA\Property(property: "ligneEntres", type: "array", description: "Lignes détaillées", items: new OA\Items(type: "object")),
                new OA\Property(property: "createdAt", type: "string", format: "date-time"),
                new OA\Property(property: "createdBy", type: "object", description: "Utilisateur créateur")
            ]
        )
    )]
    #[OA\Response(
        response: 400,
        description: "Stock insuffisant ou données invalides",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "status", type: "string", example: "ERROR"),
                new OA\Property(property: "message", type: "string", example: "Stock insuffisant pour le modèle ID 5 (disponible: 10, demandé: 20)")
            ]
        )
    )]
    #[OA\Response(response: 401, description: "Non authentifié")]
    #[OA\Response(response: 403, description: "Abonnement requis pour cette fonctionnalité")]
    public function sortie(
        Request $request,
        ModeleRepository $modeleRepository,
        LigneEntreRepository $ligneEntreRepository,
        BoutiqueRepository $boutiqueRepository,
        EntreStockRepository $entreStockRepository,
        ModeleBoutiqueRepository $modeleBoutiqueRepository,
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        $data = json_decode($request->getContent(), true);
        $lignes = $data['lignes'] ?? [];

        // Validation préalable
        if (empty($lignes) || !is_array($lignes)) {
            return $this->json([
                'status' => 'ERROR',
                'message' => 'Aucune ligne à traiter'
            ], 400);
        }

        // Récupérer la boutique
        $boutique = $boutiqueRepository->find($data['boutiqueId']);
        if (!$boutique) {
            return $this->json([
                'status' => 'ERROR',
                'message' => 'Boutique introuvable'
            ], 400);
        }

        // Récupérer tous les ModeleBoutique en une seule requête
        $modeleBoutiqueIds = array_column($lignes, 'modeleBoutiqueId');
        $modeleBoutiques = $modeleBoutiqueRepository->findBy(['id' => $modeleBoutiqueIds]);

        // Indexer par ID pour un accès rapide
        $modeleBoutiquesMap = [];
        foreach ($modeleBoutiques as $mb) {
            $modeleBoutiquesMap[$mb->getId()] = $mb;
        }

        // ⚠️ VALIDATION COMPLÈTE DES STOCKS AVANT TOUTE MODIFICATION
        foreach ($lignes as $index => $ligne) {
            $modeleBoutiqueId = $ligne['modeleBoutiqueId'] ?? null;
            $quantite = $ligne['quantite'] ?? null;

            // Vérifier que les données sont présentes
            if ($modeleBoutiqueId === null) {
                return $this->json([
                    'status' => 'ERROR',
                    'message' => "modeleBoutiqueId manquant à la ligne " . ($index + 1)
                ], 400);
            }

            if ($quantite === null) {
                return $this->json([
                    'status' => 'ERROR',
                    'message' => "quantite manquante à la ligne " . ($index + 1)
                ], 400);
            }

            $quantite = (int)$quantite;

            // ✅ Vérifier que la quantité est positive
            if ($quantite <= 0) {
                return $this->json([
                    'status' => 'ERROR',
                    'message' => "La quantité doit être supérieure à 0 à la ligne " . ($index + 1) .
                        " (valeur: {$quantite})"
                ], 400);
            }

            // ✅ Vérifier que le ModeleBoutique existe
            if (!isset($modeleBoutiquesMap[$modeleBoutiqueId])) {
                return $this->json([
                    'status' => 'ERROR',
                    'message' => "Modèle de boutique introuvable avec ID: {$modeleBoutiqueId} à la ligne " . ($index + 1)
                ], 400);
            }

            $modeleBoutique = $modeleBoutiquesMap[$modeleBoutiqueId];

            // ✅ Vérifier que le ModeleBoutique appartient bien à la boutique
            if ($modeleBoutique->getBoutique()->getId() !== $boutique->getId()) {
                return $this->json([
                    'status' => 'ERROR',
                    'message' => "Le modèle ID {$modeleBoutiqueId} n'appartient pas à la boutique sélectionnée"
                ], 400);
            }

            // ✅ Vérification CRITIQUE de la disponibilité du stock
            $stockDisponible = $modeleBoutique->getQuantite();
            if ($stockDisponible < $quantite) {
                return $this->json([
                    'status' => 'ERROR',
                    'message' => "Stock insuffisant pour le modèle '{$modeleBoutique->getModele()->getNom()}' " .
                        "(disponible: {$stockDisponible}, demandé: {$quantite})"
                ], 400);
            }

            // ✅ Vérifier aussi la quantité globale du modèle
            $modele = $modeleBoutique->getModele();
            if ($modele->getQuantiteGlobale() < $quantite) {
                return $this->json([
                    'status' => 'ERROR',
                    'message' => "Quantité globale insuffisante pour le modèle '{$modele->getNom()}' " .
                        "(disponible globalement: {$modele->getQuantiteGlobale()}, demandé: {$quantite})"
                ], 400);
            }
        }

        // Créer l'EntreStock
        $entreStock = new EntreStock();
        $entreStock->setBoutique($boutique);
        $entreStock->setType('Sortie');
        $entreStock->setEntreprise($this->getUser()->getEntreprise());
        $entreStock->setCreatedBy($this->getUser());
        $entreStock->setUpdatedBy($this->getUser());
        $entreStock->setCreatedAtValue(new \DateTime());
        $entreStock->setUpdatedAt(new \DateTime());

        $errorResponse = $this->errorResponse($entreStock);
        if ($errorResponse !== null) {
            return $errorResponse;
        }

        // 🔒 Transaction pour garantir la cohérence atomique
        $entityManager->beginTransaction();

        try {
            $totalQuantite = 0;

            // Traiter toutes les lignes sans flush intermédiaire
            foreach ($lignes as $ligne) {
                $modeleBoutique = $modeleBoutiquesMap[$ligne['modeleBoutiqueId']];
                $modele = $modeleBoutique->getModele();
                $quantite = (int)$ligne['quantite'];

                // Mise à jour des quantités (déjà validées)
                $modeleBoutique->setQuantite($modeleBoutique->getQuantite() - $quantite);
                $modele->setQuantiteGlobale($modele->getQuantiteGlobale() - $quantite);
                $totalQuantite += $quantite;

                // Création de la ligne de sortie
                $ligneEntre = new LigneEntre();
                $ligneEntre->setQuantite($quantite);
                $ligneEntre->setModele($modeleBoutique);
                $ligneEntre->setEntreStock($entreStock);

                $entityManager->persist($ligneEntre);
                $entreStock->addLigneEntre($ligneEntre);
            }

            $entreStock->setQuantite($totalQuantite);
            $entityManager->persist($entreStock);

            // ✅ Un seul flush pour tout
            $entityManager->flush();
            $entityManager->commit();

            return $this->responseData($entreStock, 'group1', ['Content-Type' => 'application/json']);
        } catch (\Exception $e) {
            $entityManager->rollback();
            return $this->json([
                'status' => 'ERROR',
                'message' => 'Erreur lors de la création de la sortie: ' . $e->getMessage()
            ], 500);
        }
    }
}
