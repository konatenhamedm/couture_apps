<?php

namespace App\Entity;

use App\Repository\ClientRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ClientRepository::class)]
#[UniqueEntity(fields: 'numero', message: 'Cet client avec ce numero existe deja')]
class Client
{ use TraitEntity;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
     #[Groups(["group1", "group_type","group_details","group_reservation","group_modeleBoutique","paiement_boutique"])]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true,unique:true)]
     #[Groups(["group1", "group_type","group_details","group_reservation","group_modeleBoutique","paiement_boutique"])]
    private ?string $numero = null;

    #[ORM\Column(length: 255, nullable: true)]
     #[Groups(["group1", "group_type","group_details","group_reservation","group_modeleBoutique","paiement_boutique"])]
    private ?string $nom = null;

    #[ORM\Column(length: 255, nullable: true)]
     #[Groups(["group1", "group_type","group_details","group_reservation","group_modeleBoutique","paiement_boutique"])]
    private ?string $prenom = null;



    /**
     * @var Collection<int, Facture>
     */
    #[ORM\OneToMany(targetEntity: Facture::class, mappedBy: 'client')]
    private Collection $factures;

    #[ORM\ManyToOne(inversedBy: 'clients')]
     #[Groups(["group1", "group_type"])]
    private ?Surccursale $surccursale = null;



     #[ORM\ManyToOne(cascade: ["persist"], fetch: "EAGER")]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(["fichier", "group1","paiement_boutique"])]
    private ?Fichier $photo = null;

     /**
      * @var Collection<int, Reservation>
      */
     #[ORM\OneToMany(targetEntity: Reservation::class, mappedBy: 'client')]
     private Collection $reservations;


     /**
      * @var Collection<int, self>
      */
     #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'entreprise')]
     private Collection $clients;

     #[ORM\ManyToOne(inversedBy: 'clients')]
     #[Groups(["group1", "group_type"])]
     private ?Boutique $boutique = null;

     /**
      * @var Collection<int, PaiementBoutique>
      */
     #[ORM\OneToMany(targetEntity: PaiementBoutique::class, mappedBy: 'client')]
     private Collection $paiementBoutiques;

     #[ORM\ManyToOne(inversedBy: 'clients')]
     private ?Entreprise $entreprise = null;

    public function __construct()
    {
        $this->factures = new ArrayCollection();
        $this->reservations = new ArrayCollection();
        $this->clients = new ArrayCollection();
        $this->paiementBoutiques = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(?string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(?string $prenom): static
    {
        $this->prenom = $prenom;

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
            $facture->setClient($this);
        }

        return $this;
    }

    public function removeFacture(Facture $facture): static
    {
        if ($this->factures->removeElement($facture)) {
            // set the owning side to null (unless already changed)
            if ($facture->getClient() === $this) {
                $facture->setClient(null);
            }
        }

        return $this;
    }

    public function getSurccursale(): ?Surccursale
    {
        return $this->surccursale;
    }

    public function setSurccursale(?Surccursale $surccursale): static
    {
        $this->surccursale = $surccursale;

        return $this;
    }

    public function getPhoto(): ?Fichier
    {
        return $this->photo;
    }

    public function setPhoto(?Fichier $photo): static
    {
        $this->photo = $photo;

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
            $reservation->setClient($this);
        }

        return $this;
    }

    public function removeReservation(Reservation $reservation): static
    {
        if ($this->reservations->removeElement($reservation)) {
            // set the owning side to null (unless already changed)
            if ($reservation->getClient() === $this) {
                $reservation->setClient(null);
            }
        }

        return $this;
    }

    

    public function getBoutique(): ?Boutique
    {
        return $this->boutique;
    }

    public function setBoutique(?Boutique $boutique): static
    {
        $this->boutique = $boutique;

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
            $paiementBoutique->setClient($this);
        }

        return $this;
    }

    public function removePaiementBoutique(PaiementBoutique $paiementBoutique): static
    {
        if ($this->paiementBoutiques->removeElement($paiementBoutique)) {
            // set the owning side to null (unless already changed)
            if ($paiementBoutique->getClient() === $this) {
                $paiementBoutique->setClient(null);
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
