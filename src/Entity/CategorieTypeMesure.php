<?php

namespace App\Entity;

use App\Repository\CategorieTypeMesureRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: CategorieTypeMesureRepository::class)]
class CategorieTypeMesure
{
    use TraitEntity;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
     #[Groups(["group1", "group_type"])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'categorieTypeMesures')]
    private ?TypeMesure $typeMesure = null;

    #[ORM\ManyToOne(inversedBy: 'categorieTypeMesures')]
     #[Groups(["group1", "group_type"])]
    private ?CategorieMesure $categorieMesure = null;

    #[ORM\ManyToOne(inversedBy: 'categorieTypeMesures')]
    private ?Entreprise $entreprise = null;

    

    public function getId(): ?int
    {
        return $this->id;
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

    public function getCategorieMesure(): ?CategorieMesure
    {
        return $this->categorieMesure;
    }

    public function setCategorieMesure(?CategorieMesure $categorieMesure): static
    {
        $this->categorieMesure = $categorieMesure;

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
