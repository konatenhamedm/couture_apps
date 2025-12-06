<?php

namespace App\Entity;

use App\Repository\ReservationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ReservationRepository::class)]
class Reservation
{ use TraitEntity;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
     #[Groups(["group1","group_details","group_reservation"])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
     #[Groups(["group1","group_details","group_reservation"])]
    private ?string $montant = null;

    #[ORM\Column(nullable: true)]
     #[Groups(["group1","group_details","group_reservation"])]
    private ?\DateTime $dateRetrait = null;

    #[ORM\Column(length: 255, nullable: true)]
     #[Groups(["group1","group_details","group_reservation"])]
    private ?string $avance = null;

    #[ORM\Column(length: 255, nullable: true)]
     #[Groups(["group1","group_details","group_reservation"])]
    private ?string $reste = null;

    #[ORM\ManyToOne(inversedBy: 'reservations')]
     #[Groups(["group1","group_details","group_reservation"])]
    private ?Client $client = null;

    /**
     * @var Collection<int, LigneReservation>
     */
    #[ORM\OneToMany(targetEntity: LigneReservation::class, mappedBy: 'reservation')]
     #[Groups(["group1","group_reservation"])]
    private Collection $ligneReservations;

    /**
     * @var Collection<int, PaiementReservation>
     */
    #[ORM\OneToMany(targetEntity: PaiementReservation::class, mappedBy: 'reservation')]
    private Collection $paiementReservations;

    #[ORM\ManyToOne(inversedBy: 'reservations')]
    private ?Entreprise $entreprise = null;

    #[ORM\ManyToOne(inversedBy: 'reservations')]
    private ?Boutique $boutique = null;

    public function __construct()
    {
        $this->ligneReservations = new ArrayCollection();
        $this->paiementReservations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getDateRetrait(): ?\DateTime
    {
        return $this->dateRetrait;
    }

    public function setDateRetrait(\DateTime $dateRetrait): static
    {
        $this->dateRetrait = $dateRetrait;

        return $this;
    }

    public function getAvance(): ?string
    {
        return $this->avance;
    }

    public function setAvance(string $avance): static
    {
        $this->avance = $avance;

        return $this;
    }

    public function getReste(): ?string
    {
        return $this->reste;
    }

    public function setReste(?string $reste): static
    {
        $this->reste = $reste;

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
            $ligneReservation->setReservation($this);
        }

        return $this;
    }

    public function removeLigneReservation(LigneReservation $ligneReservation): static
    {
        if ($this->ligneReservations->removeElement($ligneReservation)) {
            // set the owning side to null (unless already changed)
            if ($ligneReservation->getReservation() === $this) {
                $ligneReservation->setReservation(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, PaiementReservation>
     */
    public function getPaiementReservations(): Collection
    {
        return $this->paiementReservations;
    }

    public function addPaiementReservation(PaiementReservation $paiementReservation): static
    {
        if (!$this->paiementReservations->contains($paiementReservation)) {
            $this->paiementReservations->add($paiementReservation);
            $paiementReservation->setReservation($this);
        }

        return $this;
    }

    public function removePaiementReservation(PaiementReservation $paiementReservation): static
    {
        if ($this->paiementReservations->removeElement($paiementReservation)) {
            // set the owning side to null (unless already changed)
            if ($paiementReservation->getReservation() === $this) {
                $paiementReservation->setReservation(null);
            }
        }

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
}
