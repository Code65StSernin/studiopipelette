<?php

namespace App\Entity;

use App\Repository\FactureRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FactureRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Facture
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    private ?string $numero = null;

    #[ORM\OneToOne(targetEntity: Order::class, inversedBy: 'facture')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Order $order = null;

    #[ORM\OneToOne(targetEntity: Vente::class, inversedBy: 'facture')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Vente $vente = null;

    // Coordonnées client (stockées pour immuabilité)
    #[ORM\Column(length: 100)]
    private ?string $clientNom = null;

    #[ORM\Column(length: 100)]
    private ?string $clientPrenom = null;

    #[ORM\Column(length: 180)]
    private ?string $clientEmail = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $clientAdresse = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $clientCodePostal = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $clientVille = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $clientPays = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateCreation = null;

    #[ORM\Column(type: 'integer')]
    private int $totalTTC = 0;

    #[ORM\Column(length: 20)]
    private string $modeLivraison = 'relais';

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $fraisLivraison = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $btobRemiseCents = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $remisePourcentage = null;

    /**
     * @var Collection<int, LigneFacture>
     */
    #[ORM\OneToMany(targetEntity: LigneFacture::class, mappedBy: 'facture', orphanRemoval: true, cascade: ['persist'])]
    private Collection $lignesFacture;

    public function __construct()
    {
        $this->lignesFacture = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumero(): ?string
    {
        return $this->numero;
    }

    public function setNumero(string $numero): static
    {
        $this->numero = $numero;

        return $this;
    }

    public function getOrder(): ?Order
    {
        return $this->order;
    }

    public function setOrder(?Order $order): static
    {
        $this->order = $order;

        return $this;
    }

    public function getVente(): ?Vente
    {
        return $this->vente;
    }

    public function setVente(?Vente $vente): static
    {
        $this->vente = $vente;

        return $this;
    }

    public function getClientNom(): ?string
    {
        return $this->clientNom;
    }

    public function setClientNom(string $clientNom): static
    {
        $this->clientNom = $clientNom;

        return $this;
    }

    public function getClientPrenom(): ?string
    {
        return $this->clientPrenom;
    }

    public function setClientPrenom(string $clientPrenom): static
    {
        $this->clientPrenom = $clientPrenom;

        return $this;
    }

    public function getClientEmail(): ?string
    {
        return $this->clientEmail;
    }

    public function setClientEmail(string $clientEmail): static
    {
        $this->clientEmail = $clientEmail;

        return $this;
    }

    public function getClientAdresse(): ?string
    {
        return $this->clientAdresse;
    }

    public function setClientAdresse(?string $clientAdresse): static
    {
        $this->clientAdresse = $clientAdresse;

        return $this;
    }

    public function getClientCodePostal(): ?string
    {
        return $this->clientCodePostal;
    }

    public function setClientCodePostal(?string $clientCodePostal): static
    {
        $this->clientCodePostal = $clientCodePostal;

        return $this;
    }

    public function getClientVille(): ?string
    {
        return $this->clientVille;
    }

    public function setClientVille(?string $clientVille): static
    {
        $this->clientVille = $clientVille;

        return $this;
    }

    public function getClientPays(): ?string
    {
        return $this->clientPays;
    }

    public function setClientPays(?string $clientPays): static
    {
        $this->clientPays = $clientPays;

        return $this;
    }

    public function getDateCreation(): ?\DateTimeInterface
    {
        return $this->dateCreation;
    }

    public function setDateCreation(\DateTimeInterface $dateCreation): static
    {
        $this->dateCreation = $dateCreation;

        return $this;
    }

    public function getTotalTTC(): int
    {
        return $this->totalTTC;
    }

    public function setTotalTTC(int $totalTTC): static
    {
        $this->totalTTC = $totalTTC;

        return $this;
    }

    public function getModeLivraison(): string
    {
        return $this->modeLivraison;
    }

    public function setModeLivraison(string $modeLivraison): static
    {
        $this->modeLivraison = $modeLivraison;

        return $this;
    }

    public function getFraisLivraison(): ?int
    {
        return $this->fraisLivraison;
    }

    public function setFraisLivraison(?int $fraisLivraison): static
    {
        $this->fraisLivraison = $fraisLivraison;

        return $this;
    }

    public function getBtobRemiseCents(): ?int
    {
        return $this->btobRemiseCents;
    }

    public function setBtobRemiseCents(?int $btobRemiseCents): static
    {
        $this->btobRemiseCents = $btobRemiseCents;
        return $this;
    }

    /**
     * @return Collection<int, LigneFacture>
     */
    public function getLignesFacture(): Collection
    {
        return $this->lignesFacture;
    }

    public function addLigneFacture(LigneFacture $ligneFacture): static
    {
        if (!$this->lignesFacture->contains($ligneFacture)) {
            $this->lignesFacture->add($ligneFacture);
            $ligneFacture->setFacture($this);
        }

        return $this;
    }

    public function removeLigneFacture(LigneFacture $ligneFacture): static
    {
        if ($this->lignesFacture->removeElement($ligneFacture)) {
            // set the owning side to null (unless already changed)
            if ($ligneFacture->getFacture() === $this) {
                $ligneFacture->setFacture(null);
            }
        }

        return $this;
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void // Renommé pour plus de clarté
    {
        // Définit la date de création si elle n'existe pas
        if (!$this->dateCreation) {
            $this->dateCreation = new \DateTime();
        }
    }

    /**
     * Génère un numéro de facture unique basé sur un numéro séquentiel.
     * Format: FB + année(2) + mois(2) + - + numéro séquentiel (4 chiffres)
     */
    public function generateNumero(int $sequentialNumber): string
    {
        $datePart = (new \DateTime())->format('ym'); // ex: 2310
        return 'FB' . $datePart . '-' . str_pad((string) $sequentialNumber, 4, '0', STR_PAD_LEFT);
    }

    public function getRemisePourcentage(): ?float
    {
        return $this->remisePourcentage;
    }

    public function setRemisePourcentage(?float $remisePourcentage): self
    {
        $this->remisePourcentage = $remisePourcentage;

        return $this;
    }
}
