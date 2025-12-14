<?php

namespace App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use App\DTO\BoutiqueDTO;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Entity\Boutique;
use App\Entity\Caisse;
use App\Entity\CaisseBoutique;
use App\Repository\BoutiqueRepository;
use App\Repository\CaisseBoutiqueRepository;
use App\Repository\TypeUserRepository;
use App\Repository\UserRepository;
use App\Service\SubscriptionChecker;
use App\Service\Utils;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Attribute\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

/**
 * Contrôleur pour la gestion des boutiques
 * Permet de créer, lire, mettre à jour et supprimer des boutiques avec leurs caisses associées
 */
#[Route('/api/boutique')]
#[OA\Tag(name: 'boutique', description: 'Gestion des boutiques et points de vente')]
class ApiBoutiqueController extends ApiInterface
{
    /**
     * Liste toutes les boutiques du système
     */
    #[Route('/', methods: ['GET'])]
    #[OA\Get(
        path: "/api/boutique/",
        summary: "Lister toutes les boutiques",
        description: "Retourne la liste paginée de toutes les boutiques disponibles dans le système",
        tags: ['boutique']
    )]
    #[OA\Response(
        response: 200,
        description: "Liste des boutiques récupérée avec succès",
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(
                type: "object",
                properties: [
                    new OA\Property(property: "id", type: "integer", example: 1, description: "Identifiant unique de la boutique"),
                    new OA\Property(property: "libelle", type: "string", example: "Boutique Centre-Ville", description: "Nom de la boutique"),
                    new OA\Property(property: "situation", type: "string", example: "Avenue 12, Abidjan", description: "Adresse/localisation de la boutique"),
                    new OA\Property(property: "contact", type: "string", example: "+225 0123456789", description: "Numéro de contact de la boutique"),
                    new OA\Property(property: "setIsActive", type: "boolean", example: true, description: "Indique si la boutique est active"),
                    new OA\Property(property: "entreprise", type: "object", description: "Entreprise propriétaire"),
                    new OA\Property(property: "createdAt", type: "string", format: "date-time", example: "2025-01-15T10:30:00+00:00")
                ]
            )
        )
    )]
    #[OA\Response(response: 500, description: "Erreur serveur lors de la récupération")]
    public function index(BoutiqueRepository $boutiqueRepository): Response
    {
        try {
            $boutiques = $this->paginationService->paginate($boutiqueRepository->findAll());
            $response = $this->responseData($boutiques, 'group1', ['Content-Type' => 'application/json']);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            $this->setMessage("Erreur lors de la récupération des boutiques");
            $response = $this->response([]);
        }

        return $response;
    }

    /**
     * Liste les boutiques d'une entreprise selon les droits de l'utilisateur
     */
    #[Route('/entreprise', methods: ['GET'])]
    #[OA\Get(
        path: "/api/boutique/entreprise",
        summary: "Lister les boutiques d'une entreprise",
        description: "Retourne la liste des boutiques de l'entreprise de l'utilisateur authentifié. Les super-admins voient toutes les boutiques de l'entreprise, les autres utilisateurs voient uniquement leur boutique assignée.",
        tags: ['boutique']
    )]
    #[OA\Response(
        response: 200,
        description: "Liste des boutiques de l'entreprise récupérée avec succès",
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(
                type: "object",
                properties: [
                    new OA\Property(property: "id", type: "integer", example: 1, description: "Identifiant de la boutique"),
                    new OA\Property(property: "libelle", type: "string", example: "Boutique Centre-Ville", description: "Nom de la boutique"),
                    new OA\Property(property: "situation", type: "string", example: "Avenue 12, Abidjan", description: "Adresse de la boutique"),
                    new OA\Property(property: "contact", type: "string", example: "+225 0123456789", description: "Contact"),
                    new OA\Property(property: "setIsActive", type: "boolean", example: true, description: "Statut d'activité"),
                    new OA\Property(property: "entreprise", type: "object", description: "Informations de l'entreprise"),
                    new OA\Property(property: "caisseBoutique", type: "object", description: "Caisse associée à la boutique")
                ]
            )
        )
    )]
    #[OA\Response(response: 401, description: "Non authentifié")]
    #[OA\Response(response: 403, description: "Abonnement requis pour cette fonctionnalité")]
    #[OA\Response(response: 500, description: "Erreur lors de la récupération")]
    public function indexAll(BoutiqueRepository $boutiqueRepository, TypeUserRepository $typeUserRepository): Response
    {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        try {
            if ($this->getUser()->getType() == $typeUserRepository->findOneBy(['code' => 'SADM'])) {
                $boutiques = $this->paginationService->paginate($boutiqueRepository->findBy(
                    ['entreprise' => $this->getUser()->getEntreprise()],
                    ['id' => 'ASC']
                ));
            } else {
                $boutiques = $this->paginationService->paginate($boutiqueRepository->findBy(
                    ['id' => $this->getUser()->getBoutique()],
                    ['id' => 'ASC']
                ));
            }
            $response = $this->responseData($boutiques, 'group1', ['Content-Type' => 'application/json']);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            $this->setMessage("Erreur lors de la récupération des boutiques de l'entreprise");
            $response = $this->response([]);
        }

        return $response;
    }

    /**
     * Récupère les détails d'une boutique spécifique
     */
    #[Route('/get/one/{id}', methods: ['GET'])]
    #[OA\Get(
        path: "/api/boutique/get/one/{id}",
        summary: "Détails d'une boutique",
        description: "Affiche les informations détaillées d'une boutique spécifique par son identifiant",
        tags: ['boutique']
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
        description: "Boutique trouvée avec succès",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "id", type: "integer", example: 1, description: "Identifiant de la boutique"),
                new OA\Property(property: "libelle", type: "string", example: "Boutique Centre-Ville", description: "Nom de la boutique"),
                new OA\Property(property: "situation", type: "string", example: "Avenue 12, Abidjan", description: "Adresse"),
                new OA\Property(property: "contact", type: "string", example: "+225 0123456789", description: "Contact"),
                new OA\Property(property: "setIsActive", type: "boolean", example: true, description: "Statut"),
                new OA\Property(property: "entreprise", type: "object", description: "Entreprise propriétaire"),
                new OA\Property(
                    property: "caisseBoutique",
                    type: "object",
                    description: "Caisse de la boutique",
                    properties: [
                        new OA\Property(property: "id", type: "integer", example: 1),
                        new OA\Property(property: "reference", type: "string", example: "CAIS-2025-001"),
                        new OA\Property(property: "montant", type: "string", example: "150000")
                    ]
                ),
                new OA\Property(property: "createdAt", type: "string", format: "date-time"),
                new OA\Property(property: "updatedAt", type: "string", format: "date-time")
            ]
        )
    )]
    #[OA\Response(response: 404, description: "Boutique non trouvée")]
    #[OA\Response(response: 500, description: "Erreur lors de la récupération")]
    public function getOne(?Boutique $boutique): Response
    {
        try {
            if ($boutique) {
                $response = $this->response($boutique);
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
     * Crée une nouvelle boutique avec sa caisse associée
     */
    #[Route('/create', methods: ['POST'])]
    #[OA\Post(
        path: "/api/boutique/create",
        summary: "Créer une nouvelle boutique",
        description: "Permet de créer une nouvelle boutique avec création automatique d'une caisse associée. Nécessite un abonnement actif.",
        tags: ['boutique']
    )]
    #[OA\RequestBody(
        required: true,
        description: "Données de la nouvelle boutique à créer",
        content: new OA\JsonContent(
            type: "object",
            required: ["libelle", "situation", "contact"],
            properties: [
                new OA\Property(
                    property: "libelle",
                    type: "string",
                    example: "Boutique Centre-Ville",
                    description: "Nom de la boutique (obligatoire)"
                ),
                new OA\Property(
                    property: "situation",
                    type: "string",
                    example: "Avenue 12, Quartier Plateau, Abidjan",
                    description: "Adresse ou localisation de la boutique (obligatoire)"
                ),
                new OA\Property(
                    property: "contact",
                    type: "string",
                    example: "+225 0123456789",
                    description: "Numéro de téléphone de contact de la boutique (obligatoire)"
                )
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: "Boutique créée avec succès (avec caisse automatiquement créée)",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "id", type: "integer", example: 5, description: "ID de la boutique créée"),
                new OA\Property(property: "libelle", type: "string", example: "Boutique Centre-Ville"),
                new OA\Property(property: "situation", type: "string", example: "Avenue 12, Abidjan"),
                new OA\Property(property: "contact", type: "string", example: "+225 0123456789"),
                new OA\Property(property: "setIsActive", type: "boolean", example: true),
                new OA\Property(property: "entreprise", type: "object"),
                new OA\Property(
                    property: "caisseBoutique",
                    type: "object",
                    description: "Caisse créée automatiquement",
                    properties: [
                        new OA\Property(property: "reference", type: "string", example: "CAIS-2025-001"),
                        new OA\Property(property: "montant", type: "string", example: "0"),
                        new OA\Property(property: "type", type: "string", example: "boutique")
                    ]
                )
            ]
        )
    )]
    #[OA\Response(response: 400, description: "Données invalides")]
    #[OA\Response(response: 401, description: "Non authentifié")]
    #[OA\Response(response: 403, description: "Abonnement requis pour cette fonctionnalité")]
    public function create(
        Request $request,
        Utils $utils,
        SubscriptionChecker $subscriptionChecker,
        CaisseBoutiqueRepository $caisseBoutiqueRepository,
        BoutiqueRepository $boutiqueRepository
    ): Response {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        $data = json_decode($request->getContent(), true);

        $boutique = new Boutique();
        $boutique->setLibelle($data['libelle']);
        $boutique->setSituation($data['situation']);
  
        $boutique->setEntreprise($this->getUser()->getEntreprise());
        $boutique->setContact($data['contact']);
        $boutique->setIsActive($subscriptionChecker->getSettingByUser($this->getUser()->getEntreprise(), "boutique"));
        $boutique->setCreatedBy($this->getUser());
        $boutique->setUpdatedBy($this->getUser());
        $boutique->setCreatedAtValue(new \DateTime());
        $boutique->setUpdatedAt(new \DateTime());

        $errorResponse = $this->errorResponse($boutique);
        if ($errorResponse !== null) {
            return $errorResponse;
        } else {
            $boutiqueRepository->add($boutique, true);

            // Création automatique de la caisse associée
            $caisse = new CaisseBoutique();
            $caisse->setMontant("0");
            $caisse->setBoutique($boutique);
            $caisse->setIsActive(true);
            $caisse->setReference($utils->generateReference('CAIS'));
            $caisse->setType(Caisse::TYPE['boutique']);
            $caisse->setEntreprise($this->getUser()->getEntreprise());
            $caisse->setCreatedBy($this->getUser());
            $caisse->setUpdatedBy($this->getUser());
            $caisse->setCreatedAtValue(new \DateTime());
            $caisse->setUpdatedAt(new \DateTime());
            $caisseBoutiqueRepository->add($caisse, true);
        }

        return $this->responseData($boutique, 'group1', ['Content-Type' => 'application/json']);
    }

    /**
     * Met à jour une boutique existante
     */
    #[Route('/update/{id}', methods: ['PUT', 'POST'])]
    #[OA\Put(
        path: "/api/boutique/update/{id}",
        summary: "Mettre à jour une boutique",
        description: "Permet de mettre à jour les informations d'une boutique existante. Nécessite un abonnement actif.",
        tags: ['boutique']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: "Identifiant unique de la boutique à mettre à jour",
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\RequestBody(
        required: true,
        description: "Données à mettre à jour",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(
                    property: "libelle",
                    type: "string",
                    example: "Boutique Centre-Ville (Mise à Jour)",
                    description: "Nouveau nom de la boutique"
                ),
                new OA\Property(
                    property: "situation",
                    type: "string",
                    example: "Nouvelle Avenue 15, Abidjan",
                    description: "Nouvelle adresse"
                ),
                new OA\Property(
                    property: "contact",
                    type: "string",
                    example: "+225 0198765432",
                    description: "Nouveau numéro de contact"
                )
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: "Boutique mise à jour avec succès",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "id", type: "integer", example: 1),
                new OA\Property(property: "libelle", type: "string", example: "Boutique Centre-Ville (Mise à Jour)"),
                new OA\Property(property: "situation", type: "string", example: "Nouvelle Avenue 15, Abidjan"),
                new OA\Property(property: "contact", type: "string", example: "+225 0198765432"),
                new OA\Property(property: "updatedAt", type: "string", format: "date-time")
            ]
        )
    )]
    #[OA\Response(response: 400, description: "Données invalides")]
    #[OA\Response(response: 401, description: "Non authentifié")]
    #[OA\Response(response: 403, description: "Abonnement requis pour cette fonctionnalité")]
    #[OA\Response(response: 404, description: "Boutique non trouvée")]
    public function update(Request $request, Boutique $boutique, BoutiqueRepository $boutiqueRepository): Response
    {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        try {
            $data = json_decode($request->getContent());

            if ($boutique != null) {
                $boutique->setLibelle($data->libelle);
                $boutique->setSituation($data->situation);
                $boutique->setEntreprise($this->getUser()->getEntreprise());
                $boutique->setContact($data->contact);
                $boutique->setUpdatedBy($this->getUser());
                $boutique->setUpdatedAt(new \DateTime());

                $errorResponse = $this->errorResponse($boutique);
                if ($errorResponse !== null) {
                    return $errorResponse;
                } else {
                    $boutiqueRepository->add($boutique, true);
                }

                $response = $this->responseData($boutique, 'group1', ['Content-Type' => 'application/json']);
            } else {
                $this->setMessage("Cette ressource est inexistante");
                $this->setStatusCode(404);
                $response = $this->response([]);
            }
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            $this->setMessage("Erreur lors de la mise à jour de la boutique");
            $response = $this->response([]);
        }
        return $response;
    }

    /**
     * Supprime une boutique
     */
    #[Route('/delete/{id}', methods: ['DELETE'])]
    #[OA\Delete(
        path: "/api/boutique/delete/{id}",
        summary: "Supprimer une boutique",
        description: "Permet de supprimer définitivement une boutique par son identifiant. Nécessite un abonnement actif.",
        tags: ['boutique']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: "Identifiant unique de la boutique à supprimer",
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: "Boutique supprimée avec succès",
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
    #[OA\Response(response: 404, description: "Boutique non trouvée")]
    #[OA\Response(response: 500, description: "Erreur lors de la suppression")]
    public function delete(Request $request, Boutique $boutique, BoutiqueRepository $villeRepository): Response
    {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        try {
            if ($boutique != null) {
                $villeRepository->remove($boutique, true);
                $this->setMessage("Operation effectuées avec succès");
                $response = $this->response($boutique);
            } else {
                $this->setMessage("Cette ressource est inexistante");
                $this->setStatusCode(404);
                $response = $this->response([]);
            }
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            $this->setMessage("Erreur lors de la suppression de la boutique");
            $response = $this->response([]);
        }
        return $response;
    }

    /**
     * Supprime plusieurs boutiques en masse
     */
    #[Route('/delete/all/items', methods: ['DELETE'])]
    #[OA\Delete(
        path: "/api/boutique/delete/all/items",
        summary: "Supprimer plusieurs boutiques",
        description: "Permet de supprimer plusieurs boutiques en une seule opération en fournissant un tableau d'identifiants. Nécessite un abonnement actif.",
        tags: ['boutique']
    )]
    #[OA\RequestBody(
        required: true,
        description: "Tableau des identifiants des boutiques à supprimer",
        content: new OA\JsonContent(
            type: "object",
            required: ["ids"],
            properties: [
                new OA\Property(
                    property: 'ids',
                    type: 'array',
                    description: "Liste des identifiants des boutiques à supprimer",
                    items: new OA\Items(type: 'integer', example: 1),
                    example: [1, 2, 3, 5, 8]
                )
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: "Boutiques supprimées avec succès",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "message", type: "string", example: "Operation effectuées avec succès"),
                new OA\Property(property: "deletedCount", type: "integer", example: 5, description: "Nombre de boutiques supprimées")
            ]
        )
    )]
    #[OA\Response(response: 400, description: "Données invalides")]
    #[OA\Response(response: 401, description: "Non authentifié")]
    #[OA\Response(response: 403, description: "Abonnement requis pour cette fonctionnalité")]
    #[OA\Response(response: 500, description: "Erreur lors de la suppression")]
    public function deleteAll(Request $request, BoutiqueRepository $villeRepository): Response
    {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        try {
            $data = json_decode($request->getContent(), true);

            foreach ($data['ids'] as $id) {
                $boutique = $villeRepository->find($id);

                if ($boutique != null) {
                    $villeRepository->remove($boutique);
                }
            }
            $this->setMessage("Operation effectuées avec succès");
            $response = $this->response([]);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            $this->setMessage("Erreur lors de la suppression des boutiques");
            $response = $this->response([]);
        }
        return $response;
    }
}
