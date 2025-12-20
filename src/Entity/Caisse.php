<?php

namespace App\Entity;

use App\Repository\CaisseRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: CaisseRepository::class)]
#[ORM\Table(name: 'caisse')]
#[ORM\InheritanceType("JOINED")]
#[ORM\DiscriminatorColumn(name:"discr", type:"string")]
#[UniqueEntity(fields: 'reference', message: 'Ce code est déjà associé à un autre paiement.')]
class Caisse
{

    use TraitEntity;

    const TYPE = [
        'boutique' => 'boutique',
        'succursale' => 'succursale'
    ];
    
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["group1"])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(["group1"])]
    private ?string $montant = null;

    #[ORM\Column(length: 255)]
    #[Groups(["group1"])]
    private ?string $reference = null;

    #[ORM\Column(length: 255)]
    #[Groups(["group1"])]
    private ?string $type = null;

    #[ORM\ManyToOne(inversedBy: 'caisses')]
    private ?Entreprise $entreprise = null;

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

    public function setType(string $type): static
    {
        $this->type = $type;

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
}
