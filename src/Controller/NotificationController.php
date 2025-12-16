<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
class NotificationController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserRepository $userRepository
    ) {}


    

    #[Route('/device-token', name: 'save_device_token', methods: ['POST'])]
    #[OA\Post(
        path: "/api/device-token",
        summary: "Associe ou met à jour le token FCM d'un utilisateur",
        description: "Cette route permet d'enregistrer ou mettre à jour le token Firebase Cloud Messaging (FCM) d'un utilisateur identifié par son login.",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(property: "login", type: "string", example: "konatenhamed@gmail.com"),
                    new OA\Property(property: "token", type: "string", example: "fcm_12345_example_token")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Token enregistré ou mis à jour avec succès"),
            new OA\Response(response: 404, description: "Utilisateur non trouvé"),
            new OA\Response(response: 400, description: "Paramètres manquants")
        ]
    )]
    #[OA\Tag(name: 'auth')]
    public function saveDeviceToken(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $login = $data['login'] ?? null;
        $token = $data['token'] ?? null;

        if (!$login || !$token) {
            return $this->json(['message' => 'Login ou token manquant'], 400);
        }

        $user = $this->userRepository->findOneBy(['login' => $login]);
        if (!$user) {
            return $this->json(['message' => 'Utilisateur non trouvé'], 404);
        }

        $user->setFcmToken($token);
        $this->em->flush();

        return $this->json([
            'message' => 'Token FCM mis à jour avec succès',
            'user_id' => $user->getId()
        ]);
    }
}