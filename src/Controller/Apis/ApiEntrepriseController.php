<?php

namespace App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use App\Entity\Entreprise;
use App\Repository\BoutiqueRepository;
use App\Repository\EntrepriseRepository;
use App\Repository\SurccursaleRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Attribute\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

/**
 * Contrôleur pour la gestion des informations d'entreprise
 * Fournit des vues consolidées sur les boutiques et succursales de l'entreprise
 */
#[Route('/api/entreprise')]
#[OA\Tag(name: 'entreprise', description: 'Gestion des informations et structures d\'entreprise')]
class ApiEntrepriseController extends ApiInterface
{
    /**
     * Récupère toutes les boutiques et succursales de l'entreprise de l'utilisateur
     */
    #[Route('/surccursale/boutique', methods: ['GET'])]
    #[OA\Get(
        path: "/api/entreprise/surccursale/boutique",
        summary: "Lister les boutiques et succursales de l'entreprise",
        description: "Retourne une vue consolidée contenant toutes les boutiques et toutes les succursales de l'entreprise de l'utilisateur authentifié. Pratique pour avoir une vue d'ensemble de la structure organisationnelle.",
        tags: ['entreprise']
    )]
    #[OA\Response(
        response: 200,
        description: "Liste des boutiques et succursales récupérée avec succès",
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(
                    property: "surccursales",
                    type: "array",
                    description: "Liste paginée des succursales de l'entreprise",
                    items: new OA\Items(
                        type: "object",
                        properties: [
                            new OA\Property(property: "id", type: "integer", example: 1, description: "Identifiant de la succursale"),
                            new OA\Property(property: "libelle", type: "string", example: "Succursale Plateau", description: "Nom de la succursale"),
                            new OA\Property(property: "situation", type: "string", example: "Avenue 7, Plateau", description: "Adresse de la succursale"),
                            new OA\Property(property: "contact", type: "string", example: "+225 0123456789", description: "Numéro de contact"),
                            new OA\Property(property: "isActive", type: "boolean", example: true, description: "Statut d'activité"),
                            new OA\Property(property: "entreprise", type: "object", description: "Entreprise associée"),
                            new OA\Property(property: "createdAt", type: "string", format: "date-time", example: "2025-01-15T10:30:00+00:00")
                        ]
                    )
                ),
                new OA\Property(
                    property: "boutiques",
                    type: "array",
                    description: "Liste paginée des boutiques de l'entreprise",
                    items: new OA\Items(
                        type: "object",
                        properties: [
                            new OA\Property(property: "id", type: "integer", example: 1, description: "Identifiant de la boutique"),
                            new OA\Property(property: "libelle", type: "string", example: "Boutique Centre-Ville", description: "Nom de la boutique"),
                            new OA\Property(property: "situation", type: "string", example: "Avenue 12, Abidjan", description: "Adresse de la boutique"),
                            new OA\Property(property: "contact", type: "string", example: "+225 0198765432", description: "Numéro de contact"),
                            new OA\Property(property: "isActive", type: "boolean", example: true, description: "Statut d'activité"),
                            new OA\Property(property: "entreprise", type: "object", description: "Entreprise associée"),
                            new OA\Property(property: "caisseBoutique", type: "object", description: "Caisse de la boutique"),
                            new OA\Property(property: "createdAt", type: "string", format: "date-time", example: "2025-01-20T14:45:00+00:00")
                        ]
                    )
                ),
                new OA\Property(
                    property: "pagination",
                    type: "object",
                    description: "Informations de pagination",
                    properties: [
                        new OA\Property(property: "currentPage", type: "integer", example: 1),
                        new OA\Property(property: "totalPages", type: "integer", example: 3),
                        new OA\Property(property: "totalItems", type: "integer", example: 25)
                    ]
                )
            ]
        )
    )]
    #[OA\Response(response: 401, description: "Non authentifié - L'utilisateur doit être connecté")]
    #[OA\Response(response: 403, description: "Abonnement requis pour cette fonctionnalité")]
    #[OA\Response(response: 500, description: "Erreur serveur lors de la récupération des données")]
    public function index(SurccursaleRepository $surccursaleRepository, BoutiqueRepository $boutiqueRepository): Response
    {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        try {
            $surccursales = $this->paginationService->paginate(
                $surccursaleRepository->findBy(
                    ['entreprise' => $this->getUser()->getEntreprise()],
                    ['id' => 'ASC']
                )
            );

            $boutiques = $this->paginationService->paginate(
                $boutiqueRepository->findBy(
                    ['entreprise' => $this->getUser()->getEntreprise()],
                    ['id' => 'ASC']
                )
            );

            $data = [
                "surccursales" => $surccursales,
                "boutiques" => $boutiques
            ];

            $response = $this->responseData($data, 'group1', ['Content-Type' => 'application/json']);
        } catch (\Exception $exception) {
$this->setStatusCode(500);
            $this->setMessage("Erreur lors de la récupération des boutiques et succursales de l'entreprise");
            $response = $this->response([]);
        }

        return $response;
    }

    /**
     * Récupère les informations détaillées de l'entreprise de l'utilisateur
     */
    #[Route('/info', methods: ['GET'])]
    #[OA\Get(
        path: "/api/entreprise/info",
        summary: "Informations de l'entreprise",
        description: "Retourne les informations détaillées de l'entreprise de l'utilisateur authentifié, incluant les paramètres, statistiques et configuration.",
        tags: ['entreprise']
    )]
    #[OA\Response(
        response: 200,
        description: "Informations de l'entreprise récupérées avec succès",
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: "id", type: "integer", example: 1, description: "Identifiant de l'entreprise"),
                new OA\Property(property: "nom", type: "string", example: "Mon Entreprise SARL", description: "Nom de l'entreprise"),
                new OA\Property(property: "email", type: "string", format: "email", example: "contact@entreprise.com", description: "Email de contact"),
                new OA\Property(property: "telephone", type: "string", example: "+225 0123456789", description: "Téléphone principal"),
                new OA\Property(property: "adresse", type: "string", example: "Avenue 15, Abidjan", description: "Adresse du siège"),
                new OA\Property(property: "logo", type: "string", nullable: true, example: "/uploads/logos/logo_001.png", description: "Logo de l'entreprise"),
                new OA\Property(property: "isActive", type: "boolean", example: true, description: "Statut d'activité"),
                new OA\Property(
                    property: "abonnement",
                    type: "object",
                    nullable: true,
                    description: "Abonnement actif",
                    properties: [
                        new OA\Property(property: "id", type: "integer", example: 5),
                        new OA\Property(property: "libelle", type: "string", example: "Abonnement Premium"),
                        new OA\Property(property: "etat", type: "string", example: "actif"),
                        new OA\Property(property: "dateFin", type: "string", format: "date-time")
                    ]
                ),
                new OA\Property(
                    property: "statistiques",
                    type: "object",
                    description: "Statistiques de l'entreprise",
                    properties: [
                        new OA\Property(property: "nombreBoutiques", type: "integer", example: 5),
                        new OA\Property(property: "nombreSuccursales", type: "integer", example: 3),
                        new OA\Property(property: "nombreEmployes", type: "integer", example: 25),
                        new OA\Property(property: "nombreClients", type: "integer", example: 150)
                    ]
                ),
                new OA\Property(property: "createdAt", type: "string", format: "date-time"),
                new OA\Property(property: "updatedAt", type: "string", format: "date-time")
            ]
        )
    )]
    #[OA\Response(response: 401, description: "Non authentifié")]
    #[OA\Response(response: 403, description: "Abonnement requis pour cette fonctionnalité")]
    #[OA\Response(response: 500, description: "Erreur lors de la récupération")]
    public function getEntrepriseInfo(EntrepriseRepository $entrepriseRepository): Response
    {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        try {
            $entreprise = $this->getUser()->getEntreprise();

            if (!$entreprise) {
                $this->setMessage("Aucune entreprise associée à cet utilisateur");
                $this->setStatusCode(404);
                return $this->response('[]');
            }

            $response = $this->responseData($entreprise, 'group1', ['Content-Type' => 'application/json']);
        } catch (\Exception $exception) {
$this->setStatusCode(500);
            $this->setMessage("Erreur lors de la récupération des informations de l'entreprise");
            $response = $this->response([]);
        }

        return $response;
    }

    /**
     * Récupère uniquement les succursales de l'entreprise
     */
    #[Route('/surccursales', methods: ['GET'])]
    #[OA\Get(
        path: "/api/entreprise/surccursales",
        summary: "Lister uniquement les succursales",
        description: "Retourne la liste paginée de toutes les succursales de l'entreprise de l'utilisateur authentifié.",
        tags: ['entreprise']
    )]
    #[OA\Response(
        response: 200,
        description: "Liste des succursales récupérée avec succès",
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(
                type: "object",
                properties: [
                    new OA\Property(property: "id", type: "integer", example: 1),
                    new OA\Property(property: "libelle", type: "string", example: "Succursale Plateau"),
                    new OA\Property(property: "situation", type: "string", example: "Avenue 7, Plateau"),
                    new OA\Property(property: "contact", type: "string", example: "+225 0123456789"),
                    new OA\Property(property: "isActive", type: "boolean", example: true),
                    new OA\Property(property: "entreprise", type: "object")
                ]
            )
        )
    )]
    #[OA\Response(response: 401, description: "Non authentifié")]
    #[OA\Response(response: 403, description: "Abonnement requis pour cette fonctionnalité")]
    public function getSurccursales(SurccursaleRepository $surccursaleRepository): Response
    {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        try {
            $surccursales = $this->paginationService->paginate(
                $surccursaleRepository->findBy(
                    ['entreprise' => $this->getUser()->getEntreprise()],
                    ['id' => 'ASC']
                )
            );

            $response = $this->responseData($surccursales, 'group1', ['Content-Type' => 'application/json']);
        } catch (\Exception $exception) {
$this->setStatusCode(500);
            $this->setMessage("Erreur lors de la récupération des succursales");
            $response = $this->response([]);
        }

        return $response;
    }

    /**
     * Récupère uniquement les boutiques de l'entreprise
     */
    #[Route('/boutiques', methods: ['GET'])]
    #[OA\Get(
        path: "/api/entreprise/boutiques",
        summary: "Lister uniquement les boutiques",
        description: "Retourne la liste paginée de toutes les boutiques de l'entreprise de l'utilisateur authentifié.",
        tags: ['entreprise']
    )]
    #[OA\Response(
        response: 200,
        description: "Liste des boutiques récupérée avec succès",
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(
                type: "object",
                properties: [
                    new OA\Property(property: "id", type: "integer", example: 1),
                    new OA\Property(property: "libelle", type: "string", example: "Boutique Centre-Ville"),
                    new OA\Property(property: "situation", type: "string", example: "Avenue 12, Abidjan"),
                    new OA\Property(property: "contact", type: "string", example: "+225 0198765432"),
                    new OA\Property(property: "isActive", type: "boolean", example: true),
                    new OA\Property(property: "entreprise", type: "object"),
                    new OA\Property(property: "caisseBoutique", type: "object")
                ]
            )
        )
    )]
    #[OA\Response(response: 401, description: "Non authentifié")]
    #[OA\Response(response: 403, description: "Abonnement requis pour cette fonctionnalité")]
    public function getBoutiques(BoutiqueRepository $boutiqueRepository): Response
    {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        try {
            $boutiques = $this->paginationService->paginate(
                $boutiqueRepository->findBy(
                    ['entreprise' => $this->getUser()->getEntreprise()],
                    ['id' => 'ASC']
                )
            );

            $response = $this->responseData($boutiques, 'group1', ['Content-Type' => 'application/json']);
        } catch (\Exception $exception) {
$this->setStatusCode(500);
            $this->setMessage("Erreur lors de la récupération des boutiques");
            $response = $this->response([]);
        }

        return $response;
    }
}