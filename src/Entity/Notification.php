<?php

namespace App\Entity;

use App\Repository\NotificationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: NotificationRepository::class)]
class Notification
{use TraitEntity;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
     #[Groups(["group1", "group_type"])]
    private ?int $id = null;

 

    #[ORM\Column]
     #[Groups(["group1", "group_type"])]
    private ?bool $etat = null;

    #[ORM\ManyToOne(inversedBy: 'notifications')]
    private ?Entreprise $entreprise = null;

    #[ORM\Column(length: 255)]
     #[Groups(["group1", "group_type"])]
    private ?string $titre = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
     #[Groups(["group1", "group_type"])]
    private ?string $libelle = null;

    #[ORM\ManyToOne(inversedBy: 'notifications')]
    private ?User $user = null;

    public function __construct()
    {
        $this->etat = true; // Par défaut, la notification est active
    
        // Initialiser les valeurs par défaut du trait
        $this->initializeTraitDefaults();}

    public function getId(): ?int
    {
        return $this->id;
    }

    public function isEtat(): ?bool
    {
        return $this->etat;
    }

    public function setEtat(bool $etat): static
    {
        $this->etat = $etat;

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

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;

        return $this;
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): static
    {
        $this->libelle = $libelle;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }
}
