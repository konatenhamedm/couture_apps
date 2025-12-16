<?php

namespace App\Service;

use App\Entity\Notification;
use App\Entity\User;
use App\Trait\DatabaseEnvironmentTrait;
use Doctrine\ORM\EntityManagerInterface;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class SendMailService
{
    use DatabaseEnvironmentTrait;
    private $mailer;
    private $tokenStorage;
    private $firebase;

    public function __construct(
        MailerInterface $mailer,
        private EntityManagerProvider $em,
        TokenStorageInterface $tokenStorage
    ) {
        $this->mailer = $mailer;
        $this->tokenStorage = $tokenStorage;
        
        // Initialiser Firebase avec gestion d'erreur
        try {
            $credentialsPath = __DIR__ . '/../../config/firebase_credentials.json';
            if (file_exists($credentialsPath) && filesize($credentialsPath) > 0) {
                $this->firebase = (new Factory)
                    ->withServiceAccount($credentialsPath)
                    ->createMessaging();
            } else {
                error_log('Fichier Firebase credentials manquant ou vide');
                $this->firebase = null;
            }
        } catch (\Exception $e) {
            error_log('Erreur initialisation Firebase: ' . $e->getMessage());
            $this->firebase = null;
        }
    }

    /**
     * Récupère l'utilisateur connecté
     */
    public function getCurrentUser(): ?UserInterface
    {
        $token = $this->tokenStorage->getToken();

        if (null === $token) {
            return null;
        }

        $user = $token->getUser();
        if ($user instanceof User) {
            return $user;
        }

        return null;
    }

    public function send(
        string $from,
        string $to,
        string $subject,
        string $template,
        array $context
    ): void {
        //On crée le mail
        $email = (new TemplatedEmail())
            ->from($from)
            ->to($to)
            ->subject($subject)
            ->htmlTemplate("emails/$template.html.twig")
            ->context($context);

        // On envoie le mail
        $this->mailer->send($email);
    }

    public function sendNotification($data = [])
    {
        $currentUser = $this->getCurrentUser();

        // Créer la notification en base
        $notification = new Notification();
        $notification->setLibelle($data['libelle']);
        $notification->setTitre($data['titre']);
        $notification->setIsActive(true);
        $notification->setEntreprise($data['entreprise']);
        $notification->setUser($data['user']);
        $notification->setUpdatedBy($currentUser);
        $notification->setCreatedBy($currentUser);
        $notification->setUpdatedAt(new \DateTime());
        $notification->setCreatedAtValue(new \DateTime());

        $this->save($notification);
        

        // Envoyer la notification push Firebase
       // $this->sendPushNotification($data, $data['user']); // TO DO pour activer l'envoie des notifications push
    }

    /**
     * Envoie une notification push Firebase
     */
    public function sendPushNotification(User $user, array $data = [])
    {
        if ($this->firebase === null) {
            error_log('Firebase non initialisé, impossible d\'envoyer la notification push');
            return;
        }

        try {
            $firebaseNotification = FirebaseNotification::create(
                $data['titre'] ?? 'Nouvelle notification',
                $data['libelle'] ?? ''
            );

            // Si un token FCM spécifique est fourni
            if ($user->getFcmToken() !== null && !empty($user->getFcmToken())) {
                $message = CloudMessage::withTarget('token', $user->getFcmToken())
                    ->withNotification($firebaseNotification)
                    ->withData([
                        'type' => $data['type'] ?? 'general',
                        'user_id' => (string)($data['user']?->getId() ?? ''),
                        'entreprise_id' => (string)($data['entreprise']?->getId() ?? ''),
                        'timestamp' => (new \DateTime())->format('Y-m-d H:i:s')
                    ]);

                $this->firebase->send($message);
            }
            elseif (isset($data['entreprise'])) {
                $topic = 'entreprise_' . $data['entreprise']->getId();
                
                $message = CloudMessage::withTarget('topic', $topic)
                    ->withNotification($firebaseNotification)
                    ->withData([
                        'type' => $data['type'] ?? 'general',
                        'entreprise_id' => (string)$data['entreprise']->getId(),
                        'timestamp' => (new \DateTime())->format('Y-m-d H:i:s')
                    ]);

                $this->firebase->send($message);
            }

        } catch (\Exception $e) {
            error_log('Erreur envoi notification push: ' . $e->getMessage());
        }
    }

    /**
     * S'abonner un utilisateur à un topic Firebase
     */
    public function subscribeToTopic(string $fcmToken, string $topic): bool
    {
        if ($this->firebase === null) {
            return false;
        }

        try {
            $this->firebase->subscribeToTopic($topic, [$fcmToken]);
            return true;
        } catch (\Exception $e) {
            error_log('Erreur abonnement topic: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Désabonner un utilisateur d'un topic Firebase
     */
    public function unsubscribeFromTopic(string $fcmToken, string $topic): bool
    {
        if ($this->firebase === null) {
            return false;
        }

        try {
            $this->firebase->unsubscribeFromTopic($topic, [$fcmToken]);
            return true;
        } catch (\Exception $e) {
            error_log('Erreur désabonnement topic: ' . $e->getMessage());
            return false;
        }
    }
}
