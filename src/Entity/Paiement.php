<?php

namespace App\Entity;

use App\Repository\PaiementRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: PaiementRepository::class)]

#[ORM\InheritanceType("JOINED")]
#[ORM\DiscriminatorColumn(name:"discr", type:"string")]
#[UniqueEntity(fields: 'reference', message: 'Ce code est déjà associé à un autre paiement.')]
#[ORM\Table(name:'paiement')]
class Paiement
{
    const TYPE = [
        "paiementSuccursale" => "paiementSuccursale",
        "paiementBoutique" => "paiementBoutique",
        "paiementAbonnement" => "paiementAbonnement",
        'paiementFacture' => 'paiementFacture',
        'paiementReservation' => 'paiementReservation',
    ];
    use TraitEntity;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
     #[Groups(["group1", "group_type","group_details","paiement_boutique"])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
     #[Groups(["group1", "group_type","group_details","paiement_boutique"])]
    private ?string $montant = null;

    #[ORM\Column(length: 255)]
     #[Groups(["group1", "group_type","group_details","paiement_boutique"])]
    private ?string $reference = null;

    #[ORM\Column(length: 255, nullable: true)]
     #[Groups(["group1", "group_type","group_details","paiement_boutique"])]
    private ?string $type = null;



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

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(string $reference): static
    {
        $this->reference = $reference;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): static
    {
        $this->type = $type;

        return $this;
    }

}
