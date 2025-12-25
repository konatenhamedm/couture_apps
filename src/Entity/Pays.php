<?php

namespace App\Entity;

use App\Repository\PaysRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: PaysRepository::class)]
class Pays
{
    use TraitEntity;
   
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
     #[Groups(["group1", "group_type"])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
     #[Groups(["group1", "group_type"])]
    private ?string $libelle = null;

    #[ORM\Column(length: 255)]
     #[Groups(["group1", "group_type"])]
    private ?string $code = null;

    #[ORM\Column(length: 255)]
     #[Groups(["group1", "group_type"])]
    private ?string $indicatif = null;

    /**
     * @var Collection<int, Operateur>
     */
    #[ORM\OneToMany(targetEntity: Operateur::class, mappedBy: 'pays')]
    private Collection $operateurs;

    #[ORM\Column]
    private ?bool $actif = null;

    /**
     * @var Collection<int, Entreprise>
     */
    #[ORM\OneToMany(targetEntity: Entreprise::class, mappedBy: 'pays')]
    private Collection $entreprises;

    /**
     * @var Collection<int, ModuleAbonnement>
     */
    #[ORM\OneToMany(targetEntity: ModuleAbonnement::class, mappedBy: 'pays')]
    private Collection $moduleAbonnements;

    public function __construct()
    {
        $this->operateurs = new ArrayCollection();
        $this->entreprises = new ArrayCollection();
        $this->moduleAbonnements = new ArrayCollection();
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

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function getIndicatif(): ?string
    {
        return $this->indicatif;
    }

    public function setIndicatif(string $indicatif): static
    {
        $this->indicatif = $indicatif;

        return $this;
    }

    /**
     * @return Collection<int, Operateur>
     */
    public function getOperateurs(): Collection
    {
        return $this->operateurs;
    }

    public function addOperateur(Operateur $operateur): static
    {
        if (!$this->operateurs->contains($operateur)) {
            $this->operateurs->add($operateur);
            $operateur->setPays($this);
        }

        return $this;
    }

    public function removeOperateur(Operateur $operateur): static
    {
        if ($this->operateurs->removeElement($operateur)) {
            // set the owning side to null (unless already changed)
            if ($operateur->getPays() === $this) {
                $operateur->setPays(null);
            }
        }

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

    /**
     * @return Collection<int, Entreprise>
     */
    public function getEntreprises(): Collection
    {
        return $this->entreprises;
    }

    public function addEntreprise(Entreprise $entreprise): static
    {
        if (!$this->entreprises->contains($entreprise)) {
            $this->entreprises->add($entreprise);
            $entreprise->setPays($this);
        }

        return $this;
    }

    public function removeEntreprise(Entreprise $entreprise): static
    {
        if ($this->entreprises->removeElement($entreprise)) {
            // set the owning side to null (unless already changed)
            if ($entreprise->getPays() === $this) {
                $entreprise->setPays(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ModuleAbonnement>
     */
    public function getModuleAbonnements(): Collection
    {
        return $this->moduleAbonnements;
    }

    public function addModuleAbonnement(ModuleAbonnement $moduleAbonnement): static
    {
        if (!$this->moduleAbonnements->contains($moduleAbonnement)) {
            $this->moduleAbonnements->add($moduleAbonnement);
            $moduleAbonnement->setPays($this);
        }

        return $this;
    }

    public function removeModuleAbonnement(ModuleAbonnement $moduleAbonnement): static
    {
        if ($this->moduleAbonnements->removeElement($moduleAbonnement)) {
            // set the owning side to null (unless already changed)
            if ($moduleAbonnement->getPays() === $this) {
                $moduleAbonnement->setPays(null);
            }
        }

        return $this;
    }
}
