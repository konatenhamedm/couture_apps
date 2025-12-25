<?php

namespace App\Entity;

use App\Repository\LigneModuleRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: LigneModuleRepository::class)]
class LigneModule
{ use TraitEntity;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
     #[Groups(["group1", "group_type","group_abonnement"])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
     #[Groups(["group1", "group_type","group_abonnement"])]
    private ?string $libelle = null;

    #[ORM\Column(type: Types::TEXT)]
     #[Groups(["group1", "group_type","group_abonnement"])]
    private ?string $description = null;


    #[ORM\ManyToOne(inversedBy: 'ligneModules')]
    private ?ModuleAbonnement $moduleAbonnement = null;

    #[ORM\Column(length: 255, nullable: true)]
     #[Groups(["group1", "group_type","group_abonnement"])]
    private ?string $quantite = null;


    public function getId(): ?int
    {
        return $this->id;
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

   

    public function getModuleAbonnement(): ?ModuleAbonnement
    {
        return $this->moduleAbonnement;
    }

    public function setModuleAbonnement(?ModuleAbonnement $moduleAbonnement): static
    {
        $this->moduleAbonnement = $moduleAbonnement;

        return $this;
    }

    public function getModule(): ?Module
    {
        return $this->module;
    }

    public function setModule(?Module $module): static
    {
        $this->module = $module;

        return $this;
    }

    public function getQuantite(): ?string
    {
        return $this->quantite;
    }

    public function setQuantite(?string $quantite): static
    {
        $this->quantite = $quantite;

        return $this;
    }
}
