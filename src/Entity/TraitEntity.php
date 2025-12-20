<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use DateTimeImmutable;
use Symfony\Component\Serializer\Annotation\Groups as Group;


trait TraitEntity
{

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Group(["group_user", "group1", "group_type", "group_user_trx", "group_pro","group_modeleBoutique","paiement_boutique"])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Group(["group_user"])]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(targetEntity: User::class, cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: true)]
    #[Group(["group_pro","group_modeleBoutique"])]
    private ?User $createdBy = null;

    #[ORM\ManyToOne(targetEntity: User::class, cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $updatedBy = null;

    #[ORM\Column(options: ['default' => true])]
    #[Group(["group1", "group_type","group_modeleBoutique"])]
    private bool $isActive = true; // ✅ Non-nullable avec valeur par défaut

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        if ($this->createdAt === null) {
            $this->createdAt = new DateTimeImmutable();
        }
        // S'assurer que isActive a une valeur par défaut
        if (!isset($this->isActive)) {
            $this->isActive = true;
        }
    }

    #[ORM\PreUpdate]
    public function setUpdatedAt(): void
    {
        $this->updatedAt = new DateTimeImmutable();
        // S'assurer que isActive a une valeur par défaut même lors des mises à jour
        if (!isset($this->isActive)) {
            $this->isActive = true;
        }
    }

    /**
     * Initialise les valeurs par défaut du trait
     * À appeler dans le constructeur des entités qui utilisent ce trait
     */
    public function initializeTraitDefaults(): void
    {
        if (!isset($this->isActive)) {
            $this->isActive = true;
        }
        if ($this->createdAt === null) {
            $this->createdAt = new DateTimeImmutable();
        }
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $user): self
    {
        $this->createdBy = $user;
        return $this;
    }

    public function getUpdatedBy(): ?User
    {
        return $this->updatedBy;
    }

    public function setUpdatedBy(?User $user): self
    {
        $this->updatedBy = $user;
        return $this;
    }

    public function isActive(): bool
    {
        // S'assurer qu'isActive a toujours une valeur par défaut
        if (!isset($this->isActive)) {
            $this->isActive = true;
        }
        return $this->isActive;
    }

    public function setIsActive(bool $actif): static
    {
        $this->isActive = $actif;
        return $this;
    }
}