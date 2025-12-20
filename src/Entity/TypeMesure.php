<?php

namespace App\Entity;

use App\Repository\TypeMesureRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: TypeMesureRepository::class)]
class TypeMesure
{use TraitEntity;

    //exemple veste , pantalon,boubou
    use TraitEntity;
   
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
     #[Groups(["group1", "group_type"])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
     #[Groups(["group1", "group_type"])]
    private ?string $libelle = null;

    #[ORM\ManyToOne(inversedBy: 'typeMesures')]
    #[Groups(["group1", "group_type"])]
    private ?Entreprise $entreprise = null;

    /**
     * @var Collection<int, CategorieTypeMesure>
     */
    #[ORM\OneToMany(targetEntity: CategorieTypeMesure::class, mappedBy: 'typeMesure')]
    private Collection $categorieTypeMesures;

    /**
     * @var Collection<int, Mesure>
     */
    #[ORM\OneToMany(targetEntity: Mesure::class, mappedBy: 'typeMesure')]
    private Collection $mesures;

    public function __construct()
    {
        $this->categorieTypeMesures = new ArrayCollection();
        $this->mesures = new ArrayCollection();
    
        // Initialiser les valeurs par défaut du trait
        $this->initializeTraitDefaults();}

    public function getId(): ?int
    {
        return $this->id;
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

    public function getEntreprise(): ?Entreprise
    {
        return $this->entreprise;
    }

    public function setEntreprise(?Entreprise $entreprise): static
    {
        $this->entreprise = $entreprise;

        return $this;
    }

    /**
     * @return Collection<int, CategorieTypeMesure>
     */
    public function getCategorieTypeMesures(): Collection
    {
        return $this->categorieTypeMesures;
    }

    public function addCategorieTypeMesure(CategorieTypeMesure $categorieTypeMesure): static
    {
        if (!$this->categorieTypeMesures->contains($categorieTypeMesure)) {
            $this->categorieTypeMesures->add($categorieTypeMesure);
            $categorieTypeMesure->setTypeMesure($this);
        }

        return $this;
    }

    public function removeCategorieTypeMesure(CategorieTypeMesure $categorieTypeMesure): static
    {
        if ($this->categorieTypeMesures->removeElement($categorieTypeMesure)) {
            // set the owning side to null (unless already changed)
            if ($categorieTypeMesure->getTypeMesure() === $this) {
                $categorieTypeMesure->setTypeMesure(null);
            }
        }

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
            $mesure->setTypeMesure($this);
        }

        return $this;
    }

    public function removeMesure(Mesure $mesure): static
    {
        if ($this->mesures->removeElement($mesure)) {
            // set the owning side to null (unless already changed)
            if ($mesure->getTypeMesure() === $this) {
                $mesure->setTypeMesure(null);
            }
        }

        return $this;
    }
}
