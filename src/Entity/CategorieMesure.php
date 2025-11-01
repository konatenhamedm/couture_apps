<?php

namespace App\Entity;

use App\Repository\CategorieMesureRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: CategorieMesureRepository::class)]
class CategorieMesure
{
    // longueur, largeur, hauteur
    use TraitEntity;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["group1", "group_type"])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(["group1", "group_type"])]
    private ?string $libelle = null;

    #[ORM\ManyToOne(inversedBy: 'categorieMesures')]
    #[Groups(["group1", "group_type"])]
    private ?Entreprise $entreprise = null;

    /**
     * @var Collection<int, LigneMesure>
     */
    #[ORM\OneToMany(targetEntity: LigneMesure::class, mappedBy: 'categorieMesure')]
    private Collection $ligneMesures;

    /**
     * @var Collection<int, CategorieTypeMesure>
     */
    #[ORM\OneToMany(targetEntity: CategorieTypeMesure::class, mappedBy: 'categorieMesure')]
    private Collection $categorieTypeMesures;

    public function __construct()
    {
        $this->ligneMesures = new ArrayCollection();
        $this->categorieTypeMesures = new ArrayCollection();
    }

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
            $ligneMesure->setCategorieMesure($this);
        }

        return $this;
    }

    public function removeLigneMesure(LigneMesure $ligneMesure): static
    {
        if ($this->ligneMesures->removeElement($ligneMesure)) {
            // set the owning side to null (unless already changed)
            if ($ligneMesure->getCategorieMesure() === $this) {
                $ligneMesure->setCategorieMesure(null);
            }
        }

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
            $categorieTypeMesure->setCategorieMesure($this);
        }

        return $this;
    }

    public function removeCategorieTypeMesure(CategorieTypeMesure $categorieTypeMesure): static
    {
        if ($this->categorieTypeMesures->removeElement($categorieTypeMesure)) {
            // set the owning side to null (unless already changed)
            if ($categorieTypeMesure->getCategorieMesure() === $this) {
                $categorieTypeMesure->setCategorieMesure(null);
            }
        }

        return $this;
    }
}
