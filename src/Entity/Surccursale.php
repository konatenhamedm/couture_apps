<?php

namespace App\Entity;

use App\Repository\SurccursaleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: SurccursaleRepository::class)]
class Surccursale
{use TraitEntity;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
     #[Groups(["group1", "group_type"])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
     #[Groups(["group1", "group_type"])]
    private ?string $libelle = null;

    #[ORM\Column(length: 255, nullable: true)]
     #[Groups(["group1", "group_type"])]
    private ?string $contact = null;

    /**
     * @var Collection<int, User>
     */
    #[ORM\OneToMany(targetEntity: User::class, mappedBy: 'surccursale')]
    private Collection $users;

    /**
     * @var Collection<int, Client>
     */
    #[ORM\OneToMany(targetEntity: Client::class, mappedBy: 'surccursale')]
    private Collection $clients;

    #[ORM\ManyToOne(inversedBy: 'surccursales')]
    private ?Entreprise $entreprise = null;

    #[ORM\Column(nullable: true)]
     #[Groups(["group1", "group_type"])]
    private ?bool $isActive = true;

    /**
     * @var Collection<int, CaisseSuccursale>
     */
    #[ORM\OneToMany(targetEntity: CaisseSuccursale::class, mappedBy: 'succursale')]
    private Collection $caisseSuccursales;

    public function __construct()
    {
        $this->users = new ArrayCollection();
        $this->clients = new ArrayCollection();
        $this->caisseSuccursales = new ArrayCollection();
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

    public function getContact(): ?string
    {
        return $this->contact;
    }

    public function setContact(?string $contact): static
    {
        $this->contact = $contact;

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): static
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
            $user->setSurccursale($this);
        }

        return $this;
    }

    public function removeUser(User $user): static
    {
        if ($this->users->removeElement($user)) {
            // set the owning side to null (unless already changed)
            if ($user->getSurccursale() === $this) {
                $user->setSurccursale(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Client>
     */
    public function getClients(): Collection
    {
        return $this->clients;
    }

    public function addClient(Client $client): static
    {
        if (!$this->clients->contains($client)) {
            $this->clients->add($client);
            $client->setSurccursale($this);
        }

        return $this;
    }

    public function removeClient(Client $client): static
    {
        if ($this->clients->removeElement($client)) {
            // set the owning side to null (unless already changed)
            if ($client->getSurccursale() === $this) {
                $client->setSurccursale(null);
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

    public function isActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(?bool $active): static
    {
        $this->isActive = $active;

        return $this;
    }

    /**
     * @return Collection<int, CaisseSuccursale>
     */
    public function getCaisseSuccursales(): Collection
    {
        return $this->caisseSuccursales;
    }

    public function addCaisseSuccursale(CaisseSuccursale $caisseSuccursale): static
    {
        if (!$this->caisseSuccursales->contains($caisseSuccursale)) {
            $this->caisseSuccursales->add($caisseSuccursale);
            $caisseSuccursale->setSuccursale($this);
        }

        return $this;
    }

    public function removeCaisseSuccursale(CaisseSuccursale $caisseSuccursale): static
    {
        if ($this->caisseSuccursales->removeElement($caisseSuccursale)) {
            // set the owning side to null (unless already changed)
            if ($caisseSuccursale->getSuccursale() === $this) {
                $caisseSuccursale->setSuccursale(null);
            }
        }

        return $this;
    }
}
