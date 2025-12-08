<?php

namespace App\Entity;

use App\Repository\FichierRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Table;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: FichierRepository::class)]
#[Table(name: 'param_fichier')]
#[ORM\HasLifecycleCallbacks]
class Fichier
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["fichier", "groupe_batis", "group1","group_modeleBoutique"])]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    private ?int $size = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(["fichier", "groupe_batis", 'group_user', 'group_pro', "group1","group_modeleBoutique"])]
    private ?string $path = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(["fichier", "groupe_batis", 'group_user', 'group_pro', "group1","group_modeleBoutique"])]
    private ?string $alt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateCreation = null;

    #[ORM\Column(length: 5, nullable: true)]
    #[Groups(["fichier", "groupe_batis", 'group_pro'])]
    private ?string $url = null;

    #[Assert\NotNull(message: "Veuillez sélectionner un fichier", groups: ["FileRequired"])]
    private $file;

    private ?string $tempFilename = null;
    private string $uploadDir = 'uploads';
    private string $filePrefix = '';

    public function __construct()
    {
        $this->dateCreation = new \DateTime();
    }

   
    public function getId(): ?int { return $this->id; }
    public function getSize(): ?int { return $this->size; }
    public function setSize(?int $size): self { $this->size = $size; return $this; }
    public function getPath(): ?string { return $this->path; }
    public function setPath(string $path): self { $this->path = $path; return $this; }
    public function getAlt(): ?string { return $this->alt; }
    public function setAlt(?string $alt): self { $this->alt = $alt; return $this; }
    public function getDateCreation(): ?\DateTimeInterface { return $this->dateCreation; }
    public function setDateCreation(\DateTimeInterface $dateCreation): self { $this->dateCreation = $dateCreation; return $this; }
    public function getUrl(): ?string { return $this->url; }
    public function setUrl(?string $url): self { $this->url = $url; return $this; }
    public function getFile() { return $this->file; }
    public function getFilePrefix(): string { return $this->filePrefix; }
    public function setFilePrefix(string $filePrefix): self { $this->filePrefix = $filePrefix; return $this; }

    public function setFile(?UploadedFile $file = null): void
    {
        $this->file = $file;
        
        // Si on a déjà un fichier et qu'un nouveau est uploadé
        if (null !== $this->alt && null !== $file) {
            $this->tempFilename = $this->getAbsolutePath();
            $this->alt = null;
            $this->url = null;
            $this->size = null;
        }
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function preUpload(): void
    {
        if (null === $this->file) {
            return;
        }

        $originalName = $this->file->getClientOriginalName();
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $safeName = substr(preg_replace('/[^a-zA-Z0-9-_]/', '_', $originalName), 0, 100);
        
        $this->url = $extension;
        $this->alt = $this->filePrefix ? $this->filePrefix.'_'.$safeName : $safeName;
        $this->size = $this->file->getSize();
    }

    #[ORM\PostPersist]
    #[ORM\PostUpdate]
    public function upload(): void
    {
        if (null === $this->file) {
            return;
        }

        // Suppression de l'ancien fichier s'il existe
        if (null !== $this->tempFilename && file_exists($this->tempFilename)) {
            unlink($this->tempFilename);
        }

        // Création du répertoire si nécessaire
        $uploadDir = $this->getUploadRootDir();
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Déplacement du fichier
        $this->file->move($uploadDir, $this->alt);
        $this->file = null;
    }

    #[ORM\PreRemove]
    public function preRemoveUpload(): void
    {
        $this->tempFilename = $this->getAbsolutePath();
    }

    #[ORM\PostRemove]
    public function removeUpload(): void
    {
        if (null !== $this->tempFilename && file_exists($this->tempFilename)) {
            unlink($this->tempFilename);
            
            // Optionnel : supprimer le dossier parent s'il est vide
            $dir = dirname($this->tempFilename);
            if (is_dir($dir) && count(scandir($dir)) === 2) {
                rmdir($dir);
            }
        }
    }

    public function getAbsolutePath(): string
    {
        return $this->getUploadRootDir().'/'.$this->alt;
    }

    public function getWebPath(): string
    {
        return '/'.$this->uploadDir.'/'.$this->path.'/'.$this->alt;
    }

    protected function getUploadRootDir(): string
    {
        return __DIR__.'/../../public/'.$this->uploadDir.'/'.$this->path;
    }

    public function setUploadDir(string $uploadDir): self
    {
        $this->uploadDir = $uploadDir;
        return $this;
    }
}