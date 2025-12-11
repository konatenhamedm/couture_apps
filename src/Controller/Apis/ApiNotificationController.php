<?php

namespace App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use App\Repository\NotificationRepository;
use App\Service\SendMailService;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Request;

#[Route('/api/notification')]
#[OA\Tag(name: 'notification', description: 'Gestion des notifications push Firebase')]
class ApiNotificationController extends ApiInterface
{


    /**
     * Liste toutes les catégories de mesure du système
     */
    #[Route('/', methods: ['GET'])]
    #[OA\Get(
        path: "/api/notification",
        summary: "Lister toutes les notifications d'un user",
        description: "Lister toutes les notifications d'un user",
        tags: ['notification']
    )]
    #[OA\Response(response: 500, description: "Erreur serveur lors de la récupération")]
    public function getUserNotifications(NotificationRepository $notificationRepository)
    {

        try {
            $notifications = $this->paginationService->paginate($notificationRepository->findBy(
                ['user' => $this->getUser()],
                ['id' => 'DESC']
            ));

            $response = $this->responseData($notifications, 'group1', ['Content-Type' => 'application/json'], true);
        } catch (\Throwable $th) {
            $this->setStatusCode(500);
            $this->setMessage("Erreur lors de la récupération des catégories de mesure");
            $response = $this->response([]);
        }

        return $response;
    }

    /**
     * Met à jour le token FCM d'un utilisateur
     */
    #[Route('/fcm-token', methods: ['POST'])]
    #[OA\Post(
        path: "/api/notification/fcm-token",
        summary: "Mettre à jour le token FCM",
        description: "Met à jour le token Firebase Cloud Messaging de l'utilisateur connecté pour recevoir les notifications push.",
        tags: ['notification']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            type: "object",
            required: ["fcm_token"],
            properties: [
                new OA\Property(
                    property: "fcm_token",
                    type: "string",
                    example: "dGhpc19pc19hX3Rva2Vu...",
                    description: "Token FCM généré par l'application mobile"
                )
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: "Token FCM mis à jour avec succès",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "status", type: "string", example: "SUCCESS"),
                new OA\Property(property: "message", type: "string", example: "Token FCM mis à jour avec succès")
            ]
        )
    )]
    public function updateFcmToken(Request $request, UserRepository $userRepository, SendMailService $sendMailService): Response
    {
        $data = json_decode($request->getContent(), true);
        $fcmToken = $data['fcm_token'] ?? null;

        if (!$fcmToken) {
            $this->setMessage("Token FCM requis");
            return $this->response('[]', 400);
        }

        $user = $this->getUser();
        $user->setFcmToken($fcmToken);
        $userRepository->add($user, true);

        // S'abonner au topic de l'entreprise
        if ($user->getEntreprise()) {
            $topic = 'entreprise_' . $user->getEntreprise()->getId();
            $sendMailService->subscribeToTopic($fcmToken, $topic);
        }

        $this->setMessage("Token FCM mis à jour avec succès");
        return $this->response([]);
    }

    /**
     * Envoie une notification de test
     */
    #[Route('/test', methods: ['POST'])]
    #[OA\Post(
        path: "/api/notification/test",
        summary: "Envoyer une notification de test",
        description: "Envoie une notification push de test à l'utilisateur connecté.",
        tags: ['notification']
    )]
    #[OA\RequestBody(
        required: false,
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "titre", type: "string", example: "Test notification"),
                new OA\Property(property: "message", type: "string", example: "Ceci est un test")
            ]
        )
    )]
    public function sendTestNotification(Request $request, SendMailService $sendMailService): Response
    {
        $data = json_decode($request->getContent(), true);
        $user = $this->getUser();

        if (!$user->getFcmToken()) {
            $this->setMessage("Aucun token FCM enregistré pour cet utilisateur");
            return $this->response('[]', 400);
        }

        $notificationData = [
            'titre' => $data['titre'] ?? 'Test notification',
            'libelle' => $data['message'] ?? 'Ceci est une notification de test',
            'type' => 'test',
            'user' => $user,
            'entreprise' => $user->getEntreprise(),
            'fcm_token' => $user->getFcmToken()
        ];

        $sendMailService->sendNotification($notificationData);

        $this->setMessage("Notification de test envoyée");
        return $this->response([]);
    }
}
