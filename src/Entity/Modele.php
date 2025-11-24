<?php

namespace App\Entity;

use App\Repository\ModeleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ModeleRepository::class)]
class Modele
{
    use TraitEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["group1","group_details"])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(["group1","group_details"])]
    private ?string $libelle = null;


    #[ORM\ManyToOne(cascade: ["persist"], fetch: "EAGER")]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(["fichier", "group1"])]
    private ?Fichier $photo = null;


    #[ORM\Column]
    #[Groups(["group1","group_details"])]
    private ?int $quantiteGlobale = null;

    /**
     * @var Collection<int, ModeleBoutique>
     */
    #[ORM\OneToMany(targetEntity: ModeleBoutique::class, mappedBy: 'modele')]
    private Collection $modeleBoutiques;

    #[ORM\ManyToOne(inversedBy: 'modeles')]
    private ?Entreprise $entreprise = null;



    public function __construct()
    {
        $this->modeleBoutiques = new ArrayCollection();
       

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

    public function getPhoto(): ?Fichier
    {
        return $this->photo;
    }

    public function setPhoto(Fichier $photo): static
    {
        $this->photo = $photo;

        return $this;
    }

    public function getQuantiteGlobale(): ?int
    {
        return $this->quantiteGlobale;
    }

    public function setQuantiteGlobale(int $quantiteGlobale): static
    {
        $this->quantiteGlobale = $quantiteGlobale;

        return $this;
    }

    /**
     * @return Collection<int, ModeleBoutique>
     */
    public function getModeleBoutiques(): Collection
    {
        return $this->modeleBoutiques;
    }

    public function addModeleBoutique(ModeleBoutique $modeleBoutique): static
    {
        if (!$this->modeleBoutiques->contains($modeleBoutique)) {
            $this->modeleBoutiques->add($modeleBoutique);
            $modeleBoutique->setModele($this);
        }

        return $this;
    }

    public function removeModeleBoutique(ModeleBoutique $modeleBoutique): static
    {
        if ($this->modeleBoutiques->removeElement($modeleBoutique)) {
            // set the owning side to null (unless already changed)
            if ($modeleBoutique->getModele() === $this) {
                $modeleBoutique->setModele(null);
            }
        }

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
