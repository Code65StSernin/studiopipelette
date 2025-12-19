<?php

namespace App\Entity;

use App\Repository\PanierRepository;
use App\Entity\Code;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PanierRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Panier
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Utilisateur propriétaire du panier (null si non connecté)
     */
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'paniers')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?User $user = null;

    /**
     * Identifiant de session pour les paniers non connectés
     */
    #[ORM\Column(length: 191, nullable: true, unique: true)]
    private ?string $sessionId = null;

    /**
     * @var Collection<int, PanierLigne>
     */
    #[ORM\OneToMany(targetEntity: PanierLigne::class, mappedBy: 'panier', orphanRemoval: true, cascade: ['persist', 'remove'])]
    private Collection $lignes;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(targetEntity: Code::class)]
    private ?Code $codePromo = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $codePromoPourcentage = null;

    public function __construct()
    {
        $this->lignes = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getSessionId(): ?string
    {
        return $this->sessionId;
    }

    public function setSessionId(?string $sessionId): static
    {
        $this->sessionId = $sessionId;
        return $this;
    }

    /**
     * @return Collection<int, PanierLigne>
     */
    public function getLignes(): Collection
    {
        return $this->lignes;
    }

    public function addLigne(PanierLigne $ligne): static
    {
        if (!$this->lignes->contains($ligne)) {
            $this->lignes->add($ligne);
            $ligne->setPanier($this);
        }

        return $this;
    }

    public function removeLigne(PanierLigne $ligne): static
    {
        if ($this->lignes->removeElement($ligne)) {
            if ($ligne->getPanier() === $this) {
                $ligne->setPanier(null);
            }
        }

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

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    /**
     * Calcule le montant total TTC du panier
     */
    public function getTotalTTC(): float
    {
        $total = 0;
        foreach ($this->lignes as $ligne) {
            $total += $ligne->getSousTotal();
        }
        return $total;
    }

    /**
     * Retourne le nombre total d'articles dans le panier
     */
    public function getNombreArticles(): int
    {
        $total = 0;
        foreach ($this->lignes as $ligne) {
            $total += $ligne->getQuantite();
        }
        return $total;
    }

    /**
     * Vérifie si le panier contient un article avec une taille spécifique
     */
    public function contientArticle(Article $article, string $taille): ?PanierLigne
    {
        foreach ($this->lignes as $ligne) {
            if ($ligne->getArticle()->getId() === $article->getId() && $ligne->getTaille() === $taille) {
                return $ligne;
            }
        }
        return null;
    }

    /**
     * Vérifie si le panier est vide
     */
    public function isEmpty(): bool
    {
        return $this->lignes->isEmpty();
    }

    public function __toString(): string
    {
        return 'Panier #' . $this->id . ' (' . $this->getNombreArticles() . ' articles)';
    }

    public function getCodePromo(): ?Code
    {
        return $this->codePromo;
    }

    public function setCodePromo(?Code $codePromo): self
    {
        $this->codePromo = $codePromo;

        return $this;
    }

    public function getCodePromoPourcentage(): ?float
    {
        return $this->codePromoPourcentage;
    }

    public function setCodePromoPourcentage(?float $codePromoPourcentage): self
    {
        $this->codePromoPourcentage = $codePromoPourcentage;

        return $this;
    }
}

