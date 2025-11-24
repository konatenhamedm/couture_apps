<?php

namespace App\Entity;

use App\Repository\LigneEntreRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: LigneEntreRepository::class)]
class LigneEntre
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
     #[Groups(["group1", "group_type","group_ligne","group_details"])]
    private ?int $id = null;



    #[ORM\Column]
     #[Groups(["group1", "group_type","group_ligne","group_details"])]
    private ?int $quantite = null;

    #[ORM\ManyToOne(inversedBy: 'ligneEntres')]
     #[Groups(["group1", "group_type","group_ligne"])]
    private ?ModeleBoutique $modele = null;

    #[ORM\ManyToOne(inversedBy: 'ligneEntres',cascade: ['persist'])]
    #[Groups(["group_ligne" ,"group_details"])]
    private ?EntreStock $entreStock = null;

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

    public function getModele(): ?ModeleBoutique
    {
        return $this->modele;
    }

    public function setModele(?ModeleBoutique $modele): static
    {
        $this->modele = $modele;

        return $this;
    }

    public function getEntreStock(): ?EntreStock
    {
        return $this->entreStock;
    }

    public function setEntreStock(?EntreStock $entreStock): static
    {
        $this->entreStock = $entreStock;

        return $this;
    }
}
