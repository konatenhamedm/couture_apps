<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use OpenApi\Attributes as OA;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Serializer\Annotation\Groups;

#[OA\Schema(
    schema: 'User',
    description: 'Utilisateur de l’application',
    type: 'object'
)]
#[ORM\Entity(repositoryClass: "App\Repository\UserRepository")]
#[ORM\Table(name: "users")]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{

   // use TraitEntity;
    #[OA\Property(description: 'ID unique de l’utilisateur', type: 'integer', example: 1)]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    #[Groups(["group1"])]
    private ?int $id = null;

    #[OA\Property(description: 'Login (email ou identifiant)', type: 'string', example: 'jane.doe')]
    #[ORM\Column(type: "string", length: 180, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 180)]
    #[Groups(["group1"])]
    private string $login;

    #[OA\Property(description: 'Rôles de l’utilisateur', type: 'array', items: new OA\Items(type: 'string'))]
    #[ORM\Column(type: "json")]
    #[Groups(["group1"])]
    private array $roles = [];

    #[OA\Property(description: 'Mot de passe hashé', type: 'string', example: '$2y$13$xxx')]
    #[ORM\Column(type: "string")]
    private string $password;

    #[OA\Property(description: 'Statut actif de l’utilisateur', type: 'boolean', example: true)]
    #[ORM\Column(type: "boolean")]
    #[Groups(["group1"])]
    private bool $isActive = true;

    #[OA\Property(description: 'Date de création du compte', type: 'string', format: 'date-time')]
    #[ORM\Column(type: "datetime_immutable", nullable: true)]
    #[Groups(["group1"])]
    private ?\DateTimeImmutable $createdAt;


    #[ORM\ManyToOne(inversedBy: 'users')]
    #[Groups(["group1"])]
    private ?Surccursale $surccursale = null;

    #[ORM\ManyToOne(inversedBy: 'users')]
    private ?Entreprise $entreprise = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(["group1"])]
    private ?string $nom = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(["group1"])]
    private ?string $prenoms = null;

    /**
     * @var Collection<int, Notification>
     */
    #[ORM\OneToMany(targetEntity: Notification::class, mappedBy: 'user')]
    private Collection $notifications;

    #[ORM\ManyToOne(inversedBy: 'users')]
    #[Groups(["group1"])]
    private ?TypeUser $type = null;


    #[ORM\ManyToOne(cascade: ["persist"], fetch: "EAGER")]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(["fichier", "group_pro", "group1"])]
    private ?Fichier $logo = null;

    #[ORM\ManyToOne(inversedBy: 'users')]
    #[Groups(["group1"])]
    private ?Boutique $boutique = null;


    #[OA\Property(description: 'Token de réinitialisation simple (6 chiffres)', type: 'string', example: '123456')]
    #[ORM\Column(type: "string", length: 6, nullable: true)]
    private ?string $plainResetToken = null;

    #[OA\Property(description: 'Date d\'expiration du token simple', type: 'string', format: 'date-time')]
    #[ORM\Column(type: "datetime_immutable", nullable: true)]
    private ?\DateTimeImmutable $plainTokenExpiresAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $fcmToken = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    // ... Getters and setters (pas besoin de toucher ici)
    // Tu peux garder ceux que tu as déjà.

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLogin(): string
    {
        return $this->login;
    }

    public function setLogin(string $login): self
    {
        $this->login = $login;
        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function eraseCredentials(): void {}

    public function getUserIdentifier(): string
    {
        return $this->login;
    }

    public function getUsername(): string
    {
        return $this->getUserIdentifier();
    }

    public function setRoles(array $roles): self
    {
        $this->roles = array_unique($roles);
        return $this;
    }

    public function getSurccursale(): ?Surccursale
    {
        return $this->surccursale;
    }

    public function setSurccursale(?Surccursale $surccursale): static
    {
        $this->surccursale = $surccursale;

        return $this;
    }

    public function getEntreprise(): ?Entreprise
    {
        return $this->entreprise;
    }

    public function setEntreprise(?Entreprise $entreprise): static
    {
        $this->entreprise = $entreprise;

        return $this;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(?string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getPrenoms(): ?string
    {
        return $this->prenoms;
    }

    public function setPrenoms(?string $prenoms): static
    {
        $this->prenoms = $prenoms;

        return $this;
    }

    /**
     * @return Collection<int, Notification>
     */
    public function getNotifications(): Collection
    {
        return $this->notifications;
    }

    public function addNotification(Notification $notification): static
    {
        if (!$this->notifications->contains($notification)) {
            $this->notifications->add($notification);
            $notification->setUser($this);
        }

        return $this;
    }

    public function removeNotification(Notification $notification): static
    {
        if ($this->notifications->removeElement($notification)) {
            // set the owning side to null (unless already changed)
            if ($notification->getUser() === $this) {
                $notification->setUser(null);
            }
        }

        return $this;
    }

    public function getType(): ?TypeUser
    {
        return $this->type;
    }

    public function setType(?TypeUser $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getLogo(): ?Fichier
    {
        return $this->logo;
    }

    public function setLogo(?Fichier $logo): static
    {
        $this->logo = $logo;

        return $this;
    }

    public function getBoutique(): ?Boutique
    {
        return $this->boutique;
    }

    public function setBoutique(?Boutique $boutique): static
    {
        $this->boutique = $boutique;

        return $this;
    }

    public function getPlainResetToken(): ?string
    {
        return $this->plainResetToken;
    }

    public function setPlainResetToken(?string $plainResetToken): self
    {
        $this->plainResetToken = $plainResetToken;
        return $this;
    }

    public function getPlainTokenExpiresAt(): ?\DateTimeImmutable
    {
        return $this->plainTokenExpiresAt;
    }

    public function setPlainTokenExpiresAt(?\DateTimeImmutable $plainTokenExpiresAt): self
    {
        $this->plainTokenExpiresAt = $plainTokenExpiresAt;
        return $this;
    }

    public function getFcmToken(): ?string
    {
        return $this->fcmToken;
    }

    public function setFcmToken(?string $fcmToken): static
    {
        $this->fcmToken = $fcmToken;

        return $this;
    }
}
