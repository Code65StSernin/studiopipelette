<?php

namespace App\Entity;

use App\Repository\CouleurRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CouleurRepository::class)]
class Couleur
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'Le nom de la couleur est obligatoire')]
    #[Assert\Length(max: 50, maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères')]
    private ?string $nom = null;

    #[ORM\Column(length: 7, nullable: true)]
    #[Assert\Regex(
        pattern: '/^#[0-9A-Fa-f]{6}$/',
        message: 'Le code couleur doit être au format hexadécimal (ex: #FF0000)'
    )]
    private ?string $codeHex = null;

    /**
     * @var Collection<int, Article>
     */
    #[ORM\ManyToMany(targetEntity: Article::class, mappedBy: 'couleurs')]
    private Collection $articles;

    /**
     * @var Collection<int, ArticleCollection>
     */
    #[ORM\ManyToMany(targetEntity: ArticleCollection::class, mappedBy: 'couleurs')]
    private Collection $collections;

    public function __construct()
    {
        $this->articles = new ArrayCollection();
        $this->collections = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getCodeHex(): ?string
    {
        return $this->codeHex;
    }

    public function setCodeHex(?string $codeHex): static
    {
        $this->codeHex = $codeHex;

        return $this;
    }

    /**
     * @return Collection<int, Article>
     */
    public function getArticles(): Collection
    {
        return $this->articles;
    }

    public function addArticle(Article $article): static
    {
        if (!$this->articles->contains($article)) {
            $this->articles->add($article);
            $article->addCouleur($this);
        }

        return $this;
    }

    public function removeArticle(Article $article): static
    {
        if ($this->articles->removeElement($article)) {
            $article->removeCouleur($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, ArticleCollection>
     */
    public function getCollections(): Collection
    {
        return $this->collections;
    }

    public function addCollection(ArticleCollection $collection): static
    {
        if (!$this->collections->contains($collection)) {
            $this->collections->add($collection);
        }

        return $this;
    }

    public function removeCollection(ArticleCollection $collection): static
    {
        $this->collections->removeElement($collection);

        return $this;
    }

    public function __toString(): string
    {
        return $this->nom ?? 'Couleur #' . $this->id;
    }
}

