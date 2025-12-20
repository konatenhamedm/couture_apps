<?php

namespace App\Entity;

use App\Repository\TypeUserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: TypeUserRepository::class)]
#[UniqueEntity(fields: 'code', message: 'Ce code est déjà associé à un autre type d\'utilisateur.')]
class TypeUser
{ use TraitEntity;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
     #[Groups(["group1"])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
     #[Groups(["group1"])]
    private ?string $libelle = null;

    #[ORM\Column(length: 255)]
     #[Groups(["group1"])]
    private ?string $code = null;

    /**
     * @var Collection<int, User>
     */
    #[ORM\OneToMany(targetEntity: User::class, mappedBy: 'type')]
    private Collection $users;

    public function __construct()
    {
        $this->users = new ArrayCollection();
    
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

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;

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
            $user->setType($this);
        }

        return $this;
    }

    public function removeUser(User $user): static
    {
        if ($this->users->removeElement($user)) {
            // set the owning side to null (unless already changed)
            if ($user->getType() === $this) {
                $user->setType(null);
            }
        }

        return $this;
    }
}
