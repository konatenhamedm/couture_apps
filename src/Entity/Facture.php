<?php

namespace App\Entity;

use App\Repository\FactureRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: FactureRepository::class)]
class Facture
{
    use TraitEntity;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["group1", "group_type","paiement_boutique"])]

    private ?int $id = null;

    #[ORM\Column]
    #[Groups(["group1", "group_type","paiement_boutique"])]
    private ?\DateTime $dateRetrait = null;

    #[ORM\Column]
    #[Groups(["group1", "group_type","paiement_boutique"])]
    private ?\DateTime $dateDepot = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(["group1", "group_type","paiement_boutique"])]
    private ?string $avance = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(["group1", "group_type","paiement_boutique"])]
    private ?string $MontantTotal = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(["group1", "group_type","paiement_boutique"])]
    private ?string $remise = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(["group1", "group_type","paiement_boutique"])]
    private ?string $ResteArgent = null;

    #[ORM\ManyToOne(inversedBy: 'factures')]
    #[Groups(["group1", "group_type","paiement_boutique"])]
    private ?Client $client = null;


    #[ORM\ManyToOne(cascade: ["persist"], fetch: "EAGER")]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(["fichier", "group1","paiement_boutique"])]
    private ?Fichier $signature = null;

    /**
     * @var Collection<int, Mesure>
     */
    #[ORM\OneToMany(targetEntity: Mesure::class, mappedBy: 'facture')]
    #[Groups(["group1", "group_type"])]
    private Collection $mesures;

    /**
     * @var Collection<int, PaiementFacture>
     */
    #[ORM\OneToMany(targetEntity: PaiementFacture::class, mappedBy: 'facture',cascade: ['persist', 'remove'],
    orphanRemoval: true)]
    #[Groups(["group1", "group_type"])]
    private Collection $paiementFactures;

    #[ORM\ManyToOne(inversedBy: 'factures')]
    private ?Entreprise $entreprise = null;

    #[ORM\ManyToOne(inversedBy: 'factures')]
    #[Groups(["group1", "group_type"])]
    private ?Surccursale $succursale = null;



    public function __construct()
    {
        $this->mesures = new ArrayCollection();
        $this->paiementFactures = new ArrayCollection();
    
        // Initialiser les valeurs par défaut du trait
        $this->initializeTraitDefaults();}

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateRetrait(): ?\DateTime
    {
        return $this->dateRetrait;
    }

    public function setDateRetrait(\DateTime $dateRetrait): static
    {
        $this->dateRetrait = $dateRetrait;

        return $this;
    }

    public function getDateDepot(): ?\DateTime
    {
        return $this->dateDepot;
    }

    public function setDateDepot(\DateTime $dateDepot): static
    {
        $this->dateDepot = $dateDepot;

        return $this;
    }

    public function getAvance(): ?string
    {
        return $this->avance;
    }

    public function setAvance(?string $avance): static
    {
        $this->avance = $avance;

        return $this;
    }

    public function getMontantTotal(): ?string
    {
        return $this->MontantTotal;
    }

    public function setMontantTotal(?string $MontantTotal): static
    {
        $this->MontantTotal = $MontantTotal;

        return $this;
    }

    public function getRemise(): ?string
    {
        return $this->remise;
    }

    public function setRemise(?string $remise): static
    {
        $this->remise = $remise;

        return $this;
    }

    public function getResteArgent(): ?string
    {
        return $this->ResteArgent;
    }

    public function setResteArgent(?string $ResteArgent): static
    {
        $this->ResteArgent = $ResteArgent;

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
     * @return Collection<int, Mesure>
     */
    public function getMesures(): Collection
    {
        return $this->mesures;
    }

    public function addMesure(Mesure $mesure): static
    {
        if (!$this->mesures->contains($mesure)) {
            $this->mesures->add($mesure);
            $mesure->setFacture($this);
        }

        return $this;
    }

    public function removeMesure(Mesure $mesure): static
    {
        if ($this->mesures->removeElement($mesure)) {
            // set the owning side to null (unless already changed)
            if ($mesure->getFacture() === $this) {
                $mesure->setFacture(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, PaiementFacture>
     */
    public function getPaiementFactures(): Collection
    {
        return $this->paiementFactures;
    }

    public function addPaiementFacture(PaiementFacture $paiementFacture): static
    {
        if (!$this->paiementFactures->contains($paiementFacture)) {
            $this->paiementFactures->add($paiementFacture);
            $paiementFacture->setFacture($this);
        }

        return $this;
    }

    public function removePaiementFacture(PaiementFacture $paiementFacture): static
    {
        if ($this->paiementFactures->removeElement($paiementFacture)) {
            // set the owning side to null (unless already changed)
            if ($paiementFacture->getFacture() === $this) {
                $paiementFacture->setFacture(null);
            }
        }

        return $this;
    }

    public function getSignature(): ?Fichier
    {
        return $this->signature;
    }

    public function setSignature(?Fichier $signature): static
    {
        $this->signature = $signature;

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

    public function getSuccursale(): ?Surccursale
    {
        return $this->succursale;
    }

    public function setSuccursale(?Surccursale $succursale): static
    {
        $this->succursale = $succursale;

        return $this;
    }
}
