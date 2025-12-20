<?php

namespace App\Entity;

use App\Repository\CaisseBoutiqueRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CaisseBoutiqueRepository::class)]
#[ORM\Table(name: 'caisse_boutique')]
class CaisseBoutique extends Caisse
{
   

    #[ORM\ManyToOne(inversedBy: 'caisseBoutiques')]
    private ?Boutique $boutique = null;



    public function getBoutique(): ?Boutique
    {
        return $this->boutique;
    }

    public function setBoutique(?Boutique $boutique): static
    {
        $this->boutique = $boutique;

        return $this;
    }
}
