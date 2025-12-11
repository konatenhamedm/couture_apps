<?php

namespace App\Entity;

use App\Repository\PaiementFactureRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: PaiementFactureRepository::class)]
class PaiementFacture extends Paiement
{
   

    #[ORM\ManyToOne(inversedBy: 'paiementFactures')]
     #[Groups(["paiement_boutique"])]
    private ?Facture $facture = null;

    public function getFacture(): ?Facture
    {
        return $this->facture;
    }

    public function setFacture(?Facture $facture): static
    {
        $this->facture = $facture;

        return $this;
    }
}