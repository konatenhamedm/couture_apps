<?php

namespace App\Service;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Psr\Log\LoggerInterface;

/**
 * Service responsable de l'envoi des notifications push via Firebase Cloud Messaging (FCM).
 * Utilise le SDK officiel Kreait Firebase.
 */
class PushNotificationService
{
    private $messaging;

    public function __construct(
        private EntityManagerInterface $em,
        private UserRepository $userRepository,
        private LoggerInterface $logger // Optionnel, pour loguer proprement
    ) {
        // Initialise Firebase via la clé de service
        $factory = (new Factory)
            ->withServiceAccount(__DIR__ . '/../../config/firebase_credentials.json');

        $this->messaging = $factory->createMessaging();
    }

    /**
     * Envoie une notification push à un utilisateur via Firebase.
     */
    public function sendPush(string $token, string $title, string $body, array $data = []): void
    {
        try {
            // Construction du message
            $message = CloudMessage::withTarget('token', $token)
                ->withNotification(Notification::create($title, $body))
                ->withData($data);

            // Envoi
            $this->messaging->send($message);

            $this->logger->info("✅ Notification envoyée à $token");
        } catch (\Kreait\Firebase\Exception\Messaging\NotFound $e) {
            // Token non valide ou supprimé côté Firebase
            $this->invalidateToken($token);
            $this->logger->warning("🚫 Token supprimé car invalide : $token");
        } catch (\Throwable $e) {
            // Autre erreur (réseau, permission, etc.)
            $this->logger->error("Erreur envoi FCM : " . $e->getMessage());
        }
    }

    /**
     * Supprime le token FCM invalide de la base de données.
     */
    private function invalidateToken(string $token): void
    {
        $user = $this->userRepository->findOneBy(['fcmToken' => $token]);
        if ($user) {
            $user->setFcmToken(null);
            $this->em->flush();
        }
    }
}
