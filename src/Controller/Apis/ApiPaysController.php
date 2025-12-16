<?php

namespace App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use App\DTO\PaysDTO;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Entity\Pays;
use App\Repository\PaysRepository;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Attribute\Model as Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

/**
 * Contrôleur pour la gestion des pays
 * Permet de gérer les pays avec leurs indicatifs téléphoniques et leurs opérateurs associés
 */
#[Route('/api/pays')]
#[OA\Tag(name: 'pays', description: 'Gestion des pays avec indicatifs téléphoniques et opérateurs mobiles')]
class ApiPaysController extends ApiInterface
{
    /**
     * Liste tous les pays du système
     */
    #[Route('/', methods: ['GET'])]
    #[OA\Get(
        path: "/api/pays/",
        summary: "Lister tous les pays",
        description: "Retourne la liste paginée de tous les pays disponibles dans le système, incluant leurs codes ISO, indicatifs téléphoniques et opérateurs associés.",
        tags: ['pays']
    )]
    #[OA\Response(
        response: 200,
        description: "Liste des pays récupérée avec succès",
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(
                type: "object",
                properties: [
                    new OA\Property(property: "id", type: "integer", example: 1, description: "Identifiant unique du pays"),
                    new OA\Property(property: "libelle", type: "string", example: "Côte d'Ivoire", description: "Nom complet du pays"),
                    new OA\Property(property: "code", type: "string", example: "CI", description: "Code ISO du pays (2 lettres)"),
                    new OA\Property(property: "indicatif", type: "string", example: "+225", description: "Indicatif téléphonique international"),
                    new OA\Property(property: "actif", type: "boolean", example: true, description: "Statut actif/inactif du pays"),
                    new OA\Property(
                        property: "operateurs",
                        type: "array",
                        description: "Liste des opérateurs téléphoniques du pays",
                        items: new OA\Items(
                            type: "object",
                            properties: [
                                new OA\Property(property: "id", type: "integer", example: 1),
                                new OA\Property(property: "libelle", type: "string", example: "Orange CI"),
                                new OA\Property(property: "code", type: "string", example: "ORANGE_CI")
                            ]
                        )
                    ),
                    new OA\Property(property: "createdAt", type: "string", format: "date-time", example: "2025-01-15T10:30:00+00:00")
                ]
            )
        )
    )]
    #[OA\Response(response: 500, description: "Erreur serveur lors de la récupération")]
    public function index(PaysRepository $paysRepository): Response {
        try {
            // Utiliser le trait pour obtenir automatiquement les données du bon environnement
         
            
            $pays = $this->paginationService->paginate($paysRepository->findAllInEnvironment());
            $response = $this->responseData($pays, 'group1', ['Content-Type' => 'application/json']);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            $this->setMessage("Erreur lors de la récupération des pays");
            $response = $this->response([]);
        }

        return $response;
    }

    /**
     * Liste uniquement les pays actifs
     */
    #[Route('/actif', methods: ['GET'])]
    #[OA\Get(
        path: "/api/pays/actif",
        summary: "Lister les pays actifs",
        description: "Retourne la liste paginée uniquement des pays actifs dans le système. Utile pour afficher les pays disponibles dans les formulaires de sélection.",
        tags: ['pays']
    )]
    #[OA\Response(
        response: 200,
        description: "Liste des pays actifs récupérée avec succès",
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(
                type: "object",
                properties: [
                    new OA\Property(property: "id", type: "integer", example: 1),
                    new OA\Property(property: "libelle", type: "string", example: "Côte d'Ivoire"),
                    new OA\Property(property: "code", type: "string", example: "CI"),
                    new OA\Property(property: "indicatif", type: "string", example: "+225"),
                    new OA\Property(property: "actif", type: "boolean", example: true, description: "Toujours true dans cet endpoint"),
                    new OA\Property(property: "operateurs", type: "array", description: "Opérateurs téléphoniques disponibles", items: new OA\Items(type: "object"))
                ]
            )
        )
    )]
    #[OA\Response(response: 500, description: "Erreur lors de la récupération")]
    public function indexActif(PaysRepository $paysRepository): Response
    {
        try {
            // Utiliser le trait pour obtenir automatiquement les pays actifs du bon environnement
            //$paysData = $paysRepository->findByInEnvironment(['actif' => true], ['libelle' => 'ASC']);
            $paysData = $paysRepository->findActivePays();
            
            $pays = $this->paginationService->paginate($paysData);
            $response = $this->responseData($pays, 'group1', ['Content-Type' => 'application/json']);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            $this->setMessage("Erreur lors de la récupération des pays actifs");
            $response = $this->response([]);
        }

        return $response;
    }

    /**
     * Récupère les détails d'un pays spécifique
     */
    #[Route('/get/one/{id}', methods: ['GET'])]
    #[OA\Get(
        path: "/api/pays/get/one/{id}",
        summary: "Détails d'un pays",
        description: "Affiche les informations détaillées d'un pays spécifique, incluant son code ISO, son indicatif téléphonique et la liste de tous ses opérateurs mobiles.",
        tags: ['pays']
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
        description: "Pays trouvé avec succès",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "id", type: "integer", example: 1),
                new OA\Property(property: "libelle", type: "string", example: "Côte d'Ivoire"),
                new OA\Property(property: "code", type: "string", example: "CI", description: "Code ISO 3166-1 alpha-2"),
                new OA\Property(property: "indicatif", type: "string", example: "+225", description: "Indicatif téléphonique avec le signe +"),
                new OA\Property(property: "actif", type: "boolean", example: true),
                new OA\Property(
                    property: "operateurs",
                    type: "array",
                    description: "Liste complète des opérateurs téléphoniques du pays",
                    items: new OA\Items(
                        type: "object",
                        properties: [
                            new OA\Property(property: "id", type: "integer", example: 1),
                            new OA\Property(property: "libelle", type: "string", example: "Orange Côte d'Ivoire"),
                            new OA\Property(property: "code", type: "string", example: "ORANGE_CI"),
                            new OA\Property(property: "photo", type: "string", nullable: true, example: "/uploads/operateurs/orange_logo.png"),
                            new OA\Property(property: "actif", type: "boolean", example: true)
                        ]
                    )
                ),
                new OA\Property(property: "createdAt", type: "string", format: "date-time"),
                new OA\Property(property: "updatedAt", type: "string", format: "date-time")
            ]
        )
    )]
    #[OA\Response(response: 404, description: "Pays non trouvé")]
    public function getOne(int $id,PaysRepository $paysRepository): Response
    {
        try {
            // Utiliser le trait pour trouver le pays dans le bon environnement
            $pays = $paysRepository->findInEnvironment($id);
            
            if ($pays) {
                $response = $this->response($pays);
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
     * Crée un nouveau pays
     */
    #[Route('/create', methods: ['POST'])]
    #[OA\Post(
        path: "/api/pays/create",
        summary: "Créer un nouveau pays",
        description: "Permet de créer un nouveau pays avec son nom, son code ISO et son indicatif téléphonique. Le pays sera créé avec le statut actif par défaut.",
        tags: ['pays']
    )]
    #[OA\RequestBody(
        required: true,
        description: "Données du pays à créer",
        content: new OA\JsonContent(
            type: "object",
            required: ["libelle", "code", "indicatif"],
            properties: [
                new OA\Property(
                    property: "libelle",
                    type: "string",
                    example: "Sénégal",
                    description: "Nom complet du pays (obligatoire)"
                ),
                new OA\Property(
                    property: "code",
                    type: "string",
                    example: "SN",
                    description: "Code ISO 3166-1 alpha-2 du pays (obligatoire, 2 lettres majuscules)"
                ),
                new OA\Property(
                    property: "indicatif",
                    type: "string",
                    example: "+221",
                    description: "Indicatif téléphonique international avec le signe + (obligatoire)"
                )
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: "Pays créé avec succès",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "id", type: "integer", example: 15),
                new OA\Property(property: "libelle", type: "string", example: "Sénégal"),
                new OA\Property(property: "code", type: "string", example: "SN"),
                new OA\Property(property: "indicatif", type: "string", example: "+221"),
                new OA\Property(property: "actif", type: "boolean", example: true),
                new OA\Property(property: "createdAt", type: "string", format: "date-time")
            ]
        )
    )]
    #[OA\Response(response: 400, description: "Données invalides ou code pays déjà existant")]
    #[OA\Response(response: 401, description: "Non authentifié")]
    public function create(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        $pays = new Pays();
        $pays->setLibelle($data['libelle']);
        $pays->setCode($data['code']);
        $pays->setActif(true);
        $pays->setIsActive(true);
        $pays->setIndicatif($data['indicatif']);
        $pays->setCreatedBy($this->getUser());
        $pays->setUpdatedBy($this->getUser());
        $pays->setCreatedAtValue(new \DateTime());
        $pays->setUpdatedAt(new \DateTime());

        $errorResponse = $this->errorResponse($pays);
        if ($errorResponse !== null) {
            return $errorResponse;
        } else {
            // Utiliser le trait pour sauvegarder dans le bon environnement
            $this->save($pays);
        }

        return $this->responseData($pays, 'group1', ['Content-Type' => 'application/json']);
    }

    /**
     * Met à jour un pays existant
     */
    #[Route('/update/{id}', methods: ['PUT', 'POST'])]
    #[OA\Put(
        path: "/api/pays/update/{id}",
        summary: "Mettre à jour un pays",
        description: "Permet de mettre à jour les informations d'un pays, incluant son nom, son code ISO, son indicatif téléphonique et son statut actif/inactif.",
        tags: ['pays']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: "Identifiant unique du pays à mettre à jour",
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\RequestBody(
        required: true,
        description: "Nouvelles données du pays",
        content: new OA\JsonContent(
            type: "object",
            required: ["libelle", "code", "indicatif", "actif"],
            properties: [
                new OA\Property(
                    property: "libelle",
                    type: "string",
                    example: "République de Côte d'Ivoire",
                    description: "Nouveau nom complet du pays (obligatoire)"
                ),
                new OA\Property(
                    property: "code",
                    type: "string",
                    example: "CI",
                    description: "Code ISO du pays (obligatoire)"
                ),
                new OA\Property(
                    property: "indicatif",
                    type: "string",
                    example: "+225",
                    description: "Indicatif téléphonique (obligatoire)"
                ),
                new OA\Property(
                    property: "actif",
                    type: "boolean",
                    example: true,
                    description: "Statut actif (true) ou inactif (false)"
                )
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: "Pays mis à jour avec succès",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "id", type: "integer", example: 1),
                new OA\Property(property: "libelle", type: "string", example: "République de Côte d'Ivoire"),
                new OA\Property(property: "code", type: "string", example: "CI"),
                new OA\Property(property: "indicatif", type: "string", example: "+225"),
                new OA\Property(property: "actif", type: "boolean", example: true),
                new OA\Property(property: "updatedAt", type: "string", format: "date-time")
            ]
        )
    )]
    #[OA\Response(response: 400, description: "Données invalides")]
    #[OA\Response(response: 401, description: "Non authentifié")]
    #[OA\Response(response: 404, description: "Pays non trouvé")]
    public function update(Request $request, int $id,PaysRepository $paysRepository): Response
    {
        try {
            $data = json_decode($request->getContent(), true);

            // Utiliser le trait pour trouver le pays dans le bon environnement
           $pays = $paysRepository->findInEnvironment($id);

            if ($pays != null) {
                if (isset($data['libelle'])) {
                    $pays->setLibelle($data['libelle']);
                }
                if (isset($data['code'])) {
                    $pays->setCode($data['code']);
                }
                if (isset($data['actif']) !== null) {
                    $pays->setActif($data['actif']);
                }
                if (isset($data['indicatif'])) {
                    $pays->setIndicatif($data['indicatif']);
                }

                $pays->setUpdatedBy($this->getUser());
                $pays->setUpdatedAt(new \DateTime());

                $errorResponse = $this->errorResponse($pays);
                if ($errorResponse !== null) {
                    return $errorResponse;
                } else {
                    // Utiliser le trait pour sauvegarder dans le bon environnement
                    $this->save($pays);
                }

                $response = $this->responseData($pays, 'group1', ['Content-Type' => 'application/json']);
            } else {
                $this->setMessage("Cette ressource est inexistante");
                $this->setStatusCode(404);
                $response = $this->response([]);
            }
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            $this->setMessage("Erreur lors de la mise à jour du pays");
            $response = $this->response([]);
        }
        return $response;
    }

    /**
     * Supprime un pays
     */
    #[Route('/delete/{id}', methods: ['DELETE'])]
    #[OA\Delete(
        path: "/api/pays/delete/{id}",
        summary: "Supprimer un pays",
        description: "Permet de supprimer définitivement un pays par son identifiant. Attention : cette action supprime également tous les opérateurs téléphoniques associés à ce pays.",
        tags: ['pays']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: "Identifiant unique du pays à supprimer",
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: "Pays supprimé avec succès",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "message", type: "string", example: "Operation effectuées avec succès"),
                new OA\Property(property: "deleted", type: "boolean", example: true)
            ]
        )
    )]
    #[OA\Response(response: 401, description: "Non authentifié")]
    #[OA\Response(response: 404, description: "Pays non trouvé")]
    #[OA\Response(response: 500, description: "Erreur lors de la suppression (peut-être des dépendances)")]
    public function delete(Request $request, int $id,PaysRepository $paysRepository): Response
    {
        try {
            // Utiliser le trait pour trouver le pays dans le bon environnement
          $pays = $paysRepository->findInEnvironment($id);
            if ($pays != null) {
                // Utiliser le trait pour supprimer dans le bon environnement
                $this->remove($pays);
                $this->setMessage("Operation effectuées avec succès");
                $response = $this->response($pays);
            } else {
                $this->setMessage("Cette ressource est inexistante");
                $this->setStatusCode(404);
                $response = $this->response([]);
            }
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            $this->setMessage("Erreur lors de la suppression du pays");
            $response = $this->response([]);
        }
        return $response;
    }

    /**
     * Supprime plusieurs pays en masse
     */
    #[Route('/delete/all/items', methods: ['DELETE'])]
    #[OA\Delete(
        path: "/api/pays/delete/all/items",
        summary: "Supprimer plusieurs pays",
        description: "Permet de supprimer plusieurs pays en une seule opération en fournissant un tableau d'identifiants. Attention : tous les opérateurs téléphoniques associés seront également supprimés.",
        tags: ['pays']
    )]
    #[OA\RequestBody(
        required: true,
        description: "Tableau des identifiants des pays à supprimer",
        content: new OA\JsonContent(
            type: "object",
            required: ["ids"],
            properties: [
                new OA\Property(
                    property: 'ids',
                    type: 'array',
                    description: "Liste des identifiants des pays à supprimer",
                    items: new OA\Items(type: 'integer', example: 1),
                    example: [1, 2, 3, 5, 8]
                )
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: "Pays supprimés avec succès",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "message", type: "string", example: "Operation effectuées avec succès"),
                new OA\Property(property: "deletedCount", type: "integer", example: 5, description: "Nombre de pays supprimés")
            ]
        )
    )]
    #[OA\Response(response: 400, description: "Données invalides")]
    #[OA\Response(response: 401, description: "Non authentifié")]
    #[OA\Response(response: 500, description: "Erreur lors de la suppression")]
    public function deleteAll(Request $request,PaysRepository $paysRepository): Response
    {
        try {
            $data = json_decode($request->getContent(), true);

            $count = 0;
            foreach ($data['ids'] as $id) {
                // Utiliser le trait pour trouver le pays dans le bon environnement
                 $pays = $paysRepository->findInEnvironment($id);

                if ($pays != null) {
                    // Utiliser le trait pour supprimer dans le bon environnement
                    $this->remove($pays, false); // Ne pas flush à chaque suppression
                    $count++;
                }
            }
            
            // Flush une seule fois à la fin
            $this->getEntityManager()->flush();
            
            $this->setMessage("Operation effectuées avec succès");
            $response = $this->json(['message' => 'Operation effectuées avec succès', 'deletedCount' => $count]);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            $this->setMessage("Erreur lors de la suppression des pays");
            $response = $this->response([]);
        }
        return $response;
    }

    /**
     * Exemple d'utilisation du repository adapté - Recherche par code pays
     */
    #[Route('/search/code/{code}', methods: ['GET'])]
    #[OA\Get(
        path: "/api/pays/search/code/{code}",
        summary: "Rechercher un pays par son code",
        description: "Utilise le repository adapté pour rechercher un pays par son code dans l'environnement actuel",
        tags: ['pays']
    )]
    #[OA\Parameter(
        name: 'code',
        in: 'path',
        required: true,
        description: "Code du pays (ex: CI, FR, US)",
        schema: new OA\Schema(type: 'string', example: 'CI')
    )]
    #[OA\Response(
        response: 200,
        description: "Pays trouvé avec succès",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "id", type: "integer", example: 1),
                new OA\Property(property: "libelle", type: "string", example: "Côte d'Ivoire"),
                new OA\Property(property: "code", type: "string", example: "CI"),
                new OA\Property(property: "indicatif", type: "string", example: "+225")
            ]
        )
    )]
    #[OA\Response(response: 404, description: "Pays non trouvé")]
    public function searchByCode(string $code, PaysRepository $paysRepository): Response
    {
        try {
            // Utilisation du repository adapté qui utilise automatiquement le bon environnement
            $pays = $paysRepository->findOneBy(["code"=>$code]);
            
            if ($pays) {
                $response = $this->responseData($pays, 'group1', ['Content-Type' => 'application/json']);
            } else {
                $this->setMessage('Pays non trouvé avec ce code');
                $this->setStatusCode(404);
                $response = $this->response(null);
            }
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            $this->setMessage("Erreur lors de la recherche du pays");
            $response = $this->response([]);
        }

        return $response;
    }

    /**
     * Exemple d'utilisation mixte - Liste des pays actifs via repository
     */
    #[Route('/active/repository', methods: ['GET'])]
    #[OA\Get(
        path: "/api/pays/active/repository",
        summary: "Lister les pays actifs via repository",
        description: "Exemple d'utilisation du repository adapté pour récupérer les pays actifs",
        tags: ['pays']
    )]
    #[OA\Response(
        response: 200,
        description: "Liste des pays actifs récupérée avec succès"
    )]
    public function getActivePaysViaRepository(PaysRepository $paysRepository): Response
    {
        try {
            // Utilisation du repository adapté
            $paysActifs = $paysRepository->findActivePays();
            
            $pays = $this->paginationService->paginate($paysActifs);
            $response = $this->responseData($pays, 'group1', ['Content-Type' => 'application/json']);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            $this->setMessage("Erreur lors de la récupération des pays actifs");
            $response = $this->response([]);
        }

        return $response;
    }
}
