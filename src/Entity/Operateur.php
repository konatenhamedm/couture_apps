<?php

namespace App\Entity;

use App\Repository\OperateurRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: OperateurRepository::class)]
class Operateur
{
    use TraitEntity;
   
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["group1"])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(["group1"])]
    private ?string $code = null;

    #[ORM\Column(length: 255)]
    #[Groups(["group1"])]
    private ?string $libelle = null;

    #[ORM\ManyToOne(inversedBy: 'operateurs', cascade: ['persist'])]
    private ?Pays $pays = null;

    #[ORM\Column]
    private ?bool $actif = null;
    #[ORM\ManyToOne(cascade: ["persist"], fetch: "EAGER")]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(["fichier", "group1"])]
    private ?Fichier $photo = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): static
    {
        $this->libelle = $libelle;

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

    public function isActif(): ?bool
    {
        return $this->actif;
    }

    public function setActif(bool $actif): static
    {
        $this->actif = $actif;

        return $this;
    }

    public function getPhoto(): ?Fichier
    {
        return $this->photo;
    }

    public function setPhoto(Fichier $photo): static
    {
        $this->photo = $photo;

        return $this;
    }

    public function __construct()
    {
        // Initialiser les valeurs par défaut du trait
        $this->initializeTraitDefaults();
    }
}
