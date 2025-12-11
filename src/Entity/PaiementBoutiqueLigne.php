<?php

namespace App\Entity;

use App\Repository\PaiementBoutiqueLigneRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: PaiementBoutiqueLigneRepository::class)]
class PaiementBoutiqueLigne
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["group_details","group_modeleBoutique","paiement_boutique"])]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    #[Groups(["group_details","group_modeleBoutique","paiement_boutique"])]
    private ?int $quantite = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(["group_details","group_modeleBoutique","paiement_boutique"])]
    private ?string $montant = null;
    
    #[ORM\ManyToOne(inversedBy: 'paiementBoutiqueLignes')]
    #[Groups(["paiement_boutique"])]
    private ?ModeleBoutique $modeleBoutique = null;

    #[ORM\ManyToOne(inversedBy: 'paiementBoutiqueLignes')]
    #[Groups(["group_details","group_modeleBoutique"])]
    private ?PaiementBoutique $paiementBoutique = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuantite(): ?int
    {
        return $this->quantite;
    }

    public function setQuantite(?int $quantite): static
    {
        $this->quantite = $quantite;

        return $this;
    }

    public function getMontant(): ?string
    {
        return $this->montant;
    }

    public function setMontant(?string $montant): static
    {
        $this->montant = $montant;

        return $this;
    }

    public function getModeleBoutique(): ?ModeleBoutique
    {
        return $this->modeleBoutique;
    }

    public function setModeleBoutique(?ModeleBoutique $modeleBoutique): static
    {
        $this->modeleBoutique = $modeleBoutique;

        return $this;
    }

    public function getPaiementBoutique(): ?PaiementBoutique
    {
        return $this->paiementBoutique;
    }

    public function setPaiementBoutique(?PaiementBoutique $paiementBoutique): static
    {
        $this->paiementBoutique = $paiementBoutique;

        return $this;
    }
}
