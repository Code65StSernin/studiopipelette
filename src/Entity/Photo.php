<?php

namespace App\Entity;

use App\Repository\PhotoRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PhotoRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Photo
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le nom du fichier est obligatoire')]
    private ?string $filename = null;

    #[ORM\Column(length: 20, nullable: false, options: ['default' => 'image'])]
    private ?string $type = 'image';

    #[ORM\ManyToOne(targetEntity: Article::class, inversedBy: 'photos')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?Article $article = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'photos')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?User $client = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFilename(): ?string
    {
        return $this->filename;
    }

    public function setFilename(string $filename): static
    {
        $this->filename = $filename;

        return $this;
    }

    public function getArticle(): ?Article
    {
        return $this->article;
    }

    public function setArticle(?Article $article): static
    {
        $this->article = $article;

        return $this;
    }

    public function getClient(): ?User
    {
        return $this->client;
    }

    public function setClient(?User $client): static
    {
        $this->client = $client;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Retourne le chemin de l'image (800x800)
     */
    public function getImagePath(): string
    {
        if ($this->article) {
            return '/assets/img/articles/' . $this->article->getId() . '/' . $this->filename;
        } elseif ($this->client) {
            return '/assets/img/clients/' . $this->client->getId() . '/' . $this->filename;
        }
        return '';
    }

    /**
     * Retourne le chemin de la miniature (250x250)
     */
    public function getThumbnailPath(): string
    {
        if ($this->article) {
            return '/assets/img/articles/thumbnails/' . $this->article->getId() . '/' . $this->filename;
        } elseif ($this->client) {
            return '/assets/img/clients/thumbnails/' . $this->client->getId() . '/' . $this->filename;
        }
        return '';
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function isVideo(): bool
    {
        return $this->type === 'video';
    }

    public function isImage(): bool
    {
        return $this->type === 'image';
    }

    /**
     * Retourne le chemin de la vidéo
     */
    public function getVideoPath(): string
    {
        if ($this->article) {
            return '/assets/videos/articles/' . $this->article->getId() . '/' . $this->filename;
        }
        return '';
    }

    public function __toString(): string
    {
        return $this->filename ?? 'Photo #' . $this->id;
    }
}

