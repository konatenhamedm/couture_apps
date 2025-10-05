<?php

namespace App\Service;

use App\Entity\Entreprise;
use App\Entity\Notification;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service central de gestion des notifications :
 * - Enregistre en base
 * - Envoie la notification push
 */
class NotificationService
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserRepository $userRepository,
        private PushNotificationService $pushService,
        private LoggerInterface $logger
    ) {}

    /**
     * Crée et envoie une notification à un utilisateur.
     */
    public function notify(int $userId, Entreprise $entreprise, string $title, string $message, array $data = []): void
    {
        $user = $this->userRepository->find($userId);
        if (!$user) {
            $this->logger->warning("❌ Notification ignorée : utilisateur #$userId introuvable.");
            return;
        }

        // Enrichir la data pour le mobile
        $data = array_merge($data, [
            'notification_id' => uniqid('notif_'),
            'user_id' => $user->getId(),
            'entreprise_id' => $entreprise->getId(),
            'timestamp' => time(),
        ]);

        // Enregistrer en base
        $notification = (new Notification())
            ->setUser($user)
            ->setTitre($title)
            ->setEntreprise($entreprise)
            ->setLibelle($message)
            ->setUpdatedBy($user)
            ->setCreatedBy($user)
            ->setEtat(false);

        $this->em->persist($notification);
        $this->em->flush();

        // Envoyer push si un token FCM existe
        $token = $user->getFcmToken();
        if (!$token) {
            $this->logger->info("ℹ️ Aucun token FCM pour l'utilisateur #{$user->getId()}");
            return;
        }

        try {
            $this->pushService->sendPush($token, $title, $message, $data);
            $this->logger->info("✅ Notification push envoyée à l'utilisateur #{$user->getId()}");
        } catch (\Throwable $e) {
            $this->logger->error("⚠️ Erreur FCM pour l'utilisateur #{$user->getId()}: " . $e->getMessage());
        }
    }
}
