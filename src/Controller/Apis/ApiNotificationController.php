<?php

namespace App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Entity\Notification;
use App\Repository\NotificationRepository;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Annotation\Model as Model;
use Nelmio\ApiDocBundle\Attribute\Security;
use Nelmio\ApiDocBundle\Attribute\Model as AttributeModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

/**
 * Contrôleur pour la gestion des notifications
 * Permet de consulter et supprimer les notifications des utilisateurs
 */
#[Route('/api/notification')]
#[OA\Tag(name: 'notification', description: 'Gestion des notifications système et alertes utilisateurs')]
class ApiNotificationController extends ApiInterface
{
    /**
     * Liste toutes les notifications du système
     */
    #[Route('/', methods: ['GET'])]
    #[OA\Get(
        path: "/api/notification/",
        summary: "Lister toutes les notifications",
        description: "Retourne la liste paginée de toutes les notifications du système, incluant les alertes de paiements, mouvements de stock, et autres événements importants.",
        tags: ['notification']
    )]
    #[OA\Response(
        response: 200,
        description: "Liste des notifications récupérée avec succès",
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(
                type: "object",
                properties: [
                    new OA\Property(property: "id", type: "integer", example: 1, description: "Identifiant unique de la notification"),
                    new OA\Property(property: "titre", type: "string", example: "Nouveau paiement - Boutique Centre-Ville", description: "Titre de la notification"),
                    new OA\Property(property: "libelle", type: "string", example: "Un nouveau paiement de 50 000 FCFA a été enregistré", description: "Message détaillé"),
                    new OA\Property(property: "type", type: "string", example: "paiement", description: "Type de notification (paiement, stock, facture, etc.)"),
                    new OA\Property(property: "isRead", type: "boolean", example: false, description: "Statut de lecture"),
                    new OA\Property(property: "user", type: "object", description: "Utilisateur destinataire",
                        properties: [
                            new OA\Property(property: "id", type: "integer", example: 5),
                            new OA\Property(property: "login", type: "string", example: "admin@entreprise.com"),
                            new OA\Property(property: "nom", type: "string", example: "Kouassi")
                        ]
                    ),
                    new OA\Property(property: "entreprise", type: "object", description: "Entreprise concernée"),
                    new OA\Property(property: "createdAt", type: "string", format: "date-time", example: "2025-01-30T14:30:00+00:00", description: "Date de création")
                ]
            )
        )
    )]
    #[OA\Response(response: 401, description: "Non authentifié")]
    #[OA\Response(response: 500, description: "Erreur serveur lors de la récupération")]
    public function index(NotificationRepository $moduleRepository): Response
    {
        try {
            $categories = $this->paginationService->paginate($moduleRepository->findAll());
            $response = $this->responseData($categories, 'group1', ['Content-Type' => 'application/json']);
        } catch (\Exception $exception) {
$this->setStatusCode(500);
            $this->setMessage("Erreur lors de la récupération des notifications");
            $response = $this->response('[]');
        }

        return $response;
    }

    /**
     * Liste les notifications de l'utilisateur connecté
     */
    #[Route('/user', methods: ['GET'])]
    #[OA\Get(
        path: "/api/notification/user",
        summary: "Lister les notifications de l'utilisateur",
        description: "Retourne la liste paginée de toutes les notifications destinées à l'utilisateur connecté, triées de la plus récente à la plus ancienne. Permet à l'utilisateur de consulter ses alertes personnelles concernant les paiements, les stocks bas, les nouvelles commandes, etc.",
        tags: ['notification']
    )]
    #[OA\Response(
        response: 200,
        description: "Liste des notifications de l'utilisateur récupérée avec succès",
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(
                type: "object",
                properties: [
                    new OA\Property(property: "id", type: "integer", example: 1),
                    new OA\Property(property: "titre", type: "string", example: "Nouveau paiement - Boutique Centre-Ville"),
                    new OA\Property(property: "libelle", type: "string", example: "Bonjour Admin,\n\nUn nouveau paiement de 50 000 FCFA vient d'être enregistré dans la boutique Centre-Ville.\n\nDate : 30/01/2025 14:30"),
                    new OA\Property(property: "type", type: "string", example: "paiement", description: "Type: paiement, stock, facture, commande"),
                    new OA\Property(property: "isRead", type: "boolean", example: false, description: "false = non lue, true = lue"),
                    new OA\Property(property: "user", type: "object", description: "Utilisateur destinataire"),
                    new OA\Property(property: "entreprise", type: "object", description: "Entreprise"),
                    new OA\Property(property: "createdAt", type: "string", format: "date-time", example: "2025-01-30T14:30:00+00:00"),
                    new OA\Property(property: "readAt", type: "string", format: "date-time", nullable: true, example: null, description: "Date de lecture")
                ]
            )
        )
    )]
    #[OA\Response(response: 401, description: "Non authentifié")]
    #[OA\Response(response: 500, description: "Erreur lors de la récupération")]
    public function indexAll(NotificationRepository $moduleRepository): Response
    {
        try {
            $typeMesures = $this->paginationService->paginate($moduleRepository->findBy(
                ['user' => $this->getUser()],
                ['id' => 'DESC']
            ));

            $response = $this->responseData($typeMesures, 'group1', ['Content-Type' => 'application/json']);
        } catch (\Exception $exception) {
$this->setStatusCode(500);
            $this->setMessage("Erreur lors de la récupération de vos notifications");
            $response = $this->response('[]');
        }

        return $response;
    }

    /**
     * Marque une notification comme lue
     */
    #[Route('/mark-read/{id}', methods: ['PUT', 'PATCH'])]
    #[OA\Patch(
        path: "/api/notification/mark-read/{id}",
        summary: "Marquer une notification comme lue",
        description: "Permet de marquer une notification spécifique comme lue. Met à jour le statut isRead à true et enregistre la date de lecture.",
        tags: ['notification']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: "Identifiant unique de la notification à marquer comme lue",
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: "Notification marquée comme lue avec succès",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "id", type: "integer", example: 1),
                new OA\Property(property: "isRead", type: "boolean", example: true),
                new OA\Property(property: "readAt", type: "string", format: "date-time", example: "2025-01-30T15:00:00+00:00")
            ]
        )
    )]
    #[OA\Response(response: 401, description: "Non authentifié")]
    #[OA\Response(response: 404, description: "Notification non trouvée")]
    public function markAsRead(Notification $notification, NotificationRepository $notificationRepository): Response
    {
        try {
            if ($notification != null) {
                $notification->setIsRead(true);
                $notification->setReadAt(new \DateTime());
                $notificationRepository->add($notification, true);

                $this->setMessage("Notification marquée comme lue");
                $response = $this->response($notification);
            } else {
                $this->setMessage("Cette notification est inexistante");
                $this->setStatusCode(404);
                $response = $this->response('[]');
            }
        } catch (\Exception $exception) {
$this->setStatusCode(500);
            $this->setMessage("Erreur lors de la mise à jour de la notification");
            $response = $this->response('[]');
        }

        return $response;
    }

    /**
     * Marque toutes les notifications de l'utilisateur comme lues
     */
    #[Route('/mark-all-read', methods: ['PUT', 'PATCH'])]
    #[OA\Patch(
        path: "/api/notification/mark-all-read",
        summary: "Marquer toutes les notifications comme lues",
        description: "Permet de marquer toutes les notifications non lues de l'utilisateur connecté comme lues en une seule opération.",
        tags: ['notification']
    )]
    #[OA\Response(
        response: 200,
        description: "Toutes les notifications ont été marquées comme lues",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "message", type: "string", example: "Toutes les notifications ont été marquées comme lues"),
                new OA\Property(property: "count", type: "integer", example: 15, description: "Nombre de notifications mises à jour")
            ]
        )
    )]
    #[OA\Response(response: 401, description: "Non authentifié")]
    public function markAllAsRead(NotificationRepository $notificationRepository): Response
    {
        try {
            $notifications = $notificationRepository->findBy([
                'user' => $this->getUser(),
                'isRead' => false
            ]);

            $count = 0;
            foreach ($notifications as $notification) {
                $notification->setIsRead(true);
                $notification->setReadAt(new \DateTime());
                $notificationRepository->add($notification, true);
                $count++;
            }

            $this->setMessage("Toutes les notifications ont été marquées comme lues");
            $response = $this->json(['message' => 'Toutes les notifications ont été marquées comme lues', 'count' => $count]);
        } catch (\Exception $exception) {
$this->setStatusCode(500);
            $this->setMessage("Erreur lors de la mise à jour des notifications");
            $response = $this->response('[]');
        }

        return $response;
    }

    /**
     * Supprime une notification
     */
    #[Route('/delete/{id}', methods: ['DELETE'])]
    #[OA\Delete(
        path: "/api/notification/delete/{id}",
        summary: "Supprimer une notification",
        description: "Permet de supprimer définitivement une notification par son identifiant. L'utilisateur peut supprimer ses propres notifications pour nettoyer sa boîte de réception.",
        tags: ['notification']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: "Identifiant unique de la notification à supprimer",
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: "Notification supprimée avec succès",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "message", type: "string", example: "Operation effectuées avec succès"),
                new OA\Property(property: "deleted", type: "boolean", example: true)
            ]
        )
    )]
    #[OA\Response(response: 401, description: "Non authentifié")]
    #[OA\Response(response: 404, description: "Notification non trouvée")]
    #[OA\Response(response: 500, description: "Erreur lors de la suppression")]
    public function delete(Request $request, Notification $notification, NotificationRepository $villeRepository): Response
    {
        try {
            if ($notification != null) {
                $villeRepository->remove($notification, true);
                $this->setMessage("Operation effectuées avec succès");
                $response = $this->response($notification);
            } else {
                $this->setMessage("Cette ressource est inexistante");
                $this->setStatusCode(404);
                $response = $this->response('[]');
            }
        } catch (\Exception $exception) {
$this->setStatusCode(500);
            $this->setMessage("Erreur lors de la suppression de la notification");
            $response = $this->response('[]');
        }
        return $response;
    }

    /**
     * Supprime plusieurs notifications en masse
     */
    #[Route('/delete/all/items', methods: ['DELETE'])]
    #[OA\Delete(
        path: "/api/notification/delete/all/items",
        summary: "Supprimer plusieurs notifications",
        description: "Permet de supprimer plusieurs notifications en une seule opération en fournissant un tableau d'identifiants. Utile pour nettoyer rapidement plusieurs notifications obsolètes.",
        tags: ['notification']
    )]
    #[OA\RequestBody(
        required: true,
        description: "Tableau des identifiants des notifications à supprimer",
        content: new OA\JsonContent(
            type: "object",
            required: ["ids"],
            properties: [
                new OA\Property(
                    property: 'ids',
                    type: 'array',
                    description: "Liste des identifiants des notifications à supprimer",
                    items: new OA\Items(type: 'integer', example: 1),
                    example: [1, 2, 3, 5, 8]
                )
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: "Notifications supprimées avec succès",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "message", type: "string", example: "Operation effectuées avec succès"),
                new OA\Property(property: "deletedCount", type: "integer", example: 5, description: "Nombre de notifications supprimées")
            ]
        )
    )]
    #[OA\Response(response: 400, description: "Données invalides")]
    #[OA\Response(response: 401, description: "Non authentifié")]
    #[OA\Response(response: 500, description: "Erreur lors de la suppression")]
   /*  #[Security(name: 'Bearer')] */
    public function deleteAll(Request $request, NotificationRepository $villeRepository): Response
    {
        try {
            $data = json_decode($request->getContent(), true);

            $count = 0;
            foreach ($data['ids'] as $id) {
                $notification = $villeRepository->find($id);

                if ($notification != null) {
                    $villeRepository->remove($notification);
                    $count++;
                }
            }
            $this->setMessage("Operation effectuées avec succès");
            $response = $this->json(['message' => 'Operation effectuées avec succès', 'deletedCount' => $count]);
        } catch (\Exception $exception) {
$this->setStatusCode(500);
            $this->setMessage("Erreur lors de la suppression des notifications");
            $response = $this->response('[]');
        }
        return $response;
    }

    /**
     * Compte les notifications non lues de l'utilisateur
     */
    #[Route('/unread-count', methods: ['GET'])]
    #[OA\Get(
        path: "/api/notification/unread-count",
        summary: "Compter les notifications non lues",
        description: "Retourne le nombre de notifications non lues de l'utilisateur connecté. Utile pour afficher un badge avec le nombre de notifications en attente.",
        tags: ['notification']
    )]
    #[OA\Response(
        response: 200,
        description: "Nombre de notifications non lues récupéré avec succès",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "unreadCount", type: "integer", example: 5, description: "Nombre de notifications non lues"),
                new OA\Property(property: "totalCount", type: "integer", example: 25, description: "Nombre total de notifications")
            ]
        )
    )]
    #[OA\Response(response: 401, description: "Non authentifié")]
    public function getUnreadCount(NotificationRepository $notificationRepository): Response
    {
        try {
            $unreadCount = $notificationRepository->count([
                'user' => $this->getUser(),
                'isRead' => false
            ]);

            $totalCount = $notificationRepository->count([
                'user' => $this->getUser()
            ]);

            $response = $this->json([
                'unreadCount' => $unreadCount,
                'totalCount' => $totalCount
            ]);
        } catch (\Exception $exception) {
$this->setStatusCode(500);
            $this->setMessage("Erreur lors du comptage des notifications");
            $response = $this->response('[]');
        }

        return $response;
    }
}