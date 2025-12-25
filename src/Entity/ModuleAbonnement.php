<?php

namespace App\Entity;

use App\Repository\ModuleAbonnementRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ModuleAbonnementRepository::class)]
#[UniqueEntity(fields: 'code', message: 'Ce code est déjà associé à un autre module d\'abonnement.')]
class ModuleAbonnement
{use TraitEntity;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
     #[Groups(["group1", "group_type","group_abonnement"])]
    private ?int $id = null;

    #[ORM\Column]
     #[Groups(["group1", "group_type","group_abonnement"])]
    private ?bool $etat = null;

    #[ORM\Column(type: Types::TEXT)]
     #[Groups(["group1", "group_type","group_abonnement"])]
    private ?string $description = null;

    #[ORM\Column(length: 255)]
     #[Groups(["group1", "group_type","group_abonnement"])]
    private ?string $montant = null;

    #[ORM\Column(length: 255)]
     #[Groups(["group1", "group_type","group_abonnement"])]
    private ?string $duree = null;

    /**
     * @var Collection<int, LigneModule>
     */
    #[ORM\OneToMany(targetEntity: LigneModule::class, mappedBy: 'moduleAbonnement')]
     #[Groups(["group1", "group_type","group_abonnement"])]
    private Collection $ligneModules;

    /**
     * @var Collection<int, Abonnement>
     */
    #[ORM\OneToMany(targetEntity: Abonnement::class, mappedBy: 'moduleAbonnement')]
    private Collection $abonnements;



    #[ORM\Column(length: 255)]
     #[Groups(["group1", "group_type","group_abonnement"])]
     private ?string $code = null;
     
     /**
      * @var Collection<int, PaiementAbonnement>
     */
    #[ORM\OneToMany(targetEntity: PaiementAbonnement::class, mappedBy: 'moduleAbonnement')]
    private Collection $paiementAbonnements;
    
    #[ORM\Column(nullable: true, unique: true)]
    #[Groups(["group1", "group_type","group_abonnement"])]
    private ?int $numero = null;

    #[ORM\ManyToOne(inversedBy: 'moduleAbonnements')]
    private ?Pays $pays = null;

    public function __construct()
    {
        $this->ligneModules = new ArrayCollection();
        $this->abonnements = new ArrayCollection();
        $this->paiementAbonnements = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function isEtat(): ?bool
    {
        return $this->etat;
    }

    public function setEtat(bool $etat): static
    {
        $this->etat = $etat;

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

    public function getMontant(): ?string
    {
        return $this->montant;
    }

    public function setMontant(string $montant): static
    {
        $this->montant = $montant;

        return $this;
    }

    public function getDuree(): ?string
    {
        return $this->duree;
    }

    public function setDuree(string $duree): static
    {
        $this->duree = $duree;

        return $this;
    }

    /**
     * @return Collection<int, LigneModule>
     */
    public function getLigneModules(): Collection
    {
        return $this->ligneModules;
    }

    public function addLigneModule(LigneModule $ligneModule): static
    {
        if (!$this->ligneModules->contains($ligneModule)) {
            $this->ligneModules->add($ligneModule);
            $ligneModule->setModuleAbonnement($this);
        }

        return $this;
    }

    public function removeLigneModule(LigneModule $ligneModule): static
    {
        if ($this->ligneModules->removeElement($ligneModule)) {
            // set the owning side to null (unless already changed)
            if ($ligneModule->getModuleAbonnement() === $this) {
                $ligneModule->setModuleAbonnement(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Abonnement>
     */
    public function getAbonnements(): Collection
    {
        return $this->abonnements;
    }

    public function addAbonnement(Abonnement $abonnement): static
    {
        if (!$this->abonnements->contains($abonnement)) {
            $this->abonnements->add($abonnement);
            $abonnement->setModuleAbonnement($this);
        }

        return $this;
    }

    public function removeAbonnement(Abonnement $abonnement): static
    {
        if ($this->abonnements->removeElement($abonnement)) {
            // set the owning side to null (unless already changed)
            if ($abonnement->getModuleAbonnement() === $this) {
                $abonnement->setModuleAbonnement(null);
            }
        }

        return $this;
    }


    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;

        return $this;
    }

    /**
     * @return Collection<int, PaiementAbonnement>
     */
    public function getPaiementAbonnements(): Collection
    {
        return $this->paiementAbonnements;
    }

    public function addPaiementAbonnement(PaiementAbonnement $paiementAbonnement): static
    {
        if (!$this->paiementAbonnements->contains($paiementAbonnement)) {
            $this->paiementAbonnements->add($paiementAbonnement);
            $paiementAbonnement->setModuleAbonnement($this);
        }

        return $this;
    }

    public function removePaiementAbonnement(PaiementAbonnement $paiementAbonnement): static
    {
        if ($this->paiementAbonnements->removeElement($paiementAbonnement)) {
            // set the owning side to null (unless already changed)
            if ($paiementAbonnement->getModuleAbonnement() === $this) {
                $paiementAbonnement->setModuleAbonnement(null);
            }
        }

        return $this;
    }

    public function getNumero(): ?int
    {
        return $this->numero;
    }

    public function setNumero(?int $numero): static
    {
        $this->numero = $numero;

        return $this;
    }

    public function getPays(): ?Pays
    {
        return $this->pays;
    }

    public function setPays(?Pays $pays): static
    {
        $this->pays = $pays;

        return $this;
    }
}
