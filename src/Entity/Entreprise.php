<?php

namespace App\Entity;

use App\Repository\EntrepriseRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups as Group;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: EntrepriseRepository::class)]
class Entreprise
{use TraitEntity;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
     #[Groups(["group1"])]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
     #[Groups(["group1"])]
    private ?string $libelle = null;

    #[ORM\Column(length: 255, nullable: true)]
     #[Groups(["group1"])]
    private ?string $numero = null;


    #[ORM\ManyToOne(cascade: ["persist"], fetch: "EAGER")]
    #[ORM\JoinColumn(nullable: true)]
    #[Group(["fichier", "group1"])]
    private ?Fichier $logo = null;


    #[ORM\Column(length: 255, nullable: true)]
     #[Groups(["group1"])]
    private ?string $email = null;

    /**
     * @var Collection<int, CategorieMesure>
     */
    #[ORM\OneToMany(targetEntity: CategorieMesure::class, mappedBy: 'entreprise')]
    private Collection $categorieMesures;

    

    /**
     * @var Collection<int, Notification>
     */
    #[ORM\OneToMany(targetEntity: Notification::class, mappedBy: 'entreprise')]
    private Collection $notifications;

    /**
     * @var Collection<int, Abonnement>
     */
    #[ORM\OneToMany(targetEntity: Abonnement::class, mappedBy: 'entreprise')]
    private Collection $abonnements;

    /**
     * @var Collection<int, TypeMesure>
     */
    #[ORM\OneToMany(targetEntity: TypeMesure::class, mappedBy: 'entreprise')]
    private Collection $typeMesures;

    /**
     * @var Collection<int, User>
     */
    #[ORM\OneToMany(targetEntity: User::class, mappedBy: 'entreprise')]
    private Collection $users;

    /**
     * @var Collection<int, Surccursale>
     */
    #[ORM\OneToMany(targetEntity: Surccursale::class, mappedBy: 'entreprise')]
    private Collection $surccursales;

    /**
     * @var Collection<int, Setting>
     */
    #[ORM\OneToMany(targetEntity: Setting::class, mappedBy: 'entreprise')]
    private Collection $settings;

    /**
     * @var Collection<int, Boutique>
     */
    #[ORM\OneToMany(targetEntity: Boutique::class, mappedBy: 'entreprise')]
    private Collection $boutiques;

    #[ORM\ManyToOne(inversedBy: 'entreprises', cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?Pays $pays = null;

    /**
     * @var Collection<int, PaiementAbonnement>
     */
    #[ORM\OneToMany(targetEntity: PaiementAbonnement::class, mappedBy: 'entreprise')]
    private Collection $paiementAbonnements;

    /**
     * @var Collection<int, Modele>
     */
    #[ORM\OneToMany(targetEntity: Modele::class, mappedBy: 'entreprise')]
    private Collection $modeles;

    /**
     * @var Collection<int, Caisse>
     */
    #[ORM\OneToMany(targetEntity: Caisse::class, mappedBy: 'entreprise')]
    private Collection $caisses;

    /**
     * @var Collection<int, Reservation>
     */
    #[ORM\OneToMany(targetEntity: Reservation::class, mappedBy: 'entreprise')]
    private Collection $reservations;

    /**
     * @var Collection<int, EntreStock>
     */
    #[ORM\OneToMany(targetEntity: EntreStock::class, mappedBy: 'entreprise')]
    private Collection $entreStocks;

    /**
     * @var Collection<int, CategorieTypeMesure>
     */
    #[ORM\OneToMany(targetEntity: CategorieTypeMesure::class, mappedBy: 'entreprise')]
    private Collection $categorieTypeMesures;

    /**
     * @var Collection<int, Facture>
     */
    #[ORM\OneToMany(targetEntity: Facture::class, mappedBy: 'entreprise')]
    private Collection $factures;

    /**
     * @var Collection<int, Client>
     */
    #[ORM\OneToMany(targetEntity: Client::class, mappedBy: 'entreprise')]
    private Collection $clients;

    public function __construct()
    {
        $this->categorieMesures = new ArrayCollection();
        $this->notifications = new ArrayCollection();
        $this->abonnements = new ArrayCollection();
        $this->typeMesures = new ArrayCollection();
        $this->users = new ArrayCollection();
        $this->surccursales = new ArrayCollection();
        $this->settings = new ArrayCollection();
        $this->boutiques = new ArrayCollection();
        $this->paiementAbonnements = new ArrayCollection();
        $this->modeles = new ArrayCollection();
        $this->caisses = new ArrayCollection();
        $this->reservations = new ArrayCollection();
        $this->entreStocks = new ArrayCollection();
        $this->categorieTypeMesures = new ArrayCollection();
        $this->factures = new ArrayCollection();
        $this->clients = new ArrayCollection();
    
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

    public function setLibelle(?string $libelle): static
    {
        $this->libelle = $libelle;

        return $this;
    }

    public function getNumero(): ?string
    {
        return $this->numero;
    }

    public function setNumero(?string $numero): static
    {
        $this->numero = $numero;

        return $this;
    }

    public function getLogo(): ?Fichier
    {
        return $this->logo;
    }

    public function setLogo(?Fichier $logo): static
    {
        $this->logo = $logo;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return Collection<int, CategorieMesure>
     */
    public function getCategorieMesures(): Collection
    {
        return $this->categorieMesures;
    }

    public function addCategorieMesure(CategorieMesure $categorieMesure): static
    {
        if (!$this->categorieMesures->contains($categorieMesure)) {
            $this->categorieMesures->add($categorieMesure);
            $categorieMesure->setEntreprise($this);
        }

        return $this;
    }

    public function removeCategorieMesure(CategorieMesure $categorieMesure): static
    {
        if ($this->categorieMesures->removeElement($categorieMesure)) {
            // set the owning side to null (unless already changed)
            if ($categorieMesure->getEntreprise() === $this) {
                $categorieMesure->setEntreprise(null);
            }
        }

        return $this;
    }

    

    /**
     * @return Collection<int, Notification>
     */
    public function getNotifications(): Collection
    {
        return $this->notifications;
    }

    public function addNotification(Notification $notification): static
    {
        if (!$this->notifications->contains($notification)) {
            $this->notifications->add($notification);
            $notification->setEntreprise($this);
        }

        return $this;
    }

    public function removeNotification(Notification $notification): static
    {
        if ($this->notifications->removeElement($notification)) {
            // set the owning side to null (unless already changed)
            if ($notification->getEntreprise() === $this) {
                $notification->setEntreprise(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Abonnement>
     */
    public function getAbonnements(): Collection
    {
        return $this->abonnements;
    }

    public function addAbonnement(Abonnement $abonnement): static
    {
        if (!$this->abonnements->contains($abonnement)) {
            $this->abonnements->add($abonnement);
            $abonnement->setEntreprise($this);
        }

        return $this;
    }

    public function removeAbonnement(Abonnement $abonnement): static
    {
        if ($this->abonnements->removeElement($abonnement)) {
            // set the owning side to null (unless already changed)
            if ($abonnement->getEntreprise() === $this) {
                $abonnement->setEntreprise(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, TypeMesure>
     */
    public function getTypeMesures(): Collection
    {
        return $this->typeMesures;
    }

    public function addTypeMesure(TypeMesure $typeMesure): static
    {
        if (!$this->typeMesures->contains($typeMesure)) {
            $this->typeMesures->add($typeMesure);
            $typeMesure->setEntreprise($this);
        }

        return $this;
    }

    public function removeTypeMesure(TypeMesure $typeMesure): static
    {
        if ($this->typeMesures->removeElement($typeMesure)) {
            // set the owning side to null (unless already changed)
            if ($typeMesure->getEntreprise() === $this) {
                $typeMesure->setEntreprise(null);
            }
        }

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
            $user->setEntreprise($this);
        }

        return $this;
    }

    public function removeUser(User $user): static
    {
        if ($this->users->removeElement($user)) {
            // set the owning side to null (unless already changed)
            if ($user->getEntreprise() === $this) {
                $user->setEntreprise(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Surccursale>
     */
    public function getSurccursales(): Collection
    {
        return $this->surccursales;
    }

    public function addSurccursale(Surccursale $surccursale): static
    {
        if (!$this->surccursales->contains($surccursale)) {
            $this->surccursales->add($surccursale);
            $surccursale->setEntreprise($this);
        }

        return $this;
    }

    public function removeSurccursale(Surccursale $surccursale): static
    {
        if ($this->surccursales->removeElement($surccursale)) {
            // set the owning side to null (unless already changed)
            if ($surccursale->getEntreprise() === $this) {
                $surccursale->setEntreprise(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Setting>
     */
    public function getSettings(): Collection
    {
        return $this->settings;
    }

    public function addSetting(Setting $setting): static
    {
        if (!$this->settings->contains($setting)) {
            $this->settings->add($setting);
            $setting->setEntreprise($this);
        }

        return $this;
    }

    public function removeSetting(Setting $setting): static
    {
        if ($this->settings->removeElement($setting)) {
            // set the owning side to null (unless already changed)
            if ($setting->getEntreprise() === $this) {
                $setting->setEntreprise(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Boutique>
     */
    public function getBoutiques(): Collection
    {
        return $this->boutiques;
    }

    public function addBoutique(Boutique $boutique): static
    {
        if (!$this->boutiques->contains($boutique)) {
            $this->boutiques->add($boutique);
            $boutique->setEntreprise($this);
        }

        return $this;
    }

    public function removeBoutique(Boutique $boutique): static
    {
        if ($this->boutiques->removeElement($boutique)) {
            // set the owning side to null (unless already changed)
            if ($boutique->getEntreprise() === $this) {
                $boutique->setEntreprise(null);
            }
        }

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

    /**
     * @return Collection<int, PaiementAbonnement>
     */
    public function getPaiementAbonnements(): Collection
    {
        return $this->paiementAbonnements;
    }

    public function addPaiementAbonnement(PaiementAbonnement $paiementAbonnement): static
    {
        if (!$this->paiementAbonnements->contains($paiementAbonnement)) {
            $this->paiementAbonnements->add($paiementAbonnement);
            $paiementAbonnement->setEntreprise($this);
        }

        return $this;
    }

    public function removePaiementAbonnement(PaiementAbonnement $paiementAbonnement): static
    {
        if ($this->paiementAbonnements->removeElement($paiementAbonnement)) {
            // set the owning side to null (unless already changed)
            if ($paiementAbonnement->getEntreprise() === $this) {
                $paiementAbonnement->setEntreprise(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Modele>
     */
    public function getModeles(): Collection
    {
        return $this->modeles;
    }

    public function addModele(Modele $modele): static
    {
        if (!$this->modeles->contains($modele)) {
            $this->modeles->add($modele);
            $modele->setEntreprise($this);
        }

        return $this;
    }

    public function removeModele(Modele $modele): static
    {
        if ($this->modeles->removeElement($modele)) {
            // set the owning side to null (unless already changed)
            if ($modele->getEntreprise() === $this) {
                $modele->setEntreprise(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Caisse>
     */
    public function getCaisses(): Collection
    {
        return $this->caisses;
    }

    public function addCaiss(Caisse $caiss): static
    {
        if (!$this->caisses->contains($caiss)) {
            $this->caisses->add($caiss);
            $caiss->setEntreprise($this);
        }

        return $this;
    }

    public function removeCaiss(Caisse $caiss): static
    {
        if ($this->caisses->removeElement($caiss)) {
            // set the owning side to null (unless already changed)
            if ($caiss->getEntreprise() === $this) {
                $caiss->setEntreprise(null);
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
            $reservation->setEntreprise($this);
        }

        return $this;
    }

    public function removeReservation(Reservation $reservation): static
    {
        if ($this->reservations->removeElement($reservation)) {
            // set the owning side to null (unless already changed)
            if ($reservation->getEntreprise() === $this) {
                $reservation->setEntreprise(null);
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
            $entreStock->setEntreprise($this);
        }

        return $this;
    }

    public function removeEntreStock(EntreStock $entreStock): static
    {
        if ($this->entreStocks->removeElement($entreStock)) {
            // set the owning side to null (unless already changed)
            if ($entreStock->getEntreprise() === $this) {
                $entreStock->setEntreprise(null);
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
            $categorieTypeMesure->setEntreprise($this);
        }

        return $this;
    }

    public function removeCategorieTypeMesure(CategorieTypeMesure $categorieTypeMesure): static
    {
        if ($this->categorieTypeMesures->removeElement($categorieTypeMesure)) {
            // set the owning side to null (unless already changed)
            if ($categorieTypeMesure->getEntreprise() === $this) {
                $categorieTypeMesure->setEntreprise(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Facture>
     */
    public function getFactures(): Collection
    {
        return $this->factures;
    }

    public function addFacture(Facture $facture): static
    {
        if (!$this->factures->contains($facture)) {
            $this->factures->add($facture);
            $facture->setEntreprise($this);
        }

        return $this;
    }

    public function removeFacture(Facture $facture): static
    {
        if ($this->factures->removeElement($facture)) {
            // set the owning side to null (unless already changed)
            if ($facture->getEntreprise() === $this) {
                $facture->setEntreprise(null);
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
            $client->setEntreprise($this);
        }

        return $this;
    }

    public function removeClient(Client $client): static
    {
        if ($this->clients->removeElement($client)) {
            // set the owning side to null (unless already changed)
            if ($client->getEntreprise() === $this) {
                $client->setEntreprise(null);
            }
        }

        return $this;
    }
}
