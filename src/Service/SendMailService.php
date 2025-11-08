<?php

namespace App\Service;

use App\Entity\Notification;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class SendMailService
{
    private $mailer;
    private $tokenStorage;

    public function __construct(
        MailerInterface $mailer,
        private EntityManagerInterface $em,
        TokenStorageInterface $tokenStorage
    ) {
        $this->mailer = $mailer;
        $this->tokenStorage = $tokenStorage;
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

        $notification = new Notification();
        $notification->setLibelle($data['libelle']);
        $notification->setTitre($data['titre']);
        $notification->isActive(true);
        $notification->setEntreprise($data['entreprise']);
        $notification->setUser($data['user']);

        $notification->setUpdatedBy($currentUser);
        $notification->setCreatedBy($currentUser);

        $notification->setUpdatedAt(new \DateTime());
        $notification->setCreatedAtValue(new \DateTime());

        $this->em->persist($notification);
        $this->em->flush();
    }
}
