<?php

namespace App\Entity;

use App\Repository\MesureRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups as Group;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: MesureRepository::class)]
class Mesure
{
    use TraitEntity;
    const ETAT = [
        'EN_COURS' => 'En cours',
        'TERMINEE' => 'Terminée',
        'LIVRER' => 'Livrée',
    ];


    use TraitEntity;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
     #[Groups(["group1", "group_type"])]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
     #[Groups(["group1", "group_type"])]
    private ?string $nom = null;

    #[ORM\Column(length: 255, nullable: true)]
     #[Groups(["group1", "group_type"])]
    private ?string $montant = null;

    #[ORM\Column(length: 255, nullable: true)]
     #[Groups(["group1", "group_type"])]
    private ?string $remise = null;

    #[ORM\ManyToOne(inversedBy: 'mesures')]
    private ?Facture $facture = null;

    #[ORM\Column(length: 255, nullable: true)]
     #[Groups(["group1", "group_type"])]
    private ?string $etat = null;

    /**
     * @var Collection<int, LigneMesure>
     */
    #[ORM\OneToMany(targetEntity: LigneMesure::class, mappedBy: 'mesure')]
     #[Groups(["group1", "group_type"])]
    private Collection $ligneMesures;

   

    #[ORM\ManyToOne(cascade: ["persist"], fetch: "EAGER")]
    #[ORM\JoinColumn(nullable: true)]
    #[Group(["fichier", "group1"])]
    private ?Fichier $photoModele = null;


    #[ORM\ManyToOne(cascade: ["persist"], fetch: "EAGER")]
    #[ORM\JoinColumn(nullable: true)]
    #[Group(["fichier", "group1"])]
    private ?Fichier $photoPagne = null;

    #[ORM\ManyToOne(inversedBy: 'mesures')]
    #[Group(["group1"])]
    private ?TypeMesure $typeMesure = null;



    public function __construct()
    {
        $this->ligneMesures = new ArrayCollection();
        $this->etat = self::ETAT['EN_COURS'];
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(?string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getMontant(): ?string
    {
        return $this->montant;
    }

    public function setMontant(?string $montant): static
    {
        $this->montant = $montant;

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

    public function getFacture(): ?Facture
    {
        return $this->facture;
    }

    public function setFacture(?Facture $facture): static
    {
        $this->facture = $facture;

        return $this;
    }

    public function getEtat(): ?string
    {
        return $this->etat;
    }

    public function setEtat(?string $etat): static
    {
        $this->etat = $etat;

        return $this;
    }

    /**
     * @return Collection<int, LigneMesure>
     */
    public function getLigneMesures(): Collection
    {
        return $this->ligneMesures;
    }

    public function addLigneMesure(LigneMesure $ligneMesure): static
    {
        if (!$this->ligneMesures->contains($ligneMesure)) {
            $this->ligneMesures->add($ligneMesure);
            $ligneMesure->setMesure($this);
        }

        return $this;
    }

    public function removeLigneMesure(LigneMesure $ligneMesure): static
    {
        if ($this->ligneMesures->removeElement($ligneMesure)) {
            // set the owning side to null (unless already changed)
            if ($ligneMesure->getMesure() === $this) {
                $ligneMesure->setMesure(null);
            }
        }

        return $this;
    }

    public function getPhotoModele(): ?Fichier
    {
        return $this->photoModele;
    }

    public function setPhotoModele(?Fichier $photoModele): static
    {
        $this->photoModele = $photoModele;

        return $this;
    }

    public function getPhotoPagne(): ?Fichier
    {
        return $this->photoPagne;
    }

    public function setPhotoPagne(?Fichier $photoPagne): static
    {
        $this->photoPagne = $photoPagne;

        return $this;
    }

    public function getTypeMesure(): ?TypeMesure
    {
        return $this->typeMesure;
    }

    public function setTypeMesure(?TypeMesure $typeMesure): static
    {
        $this->typeMesure = $typeMesure;

        return $this;
    }


}
