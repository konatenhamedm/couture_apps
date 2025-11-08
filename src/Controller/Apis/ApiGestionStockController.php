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
#[OA\Tag(name: 'stock', description: 'Gestion des entrées et sorties de stock des boutiques')]
class ApiGestionStockController extends ApiInterface
{
    /**
     * Liste tous les mouvements de stock (entrées et sorties) d'une boutique
     */
    #[Route('/{id}', methods: ['GET'])]
    #[OA\Get(
        path: "/api/stock/{id}",
        summary: "Historique des mouvements de stock d'une boutique",
        description: "Retourne la liste paginée de tous les mouvements de stock (entrées et sorties) d'une boutique spécifique, permettant de suivre l'historique complet des variations de stock.",
        tags: ['stock']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: "Identifiant unique de la boutique",
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: "Historique des mouvements de stock récupéré avec succès",
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(
                type: "object",
                properties: [
                    new OA\Property(property: "id", type: "integer", example: 1, description: "Identifiant du mouvement de stock"),
                    new OA\Property(property: "type", type: "string", enum: ["Entree", "Sortie"], example: "Entree", description: "Type de mouvement"),
                    new OA\Property(property: "quantite", type: "integer", example: 50, description: "Quantité totale du mouvement"),
                    new OA\Property(property: "boutique", type: "object", description: "Boutique concernée",
                        properties: [
                            new OA\Property(property: "id", type: "integer", example: 1),
                            new OA\Property(property: "libelle", type: "string", example: "Boutique Centre-Ville")
                        ]
                    ),
                    new OA\Property(property: "entreprise", type: "object", description: "Entreprise"),
                    new OA\Property(property: "ligneEntres", type: "array", description: "Détails des lignes de stock",
                        items: new OA\Items(
                            type: "object",
                            properties: [
                                new OA\Property(property: "id", type: "integer", example: 1),
                                new OA\Property(property: "quantite", type: "integer", example: 10),
                                new OA\Property(property: "modele", type: "object", description: "Modèle concerné")
                            ]
                        )
                    ),
                    new OA\Property(property: "createdAt", type: "string", format: "date-time", example: "2025-01-15T10:30:00+00:00"),
                    new OA\Property(property: "createdBy", type: "object", description: "Utilisateur ayant créé le mouvement")
                ]
            )
        )
    )]
    #[OA\Response(response: 401, description: "Non authentifié")]
    #[OA\Response(response: 403, description: "Abonnement requis pour cette fonctionnalité")]
    #[OA\Response(response: 404, description: "Boutique non trouvée")]
    #[OA\Response(response: 500, description: "Erreur lors de la récupération")]
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
        summary: "Historique des mouvements d'un modèle",
        description: "Retourne la liste paginée de toutes les lignes d'entrées et sorties de stock pour un modèle spécifique dans une boutique, permettant de tracer tous les mouvements de ce modèle.",
        tags: ['stock']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: "Identifiant unique du modèle de boutique (ModeleBoutique)",
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: "Historique des mouvements du modèle récupéré avec succès",
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(
                type: "object",
                properties: [
                    new OA\Property(property: "id", type: "integer", example: 1, description: "Identifiant de la ligne de stock"),
                    new OA\Property(property: "quantite", type: "integer", example: 10, description: "Quantité concernée par cette ligne"),
                    new OA\Property(property: "modele", type: "object", description: "Modèle de boutique concerné",
                        properties: [
                            new OA\Property(property: "id", type: "integer", example: 1),
                            new OA\Property(property: "quantite", type: "integer", example: 150, description: "Quantité totale actuelle en stock"),
                            new OA\Property(property: "modele", type: "object", description: "Modèle parent")
                        ]
                    ),
                    new OA\Property(property: "entreStock", type: "object", description: "Mouvement de stock parent",
                        properties: [
                            new OA\Property(property: "id", type: "integer", example: 5),
                            new OA\Property(property: "type", type: "string", example: "Entree"),
                            new OA\Property(property: "createdAt", type: "string", format: "date-time")
                        ]
                    ),
                    new OA\Property(property: "createdAt", type: "string", format: "date-time")
                ]
            )
        )
    )]
    #[OA\Response(response: 401, description: "Non authentifié")]
    #[OA\Response(response: 403, description: "Abonnement requis pour cette fonctionnalité")]
    #[OA\Response(response: 404, description: "Modèle de boutique non trouvé")]
    #[OA\Response(response: 500, description: "Erreur lors de la récupération")]
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
        ModeleBoutiqueRepository $modeleBoutiqueRepository
    ): Response {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        $totalQuantite = 0;

        $data = json_decode($request->getContent(), true);
        
        $entreStock = new EntreStock();
        $entreStock->setBoutique($boutiqueRepository->find($data['boutiqueId']));
        $entreStock->setType('Entree');
        $entreStock->setEntreprise($this->getUser()->getEntreprise());
        $entreStock->setCreatedBy($this->getUser());
        $entreStock->setUpdatedBy($this->getUser());
        $entreStock->setCreatedAtValue(new \DateTime());
        $entreStock->setUpdatedAt(new \DateTime());

        $errorResponse = $this->errorResponse($entreStock);
        if ($errorResponse !== null) {
            return $errorResponse;
        }

        $lignes = $data['lignes'] ?? [];

        if (isset($lignes) && is_array($lignes)) {
            foreach ($lignes as $ligne) {
                $modeleBoutique = $modeleBoutiqueRepository->find($ligne['modeleBoutiqueId']);
                
                if (!$modeleBoutique) {
                    $this->setMessage("Modèle de boutique introuvable avec l'ID: " . $ligne['modeleBoutiqueId']);
                    return $this->response('[]', 400);
                }

                $modele = $modeleRepository->find($modeleBoutique->getModele()->getId());
                $quantite = (int)$ligne['quantite'];
                $totalQuantite += $quantite;

                // Création de la ligne d'entrée
                $ligneEntre = new LigneEntre();
                $ligneEntre->setQuantite($quantite);
                $ligneEntre->setModele($modeleBoutique);
                $ligneEntre->setEntreStock($entreStock);
                
                $ligneEntreRepository->add($ligneEntre, true);

                // Mise à jour des quantités
                $modeleBoutique->setQuantite($modeleBoutique->getQuantite() + $quantite);
                $modeleBoutiqueRepository->add($modeleBoutique, true);

                $modele->setQuantiteGlobale($modele->getQuantiteGlobale() + $quantite);
                $modeleRepository->add($modele, true);

                $entreStock->addLigneEntre($ligneEntre);
            }
        }

        $entreStock->setQuantite($totalQuantite);
        $entreStockRepository->add($entreStock, true);

        return $this->responseData($entreStock, 'group1', ['Content-Type' => 'application/json']);
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
        $entreStock->setCreatedAtValue(new \DateTime());
        $entreStock->setUpdatedAt(new \DateTime());

        $errorResponse = $this->errorResponse($entreStock);
        if ($errorResponse !== null) {
            return $errorResponse;
        }

        $lignes = $data['lignes'] ?? [];
        if (is_array($lignes)) {
            foreach ($lignes as $ligne) {
                $modeleBoutique = $modeleBoutiqueRepository->find($ligne['modeleBoutiqueId']);

                if (!$modeleBoutique) {
                    return $this->json([
                        'status' => 'ERROR',
                        'message' => 'Modèle de boutique introuvable avec ID: ' . $ligne['modeleBoutiqueId']
                    ], 400);
                }

                $modele = $modeleRepository->find($modeleBoutique->getModele()->getId());
                $quantite = (int)$ligne['quantite'];

                // Vérification de la disponibilité du stock
                if ($modeleBoutique->getQuantite() < $quantite) {
                    return $this->json([
                        'status' => 'ERROR',
                        'message' => "Stock insuffisant pour le modèle ID {$modeleBoutique->getId()} " .
                                   "(disponible: {$modeleBoutique->getQuantite()}, demandé: {$quantite})"
                    ], 400);
                }

                // Mise à jour des quantités
                $modeleBoutique->setQuantite($modeleBoutique->getQuantite() - $quantite);
                $totalQuantite += $quantite;

                $ligneEntre = new LigneEntre();
                $ligneEntre->setQuantite($quantite);
                $ligneEntre->setModele($modeleBoutique);
                $ligneEntre->setEntreStock($entreStock);

                $ligneEntreRepository->add($ligneEntre, true);
                $modeleBoutiqueRepository->add($modeleBoutique, true);

                if ($modele->getQuantiteGlobale() >= $quantite) {
                    $modele->setQuantiteGlobale($modele->getQuantiteGlobale() - $quantite);
                    $modeleRepository->add($modele, true);
                }

                $entreStock->addLigneEntre($ligneEntre);
            }
        }

        $entreStock->setQuantite($totalQuantite);
        $entreStockRepository->add($entreStock, true);

        return $this->responseData($entreStock, 'group1', ['Content-Type' => 'application/json']);
    }
}