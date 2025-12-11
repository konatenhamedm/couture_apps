<?php

namespace App\Entity;

use App\Repository\PaiementReservationRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: PaiementReservationRepository::class)]
class PaiementReservation extends Paiement
{
    #[ORM\ManyToOne(inversedBy: 'paiementReservations')]
     #[Groups(["paiement_boutique"])]
    private ?Reservation $reservation = null;

    public function getReservation(): ?Reservation
    {
        return $this->reservation;
    }

    public function setReservation(?Reservation $reservation): static
    {
        $this->reservation = $reservation;

        return $this;
    }
}
