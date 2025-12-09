<?php

namespace App\Entity;

use App\Repository\PaiementFactureRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PaiementFactureRepository::class)]
class PaiementFacture extends Paiement
{
   

    #[ORM\ManyToOne(inversedBy: 'paiementFactures')]
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