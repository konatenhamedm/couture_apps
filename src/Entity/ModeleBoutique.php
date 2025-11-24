<?php

namespace App\Entity;

use App\Repository\ModeleBoutiqueRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ModeleBoutiqueRepository::class)]
#[UniqueEntity(fields: ['boutique', 'modele'], message: 'Cet modele est deja ajoute au boutique.', errorPath: 'modele')]
class ModeleBoutique
{
    use TraitEntity;
    
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
     #[Groups(["group1","group_ligne","group_details"])]
    private ?int $id = null;

    #[ORM\Column]
     #[Groups(["group1","group_ligne","group_details"])]
    private ?int $quantite = null;

    #[ORM\Column(length: 255)]
     #[Groups(["group1","group_ligne","group_details"])]
    private ?string $prix = null;

    #[ORM\ManyToOne(inversedBy: 'modeleBoutiques')]
     #[Groups(["group1","group_details"])]
    private ?Modele $modele = null;

    #[ORM\ManyToOne(inversedBy: 'modeleBoutiques')]
    private ?Boutique $boutique = null;

    /**
     * @var Collection<int, LigneEntre>
     */
    #[ORM\OneToMany(targetEntity: LigneEntre::class, mappedBy: 'modele')]
    #[Groups(["group_details"])]
    private Collection $ligneEntres;

    /**
     * @var Collection<int, LigneReservation>
     */
    #[ORM\OneToMany(targetEntity: LigneReservation::class, mappedBy: 'modele')]
    #[Groups(["group_details"])]
    private Collection $ligneReservations;

    /**
     * @var Collection<int, PaiementBoutiqueLigne>
     */
    #[ORM\OneToMany(targetEntity: PaiementBoutiqueLigne::class, mappedBy: 'modeleBoutique')]
    #[Groups(["group_details"]) ]
    private Collection $paiementBoutiqueLignes;

    #[ORM\Column(length: 255)]
    #[Groups(["group1","group_details"]) ]
    private ?string $taille = null;



    public function __construct()
    {
        $this->ligneEntres = new ArrayCollection();
        $this->ligneReservations = new ArrayCollection();
        $this->paiementBoutiqueLignes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuantite(): ?int
    {
        return $this->quantite;
    }

    public function setQuantite(int $quantite): static
    {
        $this->quantite = $quantite;

        return $this;
    }

    public function getPrix(): ?string
    {
        return $this->prix;
    }

    public function setPrix(string $prix): static
    {
        $this->prix = $prix;

        return $this;
    }

    public function getModele(): ?Modele
    {
        return $this->modele;
    }

    public function setModele(?Modele $modele): static
    {
        $this->modele = $modele;

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

    /**
     * @return Collection<int, LigneEntre>
     */
    public function getLigneEntres(): Collection
    {
        return $this->ligneEntres;
    }

    public function addLigneEntre(LigneEntre $ligneEntre): static
    {
        if (!$this->ligneEntres->contains($ligneEntre)) {
            $this->ligneEntres->add($ligneEntre);
            $ligneEntre->setModele($this);
        }

        return $this;
    }

    public function removeLigneEntre(LigneEntre $ligneEntre): static
    {
        if ($this->ligneEntres->removeElement($ligneEntre)) {
            // set the owning side to null (unless already changed)
            if ($ligneEntre->getModele() === $this) {
                $ligneEntre->setModele(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, LigneReservation>
     */
    public function getLigneReservations(): Collection
    {
        return $this->ligneReservations;
    }

    public function addLigneReservation(LigneReservation $ligneReservation): static
    {
        if (!$this->ligneReservations->contains($ligneReservation)) {
            $this->ligneReservations->add($ligneReservation);
            $ligneReservation->setModele($this);
        }

        return $this;
    }

    public function removeLigneReservation(LigneReservation $ligneReservation): static
    {
        if ($this->ligneReservations->removeElement($ligneReservation)) {
            // set the owning side to null (unless already changed)
            if ($ligneReservation->getModele() === $this) {
                $ligneReservation->setModele(null);
            }
        }

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
            $paiementBoutiqueLigne->setModeleBoutique($this);
        }

        return $this;
    }

    public function removePaiementBoutiqueLigne(PaiementBoutiqueLigne $paiementBoutiqueLigne): static
    {
        if ($this->paiementBoutiqueLignes->removeElement($paiementBoutiqueLigne)) {
            // set the owning side to null (unless already changed)
            if ($paiementBoutiqueLigne->getModeleBoutique() === $this) {
                $paiementBoutiqueLigne->setModeleBoutique(null);
            }
        }

        return $this;
    }

    public function getTaille(): ?string
    {
        return $this->taille;
    }

    public function setTaille(string $taille): static
    {
        $this->taille = $taille;

        return $this;
    }

   
}
