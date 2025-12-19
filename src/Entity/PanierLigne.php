<?php

namespace App\Entity;

use App\Repository\PanierLigneRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PanierLigneRepository::class)]
class PanierLigne
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Panier::class, inversedBy: 'lignes')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Panier $panier = null;

    #[ORM\ManyToOne(targetEntity: Article::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Article $article = null;

    /**
     * Taille de l'article (ex: "S", "M", "L")
     */
    #[ORM\Column(length: 50)]
    #[Assert\NotBlank]
    private ?string $taille = null;

    /**
     * Quantité commandée
     */
    #[ORM\Column]
    #[Assert\Positive]
    private ?int $quantite = 1;

    /**
     * Prix unitaire au moment de l'ajout (historique)
     */
    #[ORM\Column]
    private ?float $prixUnitaire = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->quantite = 1;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPanier(): ?Panier
    {
        return $this->panier;
    }

    public function setPanier(?Panier $panier): static
    {
        $this->panier = $panier;
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

    public function getTaille(): ?string
    {
        return $this->taille;
    }

    public function setTaille(string $taille): static
    {
        $this->taille = $taille;
        return $this;
    }

    public function getQuantite(): ?int
    {
        return $this->quantite;
    }

    public function setQuantite(int $quantite): static
    {
        if ($quantite < 1) {
            throw new \InvalidArgumentException('La quantité doit être supérieure à 0');
        }
        $this->quantite = $quantite;
        return $this;
    }

    public function getPrixUnitaire(): ?float
    {
        return $this->prixUnitaire;
    }

    public function setPrixUnitaire(float $prixUnitaire): static
    {
        $this->prixUnitaire = $prixUnitaire;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * Calcule le sous-total (prix unitaire × quantité)
     */
    public function getSousTotal(): float
    {
        return $this->prixUnitaire * $this->quantite;
    }

    /**
     * Récupère les informations de stock pour cette taille
     */
    public function getStock(): ?int
    {
        if (!$this->article) {
            return null;
        }
        return $this->article->getStockParTaille($this->taille);
    }

    /**
     * Vérifie si la quantité demandée est disponible en stock
     */
    public function isStockSuffisant(): bool
    {
        $stock = $this->getStock();
        return $stock !== null && $stock >= $this->quantite;
    }

    public function __toString(): string
    {
        return ($this->article ? $this->article->getNom() : 'Article') . ' - Taille ' . $this->taille;
    }
}

