<?php

namespace App\Entity;

use App\Repository\ArticleCollectionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection as DoctrineCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ArticleCollectionRepository::class)]
#[ORM\Table(name: 'collection')]
class ArticleCollection
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Le nom de la collection est obligatoire')]
    #[Assert\Length(max: 100, maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères')]
    private ?string $nom = null;

    /**
     * @var DoctrineCollection<int, Couleur>
     */
    #[ORM\ManyToMany(targetEntity: Couleur::class, inversedBy: 'collections')]
    #[ORM\JoinTable(
        name: 'collection_couleur',
        joinColumns: [
            new ORM\JoinColumn(name: 'collection_id', referencedColumnName: 'id')
        ],
        inverseJoinColumns: [
            new ORM\InverseJoinColumn(name: 'couleur_id', referencedColumnName: 'id')
        ]
    )]
    private DoctrineCollection $couleurs;

    /**
     * @var DoctrineCollection<int, Article>
     */
    #[ORM\ManyToMany(targetEntity: Article::class, mappedBy: 'collections')]
    private DoctrineCollection $articles;

    public function __construct()
    {
        $this->couleurs = new ArrayCollection();
        $this->articles = new ArrayCollection();
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

    /**
     * @return DoctrineCollection<int, Couleur>
     */
    public function getCouleurs(): DoctrineCollection
    {
        return $this->couleurs;
    }

    public function addCouleur(Couleur $couleur): static
    {
        if (!$this->couleurs->contains($couleur)) {
            $this->couleurs->add($couleur);
        }

        return $this;
    }

    public function removeCouleur(Couleur $couleur): static
    {
        $this->couleurs->removeElement($couleur);

        return $this;
    }

    /**
     * @return DoctrineCollection<int, Article>
     */
    public function getArticles(): DoctrineCollection
    {
        return $this->articles;
    }

    public function addArticle(Article $article): static
    {
        if (!$this->articles->contains($article)) {
            $this->articles->add($article);
            $article->addCollection($this);
        }

        return $this;
    }

    public function removeArticle(Article $article): static
    {
        if ($this->articles->removeElement($article)) {
            $article->removeCollection($this);
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->nom ?? 'Collection #' . $this->id;
    }
}

