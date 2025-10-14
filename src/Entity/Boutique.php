<?php

namespace App\Entity;

use App\Repository\BoutiqueRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: BoutiqueRepository::class)]
class Boutique
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
    private ?string $contact = null;

    #[ORM\Column(length: 255)]
     #[Groups(["group1", "group_type"])]
    private ?string $situation = null;

    #[ORM\Column]
     #[Groups(["group1", "group_type"])]
    private ?bool $isActive = null;

    #[ORM\ManyToOne(inversedBy: 'boutiques')]
    private ?Entreprise $entreprise = null;

    /**
     * @var Collection<int, User>
     */
    #[ORM\OneToMany(targetEntity: User::class, mappedBy: 'boutique')]
    private Collection $users;

    /**
     * @var Collection<int, ModeleBoutique>
     */
    #[ORM\OneToMany(targetEntity: ModeleBoutique::class, mappedBy: 'boutique')]
    private Collection $modeleBoutiques;

    /**
     * @var Collection<int, PaiementBoutique>
     */
    #[ORM\OneToMany(targetEntity: PaiementBoutique::class, mappedBy: 'boutique')]
    private Collection $paiementBoutiques;

    /**
     * @var Collection<int, CaisseBoutique>
     */
    #[ORM\OneToMany(targetEntity: CaisseBoutique::class, mappedBy: 'boutique')]
    private Collection $caisseBoutiques;

    /**
     * @var Collection<int, Reservation>
     */
    #[ORM\OneToMany(targetEntity: Reservation::class, mappedBy: 'boutique')]
    private Collection $reservations;

    /**
     * @var Collection<int, EntreStock>
     */
    #[ORM\OneToMany(targetEntity: EntreStock::class, mappedBy: 'boutique')]
    private Collection $entreStocks;

    /**
     * @var Collection<int, Client>
     */
    #[ORM\OneToMany(targetEntity: Client::class, mappedBy: 'boutique')]
    private Collection $clients;

    public function __construct()
    {
        $this->users = new ArrayCollection();
        $this->modeleBoutiques = new ArrayCollection();
        $this->paiementBoutiques = new ArrayCollection();
        $this->caisseBoutiques = new ArrayCollection();
        $this->reservations = new ArrayCollection();
        $this->entreStocks = new ArrayCollection();
        $this->clients = new ArrayCollection();
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

    public function setContact(string $contact): static
    {
        $this->contact = $contact;

        return $this;
    }

    public function getSituation(): ?string
    {
        return $this->situation;
    }

    public function setSituation(string $situation): static
    {
        $this->situation = $situation;

        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $actif): static
    {
        $this->isActive = $actif;

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
            $user->setBoutique($this);
        }

        return $this;
    }

    public function removeUser(User $user): static
    {
        if ($this->users->removeElement($user)) {
            // set the owning side to null (unless already changed)
            if ($user->getBoutique() === $this) {
                $user->setBoutique(null);
            }
        }

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
            $modeleBoutique->setBoutique($this);
        }

        return $this;
    }

    public function removeModeleBoutique(ModeleBoutique $modeleBoutique): static
    {
        if ($this->modeleBoutiques->removeElement($modeleBoutique)) {
            // set the owning side to null (unless already changed)
            if ($modeleBoutique->getBoutique() === $this) {
                $modeleBoutique->setBoutique(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, PaiementBoutique>
     */
    public function getPaiementBoutiques(): Collection
    {
        return $this->paiementBoutiques;
    }

    public function addPaiementBoutique(PaiementBoutique $paiementBoutique): static
    {
        if (!$this->paiementBoutiques->contains($paiementBoutique)) {
            $this->paiementBoutiques->add($paiementBoutique);
            $paiementBoutique->setBoutique($this);
        }

        return $this;
    }

    public function removePaiementBoutique(PaiementBoutique $paiementBoutique): static
    {
        if ($this->paiementBoutiques->removeElement($paiementBoutique)) {
            // set the owning side to null (unless already changed)
            if ($paiementBoutique->getBoutique() === $this) {
                $paiementBoutique->setBoutique(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, CaisseBoutique>
     */
    public function getCaisseBoutiques(): Collection
    {
        return $this->caisseBoutiques;
    }

    public function addCaisseBoutique(CaisseBoutique $caisseBoutique): static
    {
        if (!$this->caisseBoutiques->contains($caisseBoutique)) {
            $this->caisseBoutiques->add($caisseBoutique);
            $caisseBoutique->setBoutique($this);
        }

        return $this;
    }

    public function removeCaisseBoutique(CaisseBoutique $caisseBoutique): static
    {
        if ($this->caisseBoutiques->removeElement($caisseBoutique)) {
            // set the owning side to null (unless already changed)
            if ($caisseBoutique->getBoutique() === $this) {
                $caisseBoutique->setBoutique(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Reservation>
     */
    public function getReservations(): Collection
    {
        return $this->reservations;
    }

    public function addReservation(Reservation $reservation): static
    {
        if (!$this->reservations->contains($reservation)) {
            $this->reservations->add($reservation);
            $reservation->setBoutique($this);
        }

        return $this;
    }

    public function removeReservation(Reservation $reservation): static
    {
        if ($this->reservations->removeElement($reservation)) {
            // set the owning side to null (unless already changed)
            if ($reservation->getBoutique() === $this) {
                $reservation->setBoutique(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, EntreStock>
     */
    public function getEntreStocks(): Collection
    {
        return $this->entreStocks;
    }

    public function addEntreStock(EntreStock $entreStock): static
    {
        if (!$this->entreStocks->contains($entreStock)) {
            $this->entreStocks->add($entreStock);
            $entreStock->setBoutique($this);
        }

        return $this;
    }

    public function removeEntreStock(EntreStock $entreStock): static
    {
        if ($this->entreStocks->removeElement($entreStock)) {
            // set the owning side to null (unless already changed)
            if ($entreStock->getBoutique() === $this) {
                $entreStock->setBoutique(null);
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
            $client->setBoutique($this);
        }

        return $this;
    }

    public function removeClient(Client $client): static
    {
        if ($this->clients->removeElement($client)) {
            // set the owning side to null (unless already changed)
            if ($client->getBoutique() === $this) {
                $client->setBoutique(null);
            }
        }

        return $this;
    }
}
