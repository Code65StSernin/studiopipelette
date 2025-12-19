<?php

namespace App\Entity;

use App\Repository\LigneFactureRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LigneFactureRepository::class)]
class LigneFacture
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Facture::class, inversedBy: 'lignesFacture')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Facture $facture = null;

    // Données article (stockées pour immuabilité)
    #[ORM\Column(length: 255)]
    private ?string $articleDesignation = null;

    #[ORM\Column(length: 50)]
    private ?string $articleTaille = null;

    #[ORM\Column(type: 'integer')]
    private int $quantite = 0;

    #[ORM\Column(type: 'integer')]
    private int $prixUnitaire = 0; // en centimes

    #[ORM\Column(type: 'integer')]
    private int $prixTotal = 0; // en centimes

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFacture(): ?Facture
    {
        return $this->facture;
    }

    public function setFacture(?Facture $facture): static
    {
        $this->facture = $facture;

        return $this;
    }

    public function getArticleDesignation(): ?string
    {
        return $this->articleDesignation;
    }

    public function setArticleDesignation(string $articleDesignation): static
    {
        $this->articleDesignation = $articleDesignation;

        return $this;
    }

    public function getArticleTaille(): ?string
    {
        return $this->articleTaille;
    }

    public function setArticleTaille(string $articleTaille): static
    {
        $this->articleTaille = $articleTaille;

        return $this;
    }

    public function getQuantite(): int
    {
        return $this->quantite;
    }

    public function setQuantite(int $quantite): static
    {
        $this->quantite = $quantite;

        return $this;
    }

    public function getPrixUnitaire(): int
    {
        return $this->prixUnitaire;
    }

    public function setPrixUnitaire(int $prixUnitaire): static
    {
        $this->prixUnitaire = $prixUnitaire;

        return $this;
    }

    public function getPrixTotal(): int
    {
        return $this->prixTotal;
    }

    public function setPrixTotal(int $prixTotal): static
    {
        $this->prixTotal = $prixTotal;

        return $this;
    }

    /**
     * Calcule le prix total basé sur quantité * prix unitaire
     */
    public function calculerPrixTotal(): void
    {
        $this->prixTotal = $this->quantite * $this->prixUnitaire;
    }
}