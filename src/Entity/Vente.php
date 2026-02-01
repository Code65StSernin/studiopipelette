<?php

namespace App\Entity;

use App\Repository\VenteRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VenteRepository::class)]
class Vente
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $client = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $dateVente = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $montantTotal = null;

    #[ORM\Column(length: 255)]
    private ?string $modePaiement = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $commentaire = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private ?bool $isAnnule = false;

    #[ORM\OneToMany(mappedBy: 'vente', targetEntity: LigneVente::class, orphanRemoval: true, cascade: ['persist'])]
    private Collection $ligneVentes;

    #[ORM\OneToOne(mappedBy: 'vente', cascade: ['persist', 'remove'])]
    private ?Avoir $avoir = null;

    #[ORM\OneToMany(mappedBy: 'vente', targetEntity: Paiement::class, orphanRemoval: true, cascade: ['persist'])]
    private Collection $paiements;

    #[ORM\OneToOne(mappedBy: 'vente', targetEntity: Facture::class, cascade: ['persist', 'remove'])]
    private ?Facture $facture = null;

    public function __construct()
    {
        $this->ligneVentes = new ArrayCollection();
        $this->paiements = new ArrayCollection();
        $this->dateVente = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getClient(): ?User
    {
        return $this->client;
    }

    public function setClient(?User $client): self
    {
        $this->client = $client;

        return $this;
    }

    public function getDateVente(): ?\DateTimeImmutable
    {
        return $this->dateVente;
    }

    public function setDateVente(\DateTimeImmutable $dateVente): self
    {
        $this->dateVente = $dateVente;

        return $this;
    }

    public function getMontantTotal(): ?string
    {
        return $this->montantTotal;
    }

    public function setMontantTotal(string $montantTotal): self
    {
        $this->montantTotal = $montantTotal;

        return $this;
    }

    public function getModePaiement(): ?string
    {
        return $this->modePaiement;
    }

    public function setModePaiement(string $modePaiement): self
    {
        $this->modePaiement = $modePaiement;

        return $this;
    }

    public function getCommentaire(): ?string
    {
        return $this->commentaire;
    }

    public function setCommentaire(?string $commentaire): self
    {
        $this->commentaire = $commentaire;

        return $this;
    }

    /**
     * @return Collection<int, LigneVente>
     */
    public function getLigneVentes(): Collection
    {
        return $this->ligneVentes;
    }

    public function addLigneVente(LigneVente $ligneVente): self
    {
        if (!$this->ligneVentes->contains($ligneVente)) {
            $this->ligneVentes->add($ligneVente);
            $ligneVente->setVente($this);
        }

        return $this;
    }

    public function removeLigneVente(LigneVente $ligneVente): self
    {
        if ($this->ligneVentes->removeElement($ligneVente)) {
            // set the owning side to null (unless already changed)
            if ($ligneVente->getVente() === $this) {
                $ligneVente->setVente(null);
            }
        }

        return $this;
    }

    public function isAnnule(): ?bool
    {
        return $this->isAnnule;
    }

    public function setIsAnnule(bool $isAnnule): self
    {
        $this->isAnnule = $isAnnule;

        return $this;
    }

    public function getAvoir(): ?Avoir
    {
        return $this->avoir;
    }

    public function setAvoir(Avoir $avoir): static
    {
        // set the owning side of the relation if necessary
        if ($avoir->getVente() !== $this) {
            $avoir->setVente($this);
        }

        $this->avoir = $avoir;

        return $this;
    }

    /**
     * @return Collection<int, Paiement>
     */
    public function getPaiements(): Collection
    {
        return $this->paiements;
    }

    public function addPaiement(Paiement $paiement): static
    {
        if (!$this->paiements->contains($paiement)) {
            $this->paiements->add($paiement);
            $paiement->setVente($this);
        }

        return $this;
    }

    public function removePaiement(Paiement $paiement): static
    {
        if ($this->paiements->removeElement($paiement)) {
            // set the owning side to null (unless already changed)
            if ($paiement->getVente() === $this) {
                $paiement->setVente(null);
            }
        }

        return $this;
    }

    /**
     * Helper to check if sale contains only 100% commission items (no depot-vente)
     * Returns true if all lines are either Prestation or Shop Article (not Depot-Vente)
     */
    public function isCommission100(): bool
    {
        foreach ($this->ligneVentes as $ligne) {
            $article = $ligne->getArticle();
            if ($article && $article->getFournisseur() && $article->getFournisseur()->isClientDepotVente()) {
                $commissionRate = 0;
                $depotVente = $article->getFournisseur()->getDepotVente();
                if ($depotVente) {
                    $commissionRate = $depotVente->getCommission();
                }
                
                // Si un article dépôt-vente a une commission inférieure à 100%, la vente n'est pas "100% commission"
                if ($commissionRate < 100) {
                    return false;
                }
            }
        }
        return true;
    }

    public function getFacture(): ?Facture
    {
        return $this->facture;
    }

    public function setFacture(?Facture $facture): self
    {
        // set the owning side of the relation if necessary
        if ($facture !== null && $facture->getVente() !== $this) {
            $facture->setVente($this);
        }

        $this->facture = $facture;

        return $this;
    }
}
