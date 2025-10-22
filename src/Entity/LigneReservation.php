<?php

namespace App\Entity;

use App\Repository\LigneReservationRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: LigneReservationRepository::class)]
class LigneReservation
{

    use TraitEntity;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
     #[Groups(["group1"])]
    private ?int $id = null;



    #[ORM\Column]
     #[Groups(["group1"])]
    private ?int $quantite = null;

    #[ORM\ManyToOne(inversedBy: 'ligneReservations')]
    private ?Reservation $reservation = null;

    #[ORM\ManyToOne(inversedBy: 'ligneReservations')]
    private ?ModeleBoutique $modele = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $avanceModele = null;

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

    public function getReservation(): ?Reservation
    {
        return $this->reservation;
    }

    public function setReservation(?Reservation $reservation): static
    {
        $this->reservation = $reservation;

        return $this;
    }

    public function getModele(): ?ModeleBoutique
    {
        return $this->modele;
    }

    public function setModele(?ModeleBoutique $modele): static
    {
        $this->modele = $modele;

        return $this;
    }

    public function getAvanceModele(): ?string
    {
        return $this->avanceModele;
    }

    public function setAvanceModele(?string $avanceModele): static
    {
        $this->avanceModele = $avanceModele;

        return $this;
    }
}
