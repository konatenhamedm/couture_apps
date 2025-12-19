<?php

namespace App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use App\DTO\ClientDTO;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Entity\Client;
use App\Repository\BoutiqueRepository;
use App\Repository\ClientRepository;
use App\Repository\SurccursaleRepository;
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
 * Contrôleur pour la gestion des clients
 * Permet de créer, lire, mettre à jour et supprimer des clients avec gestion de photos
 */
#[Route('/api/client')]
#[OA\Tag(name: 'client', description: 'Gestion des clients (boutiques et succursales)')]
class ApiClientController extends ApiInterface
{
    /**
     * Liste tous les clients du système
     */
    #[Route('/', methods: ['GET'])]
    #[OA\Get(
        path: "/api/client/",
        summary: "Lister tous les clients",
        description: "Retourne la liste paginée de tous les clients disponibles dans le système",
        tags: ['client']
    )]
    #[OA\Response(
        response: 200,
        description: "Liste des clients récupérée avec succès",
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(
                type: "object",
                properties: [
                    new OA\Property(property: "id", type: "integer", example: 1, description: "Identifiant unique du client"),
                    new OA\Property(property: "nom", type: "string", example: "Kouassi", description: "Nom du client"),
                    new OA\Property(property: "prenom", type: "string", example: "Yao", description: "Prénom du client"),
                    new OA\Property(property: "numero", type: "string", example: "+225 0123456789", description: "Numéro de téléphone"),
                    new OA\Property(property: "photo", type: "string", nullable: true, example: "/uploads/clients/photo_001.jpg", description: "Chemin de la photo"),
                    new OA\Property(property: "boutique", type: "object", nullable: true, description: "Boutique associée"),
                    new OA\Property(property: "succursale", type: "object", nullable: true, description: "Succursale associée"),
                    new OA\Property(property: "entreprise", type: "object", description: "Entreprise associée"),
                    new OA\Property(property: "createdAt", type: "string", format: "date-time", example: "2025-01-15T10:30:00+00:00")
                ]
            )
        )
    )]
    #[OA\Response(response: 500, description: "Erreur serveur lors de la récupération")]
    public function index(ClientRepository $clientRepository): Response
    {
        try {
            $clients = $this->paginationService->paginate($clientRepository->findAllInEnvironment());
            $response = $this->responseData($clients, 'group1', ['Content-Type' => 'application/json']);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            $this->setMessage("Erreur lors de la récupération des clients");
            $response = $this->response([]);
        }

        return $response;
    }

    /**
     * Liste les clients selon les droits de l'utilisateur (entreprise, boutique ou succursale)
     */
    #[Route('/entreprise', methods: ['GET'])]
    #[OA\Get(
        path: "/api/client/entreprise",
        summary: "Lister les clients selon les droits utilisateur",
        description: "Retourne la liste des clients filtrée selon le type d'utilisateur : Super-admin voit tous les clients de l'entreprise, Admin boutique voit les clients de sa boutique, autres voient les clients de leur succursale. Nécessite un abonnement actif.",
        tags: ['client']
    )]
    #[OA\Response(
        response: 200,
        description: "Liste des clients récupérée avec succès",
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(
                type: "object",
                properties: [
                    new OA\Property(property: "id", type: "integer", example: 1),
                    new OA\Property(property: "nom", type: "string", example: "Kouassi"),
                    new OA\Property(property: "prenom", type: "string", example: "Yao"),
                    new OA\Property(property: "numero", type: "string", example: "+225 0123456789"),
                    new OA\Property(property: "photo", type: "string", nullable: true, example: "/uploads/clients/photo_001.jpg"),
                    new OA\Property(property: "boutique", type: "object", nullable: true),
                    new OA\Property(property: "succursale", type: "object", nullable: true),
                    new OA\Property(property: "entreprise", type: "object")
                ]
            )
        )
    )]
    #[OA\Response(response: 401, description: "Non authentifié")]
    #[OA\Response(response: 403, description: "Abonnement requis pour cette fonctionnalité")]
    #[OA\Response(response: 500, description: "Erreur lors de la récupération")]
    public function indexAll(ClientRepository $clientRepository, TypeUserRepository $typeUserRepository): Response
    {

       // dd($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()),$this->getUser()->getEntreprise());
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        try {
            if ($this->getUser()->getType() == $typeUserRepository->findOneByInEnvironment(['code' => 'SADM'])) {
                $clients = $this->paginationService->paginate($clientRepository->findByInEnvironment(
                    ['entreprise' => $this->getUser()->getEntreprise()],
                    ['id' => 'ASC']
                ));
            } elseif ($this->getUser()->getType() == $typeUserRepository->findOneByInEnvironment(['code' => 'ADB'])) {
                $clients = $this->paginationService->paginate($clientRepository->findByInEnvironment(
                    ['boutique' => $this->getUser()->getBoutique()],
                    ['id' => 'ASC']
                ));
            } else {
                $clients = $this->paginationService->paginate($clientRepository->findByInEnvironment(
                    ['surccursale' => $this->getUser()->getSurccursale()],
                    ['id' => 'ASC']
                ));
            }

            $response = $this->responseData($clients, 'group1', ['Content-Type' => 'application/json']);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            $this->setMessage("Erreur lors de la récupération des clients");
            $response = $this->response([]);
        }

        return $response;
    }

    /**
     * Récupère les détails d'un client spécifique
     */
    #[Route('/get/one/{id}', methods: ['GET'])]
    #[OA\Get(
        path: "/api/client/get/one/{id}",
        summary: "Détails d'un client",
        description: "Affiche les informations détaillées d'un client spécifique par son identifiant. Nécessite un abonnement actif.",
        tags: ['client']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: "Identifiant unique du client",
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: "Client trouvé avec succès",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "id", type: "integer", example: 1),
                new OA\Property(property: "nom", type: "string", example: "Kouassi"),
                new OA\Property(property: "prenom", type: "string", example: "Yao"),
                new OA\Property(property: "numero", type: "string", example: "+225 0123456789"),
                new OA\Property(property: "photo", type: "string", nullable: true, example: "/uploads/clients/photo_001.jpg"),
                new OA\Property(property: "boutique", type: "object", nullable: true, description: "Boutique associée"),
                new OA\Property(property: "succursale", type: "object", nullable: true, description: "Succursale associée"),
                new OA\Property(property: "entreprise", type: "object", description: "Entreprise"),
                new OA\Property(property: "createdAt", type: "string", format: "date-time"),
                new OA\Property(property: "updatedAt", type: "string", format: "date-time")
            ]
        )
    )]
    #[OA\Response(response: 401, description: "Non authentifié")]
    #[OA\Response(response: 403, description: "Abonnement requis pour cette fonctionnalité")]
    #[OA\Response(response: 404, description: "Client non trouvé")]
    public function getOne(int $id, ClientRepository $clientRepository): Response
    {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        try {
            $client = $clientRepository->findInEnvironment($id);
            if ($client) {
                $response = $this->response($client);
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
     * Crée un nouveau client pour une succursale avec photo optionnelle
     */
    #[Route('/create', methods: ['POST'])]
    #[OA\Post(
        path: "/api/client/create",
        summary: "Créer un client pour une succursale",
        description: "Permet de créer un nouveau client associé à une succursale avec possibilité d'uploader une photo. Nécessite un abonnement actif.",
        tags: ['client']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\MediaType(
            mediaType: "multipart/form-data",
            schema: new OA\Schema(
                type: "object",
                required: ["nom", "prenoms", "numero"],
                properties: [
                    new OA\Property(
                        property: "nom",
                        type: "string",
                        example: "Kouassi",
                        description: "Nom du client (obligatoire)"
                    ),
                    new OA\Property(
                        property: "prenoms",
                        type: "string",
                        example: "Yao Jean",
                        description: "Prénom(s) du client (obligatoire)"
                    ),
                    new OA\Property(
                        property: "numero",
                        type: "string",
                        example: "+225 0123456789",
                        description: "Numéro de téléphone du client (obligatoire)"
                    ),
                    new OA\Property(
                        property: "boutique",
                        type: "integer",
                        example: 1,
                        description: "ID de la boutique à laquelle associer le client (obligatoire)"
                    ),
                    new OA\Property(
                        property: "succursale",
                        type: "integer",
                        example: 1,
                        description: "ID de la succursale à laquelle associer le client (obligatoire)"
                    ),
                    new OA\Property(
                        property: "photo",
                        type: "string",
                        format: "binary",
                        description: "Photo du client (optionnel, formats acceptés: JPG, PNG)"
                    )
                ]
            )
        )
    )]
    #[OA\Response(
        response: 201,
        description: "Client créé avec succès",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "id", type: "integer", example: 5),
                new OA\Property(property: "nom", type: "string", example: "Kouassi"),
                new OA\Property(property: "prenom", type: "string", example: "Yao Jean"),
                new OA\Property(property: "numero", type: "string", example: "+225 0123456789"),
                new OA\Property(property: "photo", type: "string", nullable: true, example: "/uploads/clients/document_01_abc123.jpg"),
                new OA\Property(property: "succursale", type: "object"),
                new OA\Property(property: "boutique", type: "object"),
               
            ]
        )
    )]
    #[OA\Response(response: 400, description: "Données invalides ou fichier non accepté")]
    #[OA\Response(response: 401, description: "Non authentifié")]
    #[OA\Response(response: 403, description: "Abonnement requis pour cette fonctionnalité")]
    public function create(Request $request, ClientRepository $clientRepository, SurccursaleRepository $surccursaleRepository, BoutiqueRepository $boutiqueRepository): Response
    {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        $names = 'document_' . '01';
        $filePrefix = str_slug($names);
        $filePath = $this->getUploadDir(self::UPLOAD_PATH, true);

        $uploadedFile = $request->files->get('photo');

        $client = new Client();
        $client->setEntreprise($this->getUser()->getEntreprise());
        $client->setPrenom($request->get('prenoms'));
        $client->setIsActive(true);
        $client->setNom($request->get('nom'));
        $client->setNumero($request->get('numero'));
        
        if($request->get('succursale') && $request->get('succursale') != null){
        $client->setSurccursale($surccursaleRepository->findInEnvironment($request->get('succursale')));
        }
        $client->setBoutique($boutiqueRepository->findInEnvironment($request->get('boutique')));
        if($request->get('boutique') && $request->get('boutique') != null){
            $client->setBoutique($boutiqueRepository->findInEnvironment($request->get('boutique')));
        }

        if ($uploadedFile) {
            if ($fichier = $this->utils->sauvegardeFichier($filePath, $filePrefix, $uploadedFile, self::UPLOAD_PATH)) {
                $client->setPhoto($fichier);
            }
        }

        $client->setCreatedBy($this->getUser());
        $client->setUpdatedBy($this->getUser());
        $client->setCreatedAtValue(new \DateTime());
        $client->setUpdatedAt(new \DateTime());

        $errorResponse = $this->errorResponse($client);
        if ($errorResponse !== null) {
            return $errorResponse;
        } else {
            $clientRepository->add($client, true);
        }

        return $this->responseData($client, 'group1', ['Content-Type' => 'application/json']);
    }

    /**
     * Crée un nouveau client pour une boutique avec photo optionnelle
     */
    #[Route('/create/boutique', methods: ['POST'])]
    #[OA\Post(
        path: "/api/client/create/boutique",
        summary: "Créer un client pour une boutique",
        description: "Permet de créer un nouveau client associé à une boutique avec possibilité d'uploader une photo. Nécessite un abonnement actif.",
        tags: ['client']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\MediaType(
            mediaType: "multipart/form-data",
            schema: new OA\Schema(
                type: "object",
                required: ["nom", "prenoms", "numero", "boutique"],
                properties: [
                    new OA\Property(
                        property: "nom",
                        type: "string",
                        example: "Kouassi",
                        description: "Nom du client (obligatoire)"
                    ),
                    new OA\Property(
                        property: "prenoms",
                        type: "string",
                        example: "Yao Jean",
                        description: "Prénom(s) du client (obligatoire)"
                    ),
                    new OA\Property(
                        property: "numero",
                        type: "string",
                        example: "+225 0123456789",
                        description: "Numéro de téléphone du client (obligatoire)"
                    ),
                    new OA\Property(
                        property: "boutique",
                        type: "integer",
                        example: 1,
                        description: "ID de la boutique à laquelle associer le client (obligatoire)"
                    ),
                    new OA\Property(
                        property: "succursale",
                        type: "integer",
                        example: 1,
                        description: "ID de la succursale à laquelle associer le client (obligatoire)"
                    ),
                    new OA\Property(
                        property: "photo",
                        type: "string",
                        format: "binary",
                        description: "Photo du client (optionnel, formats acceptés: JPG, PNG)"
                    )
                ]
            )
        )
    )]
    #[OA\Response(
        response: 201,
        description: "Client créé avec succès",
        content: new OA\JsonContent(
            type: "object",
            properties: [

                new OA\Property(property: "nom", type: "string", example: "Kouassi"),
                new OA\Property(property: "prenom", type: "string", example: "Yao Jean"),
                new OA\Property(property: "numero", type: "string", example: "+225 0123456789"),
                new OA\Property(property: "photo", type: "string", nullable: true, example: "/uploads/clients/document_01_xyz789.jpg"),
                new OA\Property(property: "boutique", type: "object"),
                new OA\Property(property: "succursale", type: "object"),
            ]
        )
    )]
    #[OA\Response(response: 400, description: "Données invalides ou fichier non accepté")]
    #[OA\Response(response: 401, description: "Non authentifié")]
    #[OA\Response(response: 403, description: "Abonnement requis pour cette fonctionnalité")]
    public function createBoutique(Request $request, ClientRepository $clientRepository, BoutiqueRepository $boutiqueRepository, SurccursaleRepository $surccursaleRepository): Response
    {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        $names = 'document_' . '01';
        $filePrefix = str_slug($names);
        $filePath = $this->getUploadDir(self::UPLOAD_PATH, true);

        $uploadedFile = $request->files->get('photo');

        $client = new Client();
        $client->setEntreprise($this->getUser()->getEntreprise());
        $client->setNom($request->get('nom'));
        $client->setIsActive(true);
        $client->setPrenom($request->get('prenoms'));
        $client->setNumero($request->get('numero'));

        if ($request->get('boutique')    && $request->get('boutique') != null) {
            $client->setBoutique($boutiqueRepository->findInEnvironment($request->get('boutique')));
        }
        if ($request->get('succursale')    && $request->get('succursale') != null) {
            $client->setSurccursale($surccursaleRepository->findInEnvironment($request->get('succursale')));
        }

        if ($uploadedFile) {
            if ($fichier = $this->utils->sauvegardeFichier($filePath, $filePrefix, $uploadedFile, self::UPLOAD_PATH)) {
                $client->setPhoto($fichier);
            }
        }

        $client->setCreatedBy($this->getUser());
        $client->setUpdatedBy($this->getUser());
        $client->setCreatedAtValue(new \DateTime());
        $client->setUpdatedAt(new \DateTime());

        $errorResponse = $this->errorResponse($client);
        if ($errorResponse !== null) {
            return $errorResponse;
        } else {
            $clientRepository->add($client, true);
        }

        return $this->responseData($client, 'group1', ['Content-Type' => 'application/json']);
    }

    /**
     * Met à jour un client existant avec possibilité de changer la photo
     */
    #[Route('/update/{id}', methods: ['PUT', 'POST'])]
    #[OA\Put(
        path: "/api/client/update/{id}",
        summary: "Mettre à jour un client",
        description: "Permet de mettre à jour les informations d'un client existant, y compris sa photo. Nécessite un abonnement actif.",
        tags: ['client']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: "Identifiant unique du client à mettre à jour",
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
                        property: "nom",
                        type: "string",
                        example: "Kouassi",
                        description: "Nouveau nom du client"
                    ),
                    new OA\Property(
                        property: "prenoms",
                        type: "string",
                        example: "Yao Pierre",
                        description: "Nouveau(x) prénom(s)"
                    ),
                    new OA\Property(
                        property: "numero",
                        type: "string",
                        example: "+225 0198765432",
                        description: "Nouveau numéro de téléphone"
                    ),
                    new OA\Property(
                        property: "boutique",
                        type: "integer",
                        example: 1,
                        description: "ID de la boutique à laquelle associer le client (obligatoire)"
                    ),
                    new OA\Property(
                        property: "succursale",
                        type: "integer",
                        example: 2,
                        description: "Nouvel ID de la succursale"
                    ),
                    new OA\Property(
                        property: "photo",
                        type: "string",
                        format: "binary",
                        description: "Nouvelle photo du client (optionnel)"
                    )
                ]
            )
        )
    )]
    #[OA\Response(
        response: 200,
        description: "Client mis à jour avec succès",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "id", type: "integer", example: 1),
                new OA\Property(property: "nom", type: "string", example: "Kouassi"),
                new OA\Property(property: "prenom", type: "string", example: "Yao Pierre"),
                new OA\Property(property: "numero", type: "string", example: "+225 0198765432"),
                new OA\Property(property: "photo", type: "string", nullable: true),
                new OA\Property(property: "updatedAt", type: "string", format: "date-time")
            ]
        )
    )]
    #[OA\Response(response: 400, description: "Données invalides")]
    #[OA\Response(response: 401, description: "Non authentifié")]
    #[OA\Response(response: 403, description: "Abonnement requis pour cette fonctionnalité")]
    #[OA\Response(response: 404, description: "Client non trouvé")]
    public function update(Request $request, int $id, ClientRepository $clientRepository, BoutiqueRepository $boutiqueRepository, SurccursaleRepository $surccursaleRepository): Response
    {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        try {
            $client = $clientRepository->findInEnvironment($id);
            $names = 'document_' . '01';
            $filePrefix = str_slug($names);
            $filePath = $this->getUploadDir(self::UPLOAD_PATH, true);

            if ($client != null) {
                if ($request->get('nom')) {
                    $client->setNom($request->get('nom'));
                }
                if ($request->get('prenoms')) {
                    $client->setPrenom($request->get('prenoms'));
                }
                if ($request->get('numero')) {
                    $client->setNumero($request->get('numero'));
                }
                if ($request->get('surccursale')) {
                    $client->setSurccursale($surccursaleRepository->findInEnvironment($request->get('surccursale')));
                }
                if ($request->get('boutique')) {
                    $client->setBoutique($boutiqueRepository->findInEnvironment($request->get('boutique')));
                }

                $uploadedFile = $request->files->get('photo');
                if ($uploadedFile) {
                    if ($fichier = $this->utils->sauvegardeFichier($filePath, $filePrefix, $uploadedFile, self::UPLOAD_PATH)) {
                        $client->setPhoto($fichier);
                    }
                }

                $client->setUpdatedBy($this->getUser());
                $client->setUpdatedAt(new \DateTime());

                $errorResponse = $this->errorResponse($client);
                if ($errorResponse !== null) {
                    return $errorResponse;
                } else {
                    $clientRepository->add($client, true);
                }

                $response = $this->responseData($client, 'group1', ['Content-Type' => 'application/json']);
            } else {
                $this->setMessage("Cette ressource est inexistante");
                $this->setStatusCode(404);
                $response = $this->response([]);
            }
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            $this->setMessage("Erreur lors de la mise à jour du client");
            $response = $this->response([]);
        }
        return $response;
    }

    /**
     * Supprime un client
     */
    #[Route('/delete/{id}', methods: ['DELETE'])]
    #[OA\Delete(
        path: "/api/client/delete/{id}",
        summary: "Supprimer un client",
        description: "Permet de supprimer définitivement un client par son identifiant. Nécessite un abonnement actif.",
        tags: ['client']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: "Identifiant unique du client à supprimer",
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: "Client supprimé avec succès",
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
    #[OA\Response(response: 404, description: "Client non trouvé")]
    #[OA\Response(response: 500, description: "Erreur lors de la suppression")]
    public function delete(Request $request, int $id, ClientRepository $villeRepository): Response
    {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        try {
            $client = $villeRepository->findInEnvironment($id);
            if ($client != null) {
                $villeRepository->remove($client, true);
                $this->setMessage("Operation effectuées avec succès");
                $response = $this->response($client);
            } else {
                $this->setMessage("Cette ressource est inexistante");
                $this->setStatusCode(404);
                $response = $this->response([]);
            }
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            $this->setMessage("Erreur lors de la suppression du client");
            $response = $this->response([]);
        }
        return $response;
    }

    /**
     * Supprime plusieurs clients en masse
     */
    #[Route('/delete/all/items', methods: ['DELETE'])]
    #[OA\Delete(
        path: "/api/client/delete/all/items",
        summary: "Supprimer plusieurs clients",
        description: "Permet de supprimer plusieurs clients en une seule opération en fournissant un tableau d'identifiants. Nécessite un abonnement actif.",
        tags: ['client']
    )]
    #[OA\RequestBody(
        required: true,
        description: "Tableau des identifiants des clients à supprimer",
        content: new OA\JsonContent(
            type: "object",
            required: ["ids"],
            properties: [
                new OA\Property(
                    property: 'ids',
                    type: 'array',
                    description: "Liste des identifiants des clients à supprimer",
                    items: new OA\Items(type: 'integer', example: 1),
                    example: [1, 2, 3, 5, 8]
                )
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: "Clients supprimés avec succès",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "message", type: "string", example: "Operation effectuées avec succès"),
                new OA\Property(property: "deletedCount", type: "integer", example: 5, description: "Nombre de clients supprimés")
            ]
        )
    )]
    #[OA\Response(response: 400, description: "Données invalides")]
    #[OA\Response(response: 401, description: "Non authentifié")]
    #[OA\Response(response: 403, description: "Abonnement requis pour cette fonctionnalité")]
    #[OA\Response(response: 500, description: "Erreur lors de la suppression")]
    public function deleteAll(Request $request, ClientRepository $villeRepository): Response
    {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        try {
            $data = json_decode($request->getContent(), true);

            foreach ($data['ids'] as $id) {
                $client = $villeRepository->findInEnvironment($id);

                if ($client != null) {
                    $villeRepository->remove($client);
                }
            }
            $this->setMessage("Operation effectuées avec succès");
            $response = $this->response([]);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            $this->setMessage("Erreur lors de la suppression des clients");
            $response = $this->response([]);
        }
        return $response;
    }
}