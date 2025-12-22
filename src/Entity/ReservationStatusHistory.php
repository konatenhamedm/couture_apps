<?php

namespace App\Entity;

use App\Repository\ReservationStatusHistoryRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Entité pour tracer l'historique des changements de statut des réservations
 */
#[ORM\Entity(repositoryClass: ReservationStatusHistoryRepository::class)]
#[ORM\Table(name: 'reservation_status_history')]
class ReservationStatusHistory
{
    use TraitEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["group1", "group_details", "group_reservation", "group_reservation_history"])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'statusHistory')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(["group_reservation_history"])]
    private ?Reservation $reservation = null;

    #[ORM\Column(length: 20)]
    #[Groups(["group1", "group_details", "group_reservation", "group_reservation_history"])]
    private ?string $oldStatus = null;

    #[ORM\Column(length: 20)]
    #[Groups(["group1", "group_details", "group_reservation", "group_reservation_history"])]
    private ?string $newStatus = null;

    #[ORM\Column]
    #[Groups(["group1", "group_details", "group_reservation", "group_reservation_history"])]
    private ?\DateTime $changedAt = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(["group1", "group_details", "group_reservation", "group_reservation_history"])]
    private ?string $reason = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(["group1", "group_details", "group_reservation", "group_reservation_history"])]
    private ?User $changedBy = null;

    public function __construct()
    {
        $this->changedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReservation(): ?Reservation
    {
        return $this->reservation;
    }

    public function setReservation(?Reservation $reservation): static
    {
        $this->reservation = $reservation;

        return $this;
    }

    public function getOldStatus(): ?string
    {
        return $this->oldStatus;
    }

    public function setOldStatus(string $oldStatus): static
    {
        $this->oldStatus = $oldStatus;

        return $this;
    }

    public function getNewStatus(): ?string
    {
        return $this->newStatus;
    }

    public function setNewStatus(string $newStatus): static
    {
        $this->newStatus = $newStatus;

        return $this;
    }

    public function getChangedAt(): ?\DateTime
    {
        return $this->changedAt;
    }

    public function setChangedAt(\DateTime $changedAt): static
    {
        $this->changedAt = $changedAt;

        return $this;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(?string $reason): static
    {
        $this->reason = $reason;

        return $this;
    }

    public function getChangedBy(): ?User
    {
        return $this->changedBy;
    }

    public function setChangedBy(?User $changedBy): static
    {
        $this->changedBy = $changedBy;

        return $this;
    }
}