<?php

namespace App\Entity;

use App\Enum\ReservationStatus;
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
     #[Groups(["group1","group_details","group_reservation","group_modeleBoutique","paiement_boutique"])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
     #[Groups(["group1","group_details","group_reservation","group_modeleBoutique","paiement_boutique"])]
    private ?string $montant = null;

    #[ORM\Column(nullable: true)]
     #[Groups(["group1","group_details","group_reservation","group_modeleBoutique","paiement_boutique"])]
    private ?\DateTime $dateRetrait = null;

    #[ORM\Column(length: 255, nullable: true)]
     #[Groups(["group1","group_details","group_reservation","group_modeleBoutique","paiement_boutique"])]
    private ?string $avance = null;

    #[ORM\Column(length: 255, nullable: true)]
     #[Groups(["group1","group_details","group_reservation","group_modeleBoutique","paiement_boutique"])]
    private ?string $reste = null;

    #[ORM\ManyToOne(inversedBy: 'reservations')]
     #[Groups(["group1","group_details","group_reservation","group_modeleBoutique","paiement_boutique"])]
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
    #[Groups(["group1","group_details","group_reservation","group_modeleBoutique","paiement_boutique"])]
    private ?Entreprise $entreprise = null;

    #[ORM\ManyToOne(inversedBy: 'reservations')]
    #[Groups(["group1","group_details","group_reservation","group_modeleBoutique","paiement_boutique"])]
    private ?Boutique $boutique = null;

    // Nouveaux champs pour le workflow de réservation
    #[ORM\Column(length: 20, options: ['default' => 'en_attente'])]
    #[Groups(["group1","group_details","group_reservation","group_modeleBoutique","paiement_boutique"])]
    private string $status = ReservationStatus::EN_ATTENTE->value;

    #[ORM\Column(nullable: true)]
    #[Groups(["group1","group_details","group_reservation","group_modeleBoutique","paiement_boutique"])]
    private ?\DateTime $confirmedAt = null;

    #[ORM\ManyToOne]
    #[Groups(["group1","group_details","group_reservation"])]
    private ?User $confirmedBy = null;

    #[ORM\Column(nullable: true)]
    #[Groups(["group1","group_details","group_reservation","group_modeleBoutique","paiement_boutique"])]
    private ?\DateTime $cancelledAt = null;

    #[ORM\ManyToOne]
    #[Groups(["group1","group_details","group_reservation"])]
    private ?User $cancelledBy = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(["group1","group_details","group_reservation"])]
    private ?string $cancellationReason = null;

    /**
     * @var Collection<int, ReservationStatusHistory>
     */
    #[ORM\OneToMany(targetEntity: ReservationStatusHistory::class, mappedBy: 'reservation')]
    #[Groups(["group_details","group_reservation"])]
    private Collection $statusHistory;

    public function __construct()
    {
        $this->ligneReservations = new ArrayCollection();
        $this->paiementReservations = new ArrayCollection();
        $this->statusHistory = new ArrayCollection();
        $this->status = ReservationStatus::EN_ATTENTE->value;
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

    // Getters et setters pour les nouveaux champs de workflow

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getStatusEnum(): ReservationStatus
    {
        return ReservationStatus::from($this->status);
    }

    public function setStatusEnum(ReservationStatus $status): static
    {
        $this->status = $status->value;

        return $this;
    }

    public function getConfirmedAt(): ?\DateTime
    {
        return $this->confirmedAt;
    }

    public function setConfirmedAt(?\DateTime $confirmedAt): static
    {
        $this->confirmedAt = $confirmedAt;

        return $this;
    }

    public function getConfirmedBy(): ?User
    {
        return $this->confirmedBy;
    }

    public function setConfirmedBy(?User $confirmedBy): static
    {
        $this->confirmedBy = $confirmedBy;

        return $this;
    }

    public function getCancelledAt(): ?\DateTime
    {
        return $this->cancelledAt;
    }

    public function setCancelledAt(?\DateTime $cancelledAt): static
    {
        $this->cancelledAt = $cancelledAt;

        return $this;
    }

    public function getCancelledBy(): ?User
    {
        return $this->cancelledBy;
    }

    public function setCancelledBy(?User $cancelledBy): static
    {
        $this->cancelledBy = $cancelledBy;

        return $this;
    }

    public function getCancellationReason(): ?string
    {
        return $this->cancellationReason;
    }

    public function setCancellationReason(?string $cancellationReason): static
    {
        $this->cancellationReason = $cancellationReason;

        return $this;
    }

    /**
     * @return Collection<int, ReservationStatusHistory>
     */
    public function getStatusHistory(): Collection
    {
        return $this->statusHistory;
    }

    public function addStatusHistory(ReservationStatusHistory $statusHistory): static
    {
        if (!$this->statusHistory->contains($statusHistory)) {
            $this->statusHistory->add($statusHistory);
            $statusHistory->setReservation($this);
        }

        return $this;
    }

    public function removeStatusHistory(ReservationStatusHistory $statusHistory): static
    {
        if ($this->statusHistory->removeElement($statusHistory)) {
            // set the owning side to null (unless already changed)
            if ($statusHistory->getReservation() === $this) {
                $statusHistory->setReservation(null);
            }
        }

        return $this;
    }

    // Méthodes utilitaires pour le workflow

    public function isConfirmable(): bool
    {
        return $this->getStatusEnum()->isConfirmable();
    }

    public function isCancellable(): bool
    {
        return $this->getStatusEnum()->isCancellable();
    }

    public function isConfirmed(): bool
    {
        return $this->getStatusEnum() === ReservationStatus::CONFIRMEE;
    }

    public function isCancelled(): bool
    {
        return $this->getStatusEnum() === ReservationStatus::ANNULEE;
    }

    public function isPending(): bool
    {
        $status = $this->getStatusEnum();
        return $status === ReservationStatus::EN_ATTENTE || $status === ReservationStatus::EN_ATTENTE_STOCK;
    }
}
