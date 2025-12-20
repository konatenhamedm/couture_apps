<?php

namespace App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use App\DTO\OperateurDTO;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Entity\Operateur;
use App\Entity\Pays;
use App\Repository\OperateurRepository;
use App\Repository\PaysRepository;
use App\Repository\UserRepository;
use DateTime;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Attribute\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

/**
 * Contrôleur pour la gestion des opérateurs téléphoniques
 * Permet de gérer les opérateurs de téléphonie mobile (Orange, MTN, Moov, etc.) par pays
 */
#[Route('/api/operateur')]
#[OA\Tag(name: 'operateur', description: 'Gestion des opérateurs téléphoniques par pays (Orange, MTN, Moov, etc.)')]
class ApiOperateurController extends ApiInterface
{
    /**
     * Liste tous les opérateurs téléphoniques du système
     */
    #[Route('/', methods: ['GET'])]
    #[OA\Get(
        path: "/api/operateur/",
        summary: "Lister tous les opérateurs téléphoniques",
        description: "Retourne la liste paginée de tous les opérateurs téléphoniques disponibles dans le système, tous pays confondus.",
        tags: ['operateur']
    )]
    #[OA\Response(
        response: 200,
        description: "Liste des opérateurs récupérée avec succès",
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(
                type: "object",
                properties: [
                    new OA\Property(property: "id", type: "integer", example: 1, description: "Identifiant unique de l'opérateur"),
                    new OA\Property(property: "libelle", type: "string", example: "Orange Côte d'Ivoire", description: "Nom de l'opérateur"),
                    new OA\Property(property: "code", type: "string", example: "ORANGE_CI", description: "Code unique de l'opérateur"),
                    new OA\Property(property: "photo", type: "string", nullable: true, example: "/uploads/operateurs/orange_logo.png", description: "Logo de l'opérateur"),
                    new OA\Property(property: "actif", type: "boolean", example: true, description: "Statut actif/inactif"),
                    new OA\Property(
                        property: "pays",
                        type: "object",
                        nullable: true,
                        description: "Pays associé",
                        properties: [
                            new OA\Property(property: "id", type: "integer", example: 1),
                            new OA\Property(property: "libelle", type: "string", example: "Côte d'Ivoire"),
                            new OA\Property(property: "code", type: "string", example: "CI")
                        ]
                    ),
                    new OA\Property(property: "createdAt", type: "string", format: "date-time", example: "2025-01-15T10:30:00+00:00")
                ]
            )
        )
    )]
    #[OA\Response(response: 500, description: "Erreur serveur lors de la récupération")]
    public function index(OperateurRepository $operateurRepository): Response
    {
        try {
            $operateurs = $this->paginationService->paginate($operateurRepository->findAllInEnvironment());
            $response = $this->responseData($operateurs, 'group1', ['Content-Type' => 'application/json']);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            $this->setMessage("Erreur lors de la récupération des opérateurs");
            $response = $this->response([]);
        }

        return $response;
    }

    /**
     * Liste les opérateurs actifs d'un pays spécifique
     */
    #[Route('/{id}', methods: ['GET'])]
    #[OA\Get(
        path: "/api/operateur/{id}",
        summary: "Lister les opérateurs d'un pays",
        description: "Retourne la liste paginée de tous les opérateurs téléphoniques actifs pour un pays spécifique. Permet de filtrer les opérateurs disponibles par pays (par exemple, tous les opérateurs en Côte d'Ivoire).",
        tags: ['operateur']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: "Identifiant unique du pays",
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: "Liste des opérateurs du pays récupérée avec succès",
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(
                type: "object",
                properties: [
                    new OA\Property(property: "id", type: "integer", example: 1),
                    new OA\Property(property: "libelle", type: "string", example: "Orange Côte d'Ivoire"),
                    new OA\Property(property: "code", type: "string", example: "ORANGE_CI"),
                    new OA\Property(property: "photo", type: "string", nullable: true, example: "/uploads/operateurs/orange_logo.png"),
                    new OA\Property(property: "actif", type: "boolean", example: true, description: "Toujours true dans ce endpoint"),
                    new OA\Property(property: "pays", type: "object", description: "Pays associé")
                ]
            )
        )
    )]
    #[OA\Response(response: 404, description: "Pays non trouvé")]
    #[OA\Response(response: 500, description: "Erreur lors de la récupération")]
    public function indexByPays(OperateurRepository $operateurRepository, Pays $pays): Response
    {
        try {
            $operateurs = $this->paginationService->paginate($operateurRepository->findByInEnvironment(
                ['pays' => $pays->getId(), 'actif' => true],
                ['libelle' => 'ASC']
            ));

            $response = $this->responseData($operateurs, 'group1', ['Content-Type' => 'application/json']);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            $this->setMessage("Erreur lors de la récupération des opérateurs du pays");
            $response = $this->response([]);
        }

        return $response;
    }

    /**
     * Récupère les détails d'un opérateur spécifique
     */
    #[Route('/get/one/{id}', methods: ['GET'])]
    #[OA\Get(
        path: "/api/operateur/get/one/{id}",
        summary: "Détails d'un opérateur",
        description: "Affiche les informations détaillées d'un opérateur téléphonique spécifique, incluant son logo, son code et son pays d'opération.",
        tags: ['operateur']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: "Identifiant unique de l'opérateur",
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: "Opérateur trouvé avec succès",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "id", type: "integer", example: 1),
                new OA\Property(property: "libelle", type: "string", example: "Orange Côte d'Ivoire"),
                new OA\Property(property: "code", type: "string", example: "ORANGE_CI"),
                new OA\Property(property: "photo", type: "string", nullable: true, example: "/uploads/operateurs/orange_logo.png"),
                new OA\Property(property: "actif", type: "boolean", example: true),
                new OA\Property(
                    property: "pays",
                    type: "object",
                    nullable: true,
                    description: "Pays d'opération",
                    properties: [
                        new OA\Property(property: "id", type: "integer", example: 1),
                        new OA\Property(property: "libelle", type: "string", example: "Côte d'Ivoire"),
                        new OA\Property(property: "code", type: "string", example: "CI"),
                        new OA\Property(property: "indicatif", type: "string", example: "+225")
                    ]
                ),
                new OA\Property(property: "createdAt", type: "string", format: "date-time"),
                new OA\Property(property: "updatedAt", type: "string", format: "date-time")
            ]
        )
    )]
    #[OA\Response(response: 404, description: "Opérateur non trouvé")]
    public function getOne(int $id, OperateurRepository $operateurRepository): Response
    {
        
        $operateur = $operateurRepository->findInEnvironment($id);
        try {
            if ($operateur) {
                $response = $this->response($operateur);
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
     * Crée un nouvel opérateur téléphonique avec logo optionnel
     */
    #[Route('/create', methods: ['POST'])]
    #[OA\Post(
        path: "/api/operateur/create",
        summary: "Créer un opérateur téléphonique",
        description: "Permet de créer un nouveau opérateur téléphonique avec son nom, son code unique et un logo optionnel. L'opérateur sera créé avec le statut actif par défaut.",
        tags: ['operateur']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\MediaType(
            mediaType: "multipart/form-data",
            schema: new OA\Schema(
                type: "object",
                required: ["libelle", "code"],
                properties: [
                    new OA\Property(
                        property: "libelle",
                        type: "string",
                        example: "Orange Côte d'Ivoire",
                        description: "Nom complet de l'opérateur (obligatoire)"
                    ),
                    new OA\Property(
                        property: "code",
                        type: "string",
                        example: "ORANGE_CI",
                        description: "Code unique identifiant l'opérateur (obligatoire, format: MAJUSCULES_PAYS)"
                    ),
                    new OA\Property(
                        property: "photo",
                        type: "string",
                        format: "binary",
                        description: "Logo de l'opérateur (optionnel, formats acceptés: JPG, PNG)"
                    )
                ]
            )
        )
    )]
    #[OA\Response(
        response: 201,
        description: "Opérateur créé avec succès",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "id", type: "integer", example: 15),
                new OA\Property(property: "libelle", type: "string", example: "Orange Côte d'Ivoire"),
                new OA\Property(property: "code", type: "string", example: "ORANGE_CI"),
                new OA\Property(property: "photo", type: "string", nullable: true, example: "/uploads/operateurs/document_01_abc123.png"),
                new OA\Property(property: "actif", type: "boolean", example: true),
                new OA\Property(property: "createdAt", type: "string", format: "date-time")
            ]
        )
    )]
    #[OA\Response(response: 400, description: "Données invalides ou fichier non accepté")]
    #[OA\Response(response: 401, description: "Non authentifié")]
    public function create(Request $request, OperateurRepository $operateurRepository): Response
    {
        $names = 'document_' . '01';
        $filePrefix = str_slug($names);
        $filePath = $this->getUploadDir(self::UPLOAD_PATH, true);
        $libelle = $request->request->get('libelle');
        $code = $request->request->get('code');

        $operateur = new Operateur();
        $operateur->setLibelle($libelle);
        $operateur->setCode($code);
        $operateur->setActif(true);
        $operateur->setIsActive(true);
        $operateur->setCreatedBy($this->getManagedUser());
        $operateur->setUpdatedBy($this->getManagedUser());
        $operateur->setCreatedAtValue();
        $operateur->setUpdatedAt();

        // Upload du logo si fourni
        $uploadedFile = $request->files->get('photo');
        if ($uploadedFile) {
            if ($fichier = $this->utils->sauvegardeFichier($filePath, $filePrefix, $uploadedFile, self::UPLOAD_PATH)) {
                $operateur->setPhoto($fichier);
            }
        }

        $errorResponse = $this->errorResponse($operateur);
        if ($errorResponse !== null) {
            return $errorResponse;
        }

        $operateurRepository->add($operateur, true);

        return $this->responseData($operateur, 'group1', ['Content-Type' => 'application/json']);
    }

    /**
     * Met à jour un opérateur existant avec possibilité de changer le logo
     */
    #[Route('/update/{id}', methods: ['PUT', 'POST'])]
    #[OA\Put(
        path: "/api/operateur/update/{id}",
        summary: "Mettre à jour un opérateur",
        description: "Permet de mettre à jour les informations d'un opérateur téléphonique, incluant son nom, son code, son statut actif/inactif, son pays d'opération et son logo.",
        tags: ['operateur']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: "Identifiant unique de l'opérateur à mettre à jour",
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\MediaType(
            mediaType: "multipart/form-data",
            schema: new OA\Schema(
                type: "object",
                required: ["libelle", "code"],
                properties: [
                    new OA\Property(
                        property: "libelle",
                        type: "string",
                        example: "Orange Côte d'Ivoire (Nouvelle Génération)",
                        description: "Nouveau nom de l'opérateur (obligatoire)"
                    ),
                    new OA\Property(
                        property: "code",
                        type: "string",
                        example: "ORANGE_CI",
                        description: "Code de l'opérateur (obligatoire)"
                    ),
                    new OA\Property(
                        property: "pays",
                        type: "integer",
                        example: 1,
                        description: "ID du pays d'opération (optionnel)"
                    ),
                    new OA\Property(
                        property: "actif",
                        type: "boolean",
                        example: true,
                        description: "Statut actif (true) ou inactif (false)"
                    ),
                    new OA\Property(
                        property: "photo",
                        type: "string",
                        format: "binary",
                        description: "Nouveau logo de l'opérateur (optionnel)"
                    )
                ]
            )
        )
    )]
    #[OA\Response(
        response: 200,
        description: "Opérateur mis à jour avec succès",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "id", type: "integer", example: 1),
                new OA\Property(property: "libelle", type: "string", example: "Orange Côte d'Ivoire (Nouvelle Génération)"),
                new OA\Property(property: "code", type: "string", example: "ORANGE_CI"),
                new OA\Property(property: "actif", type: "boolean", example: true),
                new OA\Property(property: "pays", type: "object"),
                new OA\Property(property: "updatedAt", type: "string", format: "date-time")
            ]
        )
    )]
    #[OA\Response(response: 400, description: "Données invalides ou fichier non accepté")]
    #[OA\Response(response: 401, description: "Non authentifié")]
    #[OA\Response(response: 404, description: "Opérateur non trouvé")]
    public function update(Request $request, PaysRepository $paysRepository, Operateur $operateur, OperateurRepository $operateurRepository): Response
    {
        try {
            $names = 'document_' . '01';
            $filePrefix = str_slug($names);
            $filePath = $this->getUploadDir(self::UPLOAD_PATH, true);
            $libelle = $request->get('libelle');
            $code = $request->get('code');
            $pays = $request->get('pays');
            $actif = $request->get('actif');

            if ($operateur !== null) {
                if ($libelle) {
                    $operateur->setLibelle($libelle);
                }
                if ($code) {
                    $operateur->setCode($code);
                }
                if ($actif !== null) {
                    $operateur->setActif($actif);
                }
                if ($pays) {
                    $paysEntity = $paysRepository->findInEnvironment($pays);
                    if ($paysEntity) {
                        $operateur->setPays($paysEntity);
                    }
                }

                $operateur->setUpdatedBy($this->getManagedUser());
                $operateur->setUpdatedAt();

                // Upload du nouveau logo si fourni
                $uploadedFile = $request->files->get('photo');
                if ($uploadedFile) {
                    if ($fichier = $this->utils->sauvegardeFichier($filePath, $filePrefix, $uploadedFile, self::UPLOAD_PATH)) {
                        $operateur->setPhoto($fichier);
                    }
                }

                $errorResponse = $this->errorResponse($operateur);
                if ($errorResponse !== null) {
                    return $errorResponse;
                }

                $operateurRepository->add($operateur, true);
                return $this->responseData($operateur, 'group1', ['Content-Type' => 'application/json']);
            } else {
                $this->setMessage("Cette ressource est inexistante");
                $this->setStatusCode(404);
                return $this->response('[]');
            }
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            $this->setMessage("Erreur lors de la mise à jour de l'opérateur");
            return $this->response('[]');
        }
    }

    /**
     * Supprime un opérateur téléphonique
     */
    #[Route('/delete/{id}', methods: ['DELETE'])]
    #[OA\Delete(
        path: "/api/operateur/delete/{id}",
        summary: "Supprimer un opérateur",
        description: "Permet de supprimer définitivement un opérateur téléphonique par son identifiant. Attention : cette action est irréversible.",
        tags: ['operateur']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: "Identifiant unique de l'opérateur à supprimer",
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: "Opérateur supprimé avec succès",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "message", type: "string", example: "Operation effectuées avec succès"),
                new OA\Property(property: "deleted", type: "boolean", example: true)
            ]
        )
    )]
    #[OA\Response(response: 401, description: "Non authentifié")]
    #[OA\Response(response: 404, description: "Opérateur non trouvé")]
    #[OA\Response(response: 500, description: "Erreur lors de la suppression")]
    public function delete(Request $request, int $id, OperateurRepository $villeRepository): Response
    {
        try {
            $operateur = $villeRepository->findInEnvironment($id);
            if ($operateur != null) {
                $villeRepository->remove($operateur, true);
                $this->setMessage("Operation effectuées avec succès");
                $response = $this->response($operateur);
            } else {
                $this->setMessage("Cette ressource est inexistante");
                $this->setStatusCode(404);
                $response = $this->response([]);
            }
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            $this->setMessage("Erreur lors de la suppression de l'opérateur");
            $response = $this->response([]);
        }
        return $response;
    }

    /**
     * Supprime plusieurs opérateurs en masse
     */
    #[Route('/delete/all/items', methods: ['DELETE'])]
    #[OA\Delete(
        path: "/api/operateur/delete/all/items",
        summary: "Supprimer plusieurs opérateurs",
        description: "Permet de supprimer plusieurs opérateurs téléphoniques en une seule opération en fournissant un tableau d'identifiants.",
        tags: ['operateur']
    )]
    #[OA\RequestBody(
        required: true,
        description: "Tableau des identifiants des opérateurs à supprimer",
        content: new OA\JsonContent(
            type: "object",
            required: ["ids"],
            properties: [
                new OA\Property(
                    property: 'ids',
                    type: 'array',
                    description: "Liste des identifiants des opérateurs à supprimer",
                    items: new OA\Items(type: 'integer', example: 1),
                    example: [1, 2, 3, 5, 8]
                )
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: "Opérateurs supprimés avec succès",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "message", type: "string", example: "Operation effectuées avec succès"),
                new OA\Property(property: "deletedCount", type: "integer", example: 5, description: "Nombre d'opérateurs supprimés")
            ]
        )
    )]
    #[OA\Response(response: 400, description: "Données invalides")]
    #[OA\Response(response: 401, description: "Non authentifié")]
    #[OA\Response(response: 500, description: "Erreur lors de la suppression")]
    public function deleteAll(Request $request, OperateurRepository $villeRepository): Response
    {
        try {
            $data = json_decode($request->getContent(), true);

            $count = 0;
            foreach ($data['ids'] as $id) {
                $operateur = $villeRepository->findInEnvironment($id);

                if ($operateur != null) {
                    $villeRepository->remove($operateur);
                    $count++;
                }
            }
            $this->setMessage("Operation effectuées avec succès");
            $response = $this->json(['message' => 'Operation effectuées avec succès', 'deletedCount' => $count]);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            $this->setMessage("Erreur lors de la suppression des opérateurs");
            $response = $this->response([]);
        }
        return $response;
    }
}