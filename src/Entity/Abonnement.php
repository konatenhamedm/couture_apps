<?php

namespace App\Entity;

use App\Repository\AbonnementRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: AbonnementRepository::class)]
class Abonnement
{
    use TraitEntity;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["group1", "group_abonnement"])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'abonnements')]
    #[Groups(["group_abonnement"])]
    private ?ModuleAbonnement $moduleAbonnement = null;

    #[ORM\Column(length: 255)]
    #[Groups(["group1","group_abonnement"])]
    private ?string $etat = null;

    #[ORM\ManyToOne(inversedBy: 'abonnements')]
    #[Groups(["group1","group_abonnement"])]
    private ?Entreprise $entreprise = null;

    #[ORM\Column]
    #[Groups(["group1","group_abonnement"])]
    private ?\DateTime $dateFin = null;

    #[ORM\Column(length: 255)]
    #[Groups(["group1","group_abonnement"])]
    private ?string $type = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getEtat(): ?string
    {
        return $this->etat;
    }

    public function setEtat(string $etat): static
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

    public function getDateFin(): ?\DateTime
    {
        return $this->dateFin;
    }

    public function setDateFin(\DateTime $dateFin): static
    {
        $this->dateFin = $dateFin;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }
}
