<?php

namespace App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use App\DTO\ModeleDTO;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Entity\Modele;
use App\Repository\ModeleRepository;
use App\Repository\TypeUserRepository;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Attribute\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

/**
 * Contrôleur pour la gestion des modèles de vêtements
 * Permet de créer, lire, mettre à jour et supprimer des modèles avec photos et gestion des quantités globales
 */
#[Route('/api/modele')]
#[OA\Tag(name: 'modele', description: 'Gestion des modèles de vêtements (catalogue produits avec photos)')]
class ApiModeleController extends ApiInterface
{
    /**
     * Liste tous les modèles de vêtements du système
     */
    #[Route('/', methods: ['GET'])]
    #[OA\Get(
        path: "/api/modele/",
        summary: "Lister tous les modèles de vêtements",
        description: "Retourne la liste paginée de tous les modèles de vêtements disponibles dans le système avec leurs photos et quantités globales.",
        tags: ['modele']
    )]
    #[OA\Response(
        response: 200,
        description: "Liste des modèles récupérée avec succès",
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(
                type: "object",
                properties: [
                    new OA\Property(property: "id", type: "integer", example: 1, description: "Identifiant unique du modèle"),
                    new OA\Property(property: "libelle", type: "string", example: "Robe Wax Élégante", description: "Nom du modèle"),
                    new OA\Property(property: "reference", type: "string", example: "MOD-2025-001", description: "Référence unique du modèle"),
                    new OA\Property(property: "description", type: "string", example: "Belle robe en tissu wax avec motifs africains", description: "Description détaillée"),
                    new OA\Property(property: "photo", type: "string", nullable: true, example: "/uploads/modeles/photo_001.jpg", description: "Photo du modèle"),
                    new OA\Property(property: "quantiteGlobale", type: "integer", example: 150, description: "Quantité totale en stock (toutes boutiques confondues)"),
                    new OA\Property(property: "entreprise", type: "object", description: "Entreprise propriétaire"),
                    new OA\Property(property: "succursale", type: "object", nullable: true, description: "Succursale associée"),
                    new OA\Property(property: "createdAt", type: "string", format: "date-time", example: "2025-01-15T10:30:00+00:00")
                ]
            )
        )
    )]
    #[OA\Response(response: 500, description: "Erreur serveur lors de la récupération")]
    public function index(ModeleRepository $modeleRepository): Response
    {
        try {
            $modeles = $this->paginationService->paginate($modeleRepository->findAll());
            $response = $this->responseData($modeles, 'group1', ['Content-Type' => 'application/json']);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            $this->setMessage("Erreur lors de la récupération des modèles");
            $response = $this->response([]);
        }

        return $response;
    }

    /**
     * Liste les modèles selon les droits de l'utilisateur (entreprise ou succursale)
     */
    #[Route('/entreprise', methods: ['GET'])]
    #[OA\Get(
        path: "/api/modele/entreprise",
        summary: "Lister les modèles selon les droits utilisateur",
        description: "Retourne la liste des modèles filtrée selon le type d'utilisateur : Super-admin voit tous les modèles de l'entreprise, autres utilisateurs voient uniquement les modèles de leur succursale. Nécessite un abonnement actif.",
        tags: ['modele']
    )]
    #[OA\Response(
        response: 200,
        description: "Liste des modèles récupérée avec succès",
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(
                type: "object",
                properties: [
                    new OA\Property(property: "id", type: "integer", example: 1),
                    new OA\Property(property: "libelle", type: "string", example: "Robe Wax Élégante"),
                    new OA\Property(property: "reference", type: "string", example: "MOD-2025-001"),
                    new OA\Property(property: "photo", type: "string", nullable: true, example: "/uploads/modeles/photo_001.jpg"),
                    new OA\Property(property: "quantiteGlobale", type: "integer", example: 150),
                    new OA\Property(property: "entreprise", type: "object"),
                    new OA\Property(property: "succursale", type: "object", nullable: true)
                ]
            )
        )
    )]
    #[OA\Response(response: 401, description: "Non authentifié")]
    #[OA\Response(response: 403, description: "Abonnement requis pour cette fonctionnalité")]
    #[OA\Response(response: 500, description: "Erreur lors de la récupération")]
    public function indexAll(ModeleRepository $modeleRepository, TypeUserRepository $typeUserRepository): Response
    {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        try {
            if ($this->getUser()->getType() == $typeUserRepository->findOneBy(['code' => 'SADM'])) {
                $modeles = $this->paginationService->paginate($modeleRepository->findBy(
                    ['entreprise' => $this->getUser()->getEntreprise()],
                    ['id' => 'DESC']
                ));
            } else {
                $modeles = $this->paginationService->paginate($modeleRepository->findBy(
                    ['surccursale' => $this->getUser()->getSurccursale()],
                    ['id' => 'DESC']
                ));
            }

            $response = $this->responseData($modeles, 'group1', ['Content-Type' => 'application/json']);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            $this->setMessage("Erreur lors de la récupération des modèles");
            $response = $this->response([]);
        }

        return $response;
    }

    /**
     * Récupère les détails d'un modèle spécifique
     */
    #[Route('/get/one/{id}', methods: ['GET'])]
    #[OA\Get(
        path: "/api/modele/get/one/{id}",
        summary: "Détails d'un modèle",
        description: "Affiche les informations détaillées d'un modèle de vêtement spécifique, incluant sa photo, sa description et sa quantité globale en stock. Nécessite un abonnement actif.",
        tags: ['modele']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: "Identifiant unique du modèle",
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: "Modèle trouvé avec succès",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "id", type: "integer", example: 1),
                new OA\Property(property: "libelle", type: "string", example: "Robe Wax Élégante"),
                new OA\Property(property: "reference", type: "string", example: "MOD-2025-001"),
                new OA\Property(property: "description", type: "string", example: "Belle robe en tissu wax avec motifs africains"),
                new OA\Property(property: "photo", type: "string", nullable: true, example: "/uploads/modeles/photo_001.jpg"),
                new OA\Property(property: "quantiteGlobale", type: "integer", example: 150, description: "Stock total toutes boutiques"),
                new OA\Property(property: "entreprise", type: "object", description: "Entreprise propriétaire"),
                new OA\Property(property: "succursale", type: "object", nullable: true, description: "Succursale associée"),
                new OA\Property(property: "modeleBoutiques", type: "array", description: "Répartition par boutique", items: new OA\Items(type: "object")),
                new OA\Property(property: "createdAt", type: "string", format: "date-time"),
                new OA\Property(property: "updatedAt", type: "string", format: "date-time")
            ]
        )
    )]
    #[OA\Response(response: 401, description: "Non authentifié")]
    #[OA\Response(response: 403, description: "Abonnement requis pour cette fonctionnalité")]
    #[OA\Response(response: 404, description: "Modèle non trouvé")]
    public function getOne(?Modele $modele): Response
    {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        try {
            if ($modele) {
                $response = $this->response($modele);
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
     * Crée un nouveau modèle de vêtement avec photo optionnelle
     */
    #[Route('/create', methods: ['POST'])]
    #[OA\Post(
        path: "/api/modele/create",
        summary: "Créer un nouveau modèle",
        description: "Permet de créer un nouveau modèle de vêtement avec son nom, une quantité initiale et une photo optionnelle. Le modèle sera associé à l'entreprise de l'utilisateur. Nécessite un abonnement actif.",
        tags: ['modele']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\MediaType(
            mediaType: "multipart/form-data",
            schema: new OA\Schema(
                type: "object",
                required: ["libelle", "quantite"],
                properties: [
                    new OA\Property(
                        property: "libelle",
                        type: "string",
                        example: "Robe Wax Élégante",
                        description: "Nom du modèle de vêtement (obligatoire)"
                    ),
                    new OA\Property(
                        property: "quantite",
                        type: "integer",
                        example: 0,
                        description: "Quantité globale initiale en stock (obligatoire, généralement 0 au départ)"
                    ),
                    new OA\Property(
                        property: "photo",
                        type: "string",
                        format: "binary",
                        description: "Photo du modèle (optionnel, formats acceptés: JPG, PNG)"
                    )
                ]
            )
        )
    )]
    #[OA\Response(
        response: 201,
        description: "Modèle créé avec succès",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "id", type: "integer", example: 15),
                new OA\Property(property: "libelle", type: "string", example: "Robe Wax Élégante"),
                new OA\Property(property: "reference", type: "string", example: "MOD-2025-015", description: "Référence auto-générée"),
                new OA\Property(property: "photo", type: "string", nullable: true, example: "/uploads/modeles/document_01_abc123.jpg"),
                new OA\Property(property: "quantiteGlobale", type: "integer", example: 0),
                new OA\Property(property: "entreprise", type: "object"),
                new OA\Property(property: "createdAt", type: "string", format: "date-time")
            ]
        )
    )]
    #[OA\Response(response: 400, description: "Données invalides ou fichier non accepté")]
    #[OA\Response(response: 401, description: "Non authentifié")]
    #[OA\Response(response: 403, description: "Abonnement requis pour cette fonctionnalité")]
    public function create(Request $request, ModeleRepository $modeleRepository): Response
    {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        $names = 'document_' . '01';
        $filePrefix = str_slug($names);
        $filePath = $this->getUploadDir(self::UPLOAD_PATH, true);

        $modele = new Modele();
        $modele->setLibelle($request->get('libelle'));
        $modele->setIsActive(true);
        $modele->setQuantiteGlobale($request->get('quantite') ?? 0);
        $modele->setEntreprise($this->getUser()->getEntreprise());

        // Upload de la photo si fournie
        $uploadedFile = $request->files->get('photo');
        if ($uploadedFile) {
            if ($fichier = $this->utils->sauvegardeFichier($filePath, $filePrefix, $uploadedFile, self::UPLOAD_PATH)) {
                $modele->setPhoto($fichier);
            }
        }

        $modele->setCreatedBy($this->getUser());
        $modele->setUpdatedBy($this->getUser());
        $modele->setCreatedAtValue(new \DateTime());
        $modele->setUpdatedAt(new \DateTime());

        $errorResponse = $this->errorResponse($modele);
        if ($errorResponse !== null) {
            return $errorResponse;
        } else {
            $modeleRepository->add($modele, true);
        }

        return $this->responseData($modele, 'group1', ['Content-Type' => 'application/json']);
    }

    /**
     * Met à jour un modèle existant avec possibilité de changer la photo
     */
    #[Route('/update/{id}', methods: ['PUT', 'POST'])]
    #[OA\Put(
        path: "/api/modele/update/{id}",
        summary: "Mettre à jour un modèle",
        description: "Permet de mettre à jour les informations d'un modèle de vêtement, y compris son nom, sa quantité globale et sa photo. Note : La quantité globale est normalement gérée automatiquement via les mouvements de stock. Nécessite un abonnement actif.",
        tags: ['modele']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: "Identifiant unique du modèle à mettre à jour",
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\MediaType(
            mediaType: "multipart/form-data",
            schema: new OA\Schema(
                type: "object",
                properties: [
                    new OA\Property(
                        property: "libelle",
                        type: "string",
                        example: "Robe Wax Élégante (Nouvelle Collection)",
                        description: "Nouveau nom du modèle"
                    ),
                    new OA\Property(
                        property: "quantite",
                        type: "integer",
                        example: 150,
                        description: "Nouvelle quantité globale (généralement mise à jour automatiquement)"
                    ),
                    new OA\Property(
                        property: "photo",
                        type: "string",
                        format: "binary",
                        description: "Nouvelle photo du modèle (optionnel)"
                    )
                ]
            )
        )
    )]
    #[OA\Response(
        response: 200,
        description: "Modèle mis à jour avec succès",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "id", type: "integer", example: 1),
                new OA\Property(property: "libelle", type: "string", example: "Robe Wax Élégante (Nouvelle Collection)"),
                new OA\Property(property: "quantiteGlobale", type: "integer", example: 150),
                new OA\Property(property: "photo", type: "string", nullable: true),
                new OA\Property(property: "updatedAt", type: "string", format: "date-time")
            ]
        )
    )]
    #[OA\Response(response: 400, description: "Données invalides ou fichier non accepté")]
    #[OA\Response(response: 401, description: "Non authentifié")]
    #[OA\Response(response: 403, description: "Abonnement requis pour cette fonctionnalité")]
    #[OA\Response(response: 404, description: "Modèle non trouvé")]
    public function update(Request $request, Modele $modele, ModeleRepository $modeleRepository): Response
    {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        try {
            $names = 'document_' . '01';
            $filePrefix = str_slug($names);
            $filePath = $this->getUploadDir(self::UPLOAD_PATH, true);

            if ($modele != null) {
                if ($request->get('libelle')) {
                    $modele->setLibelle($request->get('libelle'));
                }

                if ($request->get('quantite') !== null) {
                    $modele->setQuantiteGlobale($request->get('quantite'));
                }

                // Upload de la nouvelle photo si fournie
                $uploadedFile = $request->files->get('photo');
                if ($uploadedFile) {
                    if ($fichier = $this->utils->sauvegardeFichier($filePath, $filePrefix, $uploadedFile, self::UPLOAD_PATH)) {
                        $modele->setPhoto($fichier);
                    }
                }

                $modele->setUpdatedBy($this->getUser());
                $modele->setUpdatedAt(new \DateTime());

                $errorResponse = $this->errorResponse($modele);
                if ($errorResponse !== null) {
                    return $errorResponse;
                } else {
                    $modeleRepository->add($modele, true);
                }

                $response = $this->responseData($modele, 'group1', ['Content-Type' => 'application/json']);
            } else {
                $this->setMessage("Cette ressource est inexistante");
                $this->setStatusCode(404);
                $response = $this->response([]);
            }
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            $this->setMessage("Erreur lors de la mise à jour du modèle");
            $response = $this->response([]);
        }
        return $response;
    }

    /**
     * Supprime un modèle de vêtement
     */
    #[Route('/delete/{id}', methods: ['DELETE'])]
    #[OA\Delete(
        path: "/api/modele/delete/{id}",
        summary: "Supprimer un modèle",
        description: "Permet de supprimer définitivement un modèle de vêtement par son identifiant. Attention : cette action supprime également toutes les associations avec les boutiques (ModeleBoutique) et l'historique de stock. Nécessite un abonnement actif.",
        tags: ['modele']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: "Identifiant unique du modèle à supprimer",
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: "Modèle supprimé avec succès",
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
    #[OA\Response(response: 404, description: "Modèle non trouvé")]
    #[OA\Response(response: 500, description: "Erreur lors de la suppression")]
    public function delete(Request $request, Modele $modele, ModeleRepository $villeRepository): Response
    {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        try {
            if ($modele != null) {
                $villeRepository->remove($modele, true);
                $this->setMessage("Operation effectuées avec succès");
                $response = $this->response($modele);
            } else {
                $this->setMessage("Cette ressource est inexistante");
                $this->setStatusCode(404);
                $response = $this->response([]);
            }
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            $this->setMessage("Erreur lors de la suppression du modèle");
            $response = $this->response([]);
        }
        return $response;
    }

    /**
     * Supprime plusieurs modèles en masse
     */
    #[Route('/delete/all/items', methods: ['DELETE'])]
    #[OA\Delete(
        path: "/api/modele/delete/all/items",
        summary: "Supprimer plusieurs modèles",
        description: "Permet de supprimer plusieurs modèles de vêtements en une seule opération en fournissant un tableau d'identifiants. Toutes les associations et l'historique de stock seront également supprimés. Nécessite un abonnement actif.",
        tags: ['modele']
    )]
    #[OA\RequestBody(
        required: true,
        description: "Tableau des identifiants des modèles à supprimer",
        content: new OA\JsonContent(
            type: "object",
            required: ["ids"],
            properties: [
                new OA\Property(
                    property: 'ids',
                    type: 'array',
                    description: "Liste des identifiants des modèles à supprimer",
                    items: new OA\Items(type: 'integer', example: 1),
                    example: [1, 2, 3, 5, 8]
                )
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: "Modèles supprimés avec succès",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "message", type: "string", example: "Operation effectuées avec succès"),
                new OA\Property(property: "deletedCount", type: "integer", example: 5, description: "Nombre de modèles supprimés")
            ]
        )
    )]
    #[OA\Response(response: 400, description: "Données invalides")]
    #[OA\Response(response: 401, description: "Non authentifié")]
    #[OA\Response(response: 403, description: "Abonnement requis pour cette fonctionnalité")]
    #[OA\Response(response: 500, description: "Erreur lors de la suppression")]
    public function deleteAll(Request $request, ModeleRepository $villeRepository): Response
    {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        try {
            $data = json_decode($request->getContent(), true);

            foreach ($data['ids'] as $id) {
                $modele = $villeRepository->find($id);

                if ($modele != null) {
                    $villeRepository->remove($modele);
                }
            }
            $this->setMessage("Operation effectuées avec succès");
            $response = $this->response([]);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            $this->setMessage("Erreur lors de la suppression des modèles");
            $response = $this->response([]);
        }
        return $response;
    }
}