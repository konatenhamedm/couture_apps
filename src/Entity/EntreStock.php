<?php

namespace App\Entity;

use App\Repository\EntreStockRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: EntreStockRepository::class)]
class EntreStock
{ use TraitEntity;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
     #[Groups(["group1", "group_type","group_ligne","group_details","group_modeleBoutique"])]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
     #[Groups(["group1", "group_type","group_ligne","group_details","group_modeleBoutique"])]
    private ?\DateTime $date = null;

    #[ORM\Column]
     #[Groups(["group1", "group_type","group_ligne","group_details","group_modeleBoutique"])]
    private ?int $quantite = null;

    #[ORM\ManyToOne(inversedBy: 'entreStocks')]
     #[Groups(["group1", "group_type"])]
    private ?Entreprise $entreprise = null;

    #[ORM\ManyToOne(inversedBy: 'entreStocks')]
     #[Groups(["group1", "group_type","group_ligne"])]
    private ?Boutique $boutique = null;

    /**
     * @var Collection<int, LigneEntre>
     */
    #[ORM\OneToMany(targetEntity: LigneEntre::class, mappedBy: 'entreStock',)]
     #[Groups(["group1", "group_type"])]
    private Collection $ligneEntres;

    #[ORM\Column(length: 255)]
    private ?string $type = null;

    #[ORM\Column(length: 50, options: ["default" => "EN_ATTENTE"])]
    #[Groups(["group1", "group_type", "group_ligne", "group_details","group_modeleBoutique"])]
    private ?string $statut = 'EN_ATTENTE';

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(["group1", "group_type","group_modeleBoutique"])]
    private ?string $commentaire = null;

    public function __construct()
    {
        $this->ligneEntres = new ArrayCollection();
        $this->date = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDate(): ?\DateTime
    {
        return $this->date;
    }

    public function setDate(?\DateTime $date): static
    {
        $this->date = $date;

        return $this;
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

    public function getEntreprise(): ?Entreprise
    {
        return $this->entreprise;
    }

    public function setEntreprise(?Entreprise $entreprise): static
    {
        $this->entreprise = $entreprise;

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
            $ligneEntre->setEntreStock($this);
        }

        return $this;
    }

    public function removeLigneEntre(LigneEntre $ligneEntre): static
    {
        if ($this->ligneEntres->removeElement($ligneEntre)) {
            // set the owning side to null (unless already changed)
            if ($ligneEntre->getEntreStock() === $this) {
                $ligneEntre->setEntreStock(null);
            }
        }

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

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getCommentaire(): ?string
    {
        return $this->commentaire;
    }

    public function setCommentaire(?string $commentaire): static
    {
        $this->commentaire = $commentaire;

        return $this;
    }
}
