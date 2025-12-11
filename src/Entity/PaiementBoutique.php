<?php

namespace App\Entity;

use App\Repository\PaiementBoutiqueRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: PaiementBoutiqueRepository::class)]
class PaiementBoutique extends Paiement
{
   

    #[ORM\ManyToOne(inversedBy: 'paiementBoutiques')]
    #[Groups(["paiement_boutique"])]
    private ?Boutique $boutique = null;

    #[ORM\Column(nullable: true)]
     #[Groups(["group1", "group_type","group_details","group_modeleBoutique","paiement_boutique"])]
    private ?int $quantite = null;

    #[ORM\ManyToOne(inversedBy: 'paiementBoutiques')]
    #[Groups(["group_details","group_modeleBoutique","paiement_boutique"])]
    private ?Client $client = null;
    
    /**
     * @var Collection<int, PaiementBoutiqueLigne>
    */
    #[ORM\OneToMany(targetEntity: PaiementBoutiqueLigne::class, mappedBy: 'paiementBoutique')]
    #[Groups(["paiement_boutique"])]
    private Collection $paiementBoutiqueLignes;

    public function __construct()
    {
        $this->paiementBoutiqueLignes = new ArrayCollection();
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

    public function getQuantite(): ?int
    {
        return $this->quantite;
    }

    public function setQuantite(?int $quantite): static
    {
        $this->quantite = $quantite;

        return $this;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): static
    {
        $this->client = $client;

        return $this;
    }

    /**
     * @return Collection<int, PaiementBoutiqueLigne>
     */
    public function getPaiementBoutiqueLignes(): Collection
    {
        return $this->paiementBoutiqueLignes;
    }

    public function addPaiementBoutiqueLigne(PaiementBoutiqueLigne $paiementBoutiqueLigne): static
    {
        if (!$this->paiementBoutiqueLignes->contains($paiementBoutiqueLigne)) {
            $this->paiementBoutiqueLignes->add($paiementBoutiqueLigne);
            $paiementBoutiqueLigne->setPaiementBoutique($this);
        }

        return $this;
    }

    public function removePaiementBoutiqueLigne(PaiementBoutiqueLigne $paiementBoutiqueLigne): static
    {
        if ($this->paiementBoutiqueLignes->removeElement($paiementBoutiqueLigne)) {
            // set the owning side to null (unless already changed)
            if ($paiementBoutiqueLigne->getPaiementBoutique() === $this) {
                $paiementBoutiqueLigne->setPaiementBoutique(null);
            }
        }

        return $this;
    }


}
