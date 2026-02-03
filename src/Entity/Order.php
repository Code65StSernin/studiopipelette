<?php

namespace App\Entity;

use App\Repository\OrderRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\User;
use App\Entity\Facture;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: '`order`')]
class Order
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PAID = 'paid';
    public const STATUS_CANCELED = 'canceled';

    public const SHIPPING_MODE_RELAIS = 'relais';
    public const SHIPPING_MODE_DOMICILE = 'domicile';
    public const SHIPPING_MODE_LETTRE_SUIVIE = 'lettre_suivie';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $user = null;

    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column(type: 'integer')]
    private int $amountTotalCents = 0;

    #[ORM\Column(type: 'integer')]
    private int $amountProductsCents = 0;

    #[ORM\Column(type: 'integer')]
    private int $amountShippingCents = 0;

    #[ORM\Column(length: 20)]
    private string $shippingMode = 'relais'; // relais|domicile|lettre_suivie

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $relayId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $relayName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $relayAddress = null;

    #[ORM\Column(length: 191, nullable: true, unique: true)]
    private ?string $stripeCheckoutSessionId = null;

    #[ORM\Column(length: 191, nullable: true)]
    private ?string $stripePaymentIntentId = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $paidAt = null;

    #[ORM\OneToOne(mappedBy: 'order', targetEntity: Facture::class, cascade: ['persist', 'remove'])]
    private ?Facture $facture = null;

    // --- Données d'expédition Mondial Relay (pour le bordereau) ---

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $mondialRelayRecipientFirstName = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $mondialRelayRecipientLastName = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $mondialRelayParcelsCount = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $mondialRelayContentValueCents = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $mondialRelayContentDescription = 'Bougies décoratives';

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $mondialRelayLengthCm = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $mondialRelayWidthCm = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $mondialRelayHeightCm = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $mondialRelayWeightKg = null;

    // Infos de suivi / bordereau renvoyées par l'API Mondial Relay
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $mondialRelayShipmentNumber = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $mondialRelayLabelUrl = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isShipped = false;

    #[ORM\Column(type: 'boolean')]
    private bool $debloque = false;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): self { $this->user = $user; return $this; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): self { $this->status = $status; return $this; }

    public function getAmountTotalCents(): int { return $this->amountTotalCents; }
    public function setAmountTotalCents(int $cents): self { $this->amountTotalCents = $cents; return $this; }

    public function getAmountProductsCents(): int { return $this->amountProductsCents; }
    public function setAmountProductsCents(int $cents): self { $this->amountProductsCents = $cents; return $this; }

    public function getAmountShippingCents(): int { return $this->amountShippingCents; }
    public function setAmountShippingCents(int $cents): self { $this->amountShippingCents = $cents; return $this; }

    public function getShippingMode(): string { return $this->shippingMode; }
    public function setShippingMode(string $mode): self { $this->shippingMode = $mode; return $this; }

    public function getRelayId(): ?string { return $this->relayId; }
    public function setRelayId(?string $relayId): self { $this->relayId = $relayId; return $this; }

    public function getRelayName(): ?string { return $this->relayName; }
    public function setRelayName(?string $relayName): self { $this->relayName = $relayName; return $this; }

    public function getRelayAddress(): ?string { return $this->relayAddress; }
    public function setRelayAddress(?string $relayAddress): self { $this->relayAddress = $relayAddress; return $this; }

    public function getStripeCheckoutSessionId(): ?string { return $this->stripeCheckoutSessionId; }
    public function setStripeCheckoutSessionId(?string $id): self { $this->stripeCheckoutSessionId = $id; return $this; }

    public function getStripePaymentIntentId(): ?string { return $this->stripePaymentIntentId; }
    public function setStripePaymentIntentId(?string $id): self { $this->stripePaymentIntentId = $id; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function getPaidAt(): ?\DateTimeImmutable { return $this->paidAt; }
    public function setPaidAt(?\DateTimeImmutable $paidAt): self { $this->paidAt = $paidAt; return $this; }

    public function getFacture(): ?Facture
    {
        return $this->facture;
    }

    public function setFacture(?Facture $facture): self
    {
        // unset the owning side of the relation if necessary
        if ($facture === null && $this->facture !== null) {
            $this->facture->setOrder($this);
        }

        // set the owning side of the relation if necessary
        if ($facture !== null && $facture->getOrder() !== $this) {
            $facture->setOrder($this);
        }

        $this->facture = $facture;

        return $this;
    }

    // --- Getters / setters Mondial Relay ---

    public function getMondialRelayRecipientFirstName(): ?string
    {
        return $this->mondialRelayRecipientFirstName;
    }

    public function setMondialRelayRecipientFirstName(?string $firstName): self
    {
        $this->mondialRelayRecipientFirstName = $firstName;
        return $this;
    }

    public function getMondialRelayRecipientLastName(): ?string
    {
        return $this->mondialRelayRecipientLastName;
    }

    public function setMondialRelayRecipientLastName(?string $lastName): self
    {
        $this->mondialRelayRecipientLastName = $lastName;
        return $this;
    }

    public function getMondialRelayParcelsCount(): ?int
    {
        return $this->mondialRelayParcelsCount;
    }

    public function setMondialRelayParcelsCount(?int $count): self
    {
        $this->mondialRelayParcelsCount = $count;
        return $this;
    }

    public function getMondialRelayContentValueCents(): ?int
    {
        return $this->mondialRelayContentValueCents;
    }

    public function setMondialRelayContentValueCents(?int $valueCents): self
    {
        $this->mondialRelayContentValueCents = $valueCents;
        return $this;
    }

    public function getMondialRelayContentDescription(): ?string
    {
        return $this->mondialRelayContentDescription;
    }

    public function setMondialRelayContentDescription(?string $description): self
    {
        $this->mondialRelayContentDescription = $description;
        return $this;
    }

    public function getMondialRelayLengthCm(): ?int
    {
        return $this->mondialRelayLengthCm;
    }

    public function setMondialRelayLengthCm(?int $lengthCm): self
    {
        $this->mondialRelayLengthCm = $lengthCm;
        return $this;
    }

    public function getMondialRelayWidthCm(): ?int
    {
        return $this->mondialRelayWidthCm;
    }

    public function setMondialRelayWidthCm(?int $widthCm): self
    {
        $this->mondialRelayWidthCm = $widthCm;
        return $this;
    }

    public function getMondialRelayHeightCm(): ?int
    {
        return $this->mondialRelayHeightCm;
    }

    public function setMondialRelayHeightCm(?int $heightCm): self
    {
        $this->mondialRelayHeightCm = $heightCm;
        return $this;
    }

    public function getMondialRelayWeightKg(): ?float
    {
        return $this->mondialRelayWeightKg;
    }

    public function setMondialRelayWeightKg(?float $weightKg): self
    {
        $this->mondialRelayWeightKg = $weightKg;
        return $this;
    }

    public function getMondialRelayShipmentNumber(): ?string
    {
        return $this->mondialRelayShipmentNumber;
    }

    public function setMondialRelayShipmentNumber(?string $shipmentNumber): self
    {
        $this->mondialRelayShipmentNumber = $shipmentNumber;
        return $this;
    }

    public function getMondialRelayLabelUrl(): ?string
    {
        return $this->mondialRelayLabelUrl;
    }

    public function setMondialRelayLabelUrl(?string $labelUrl): self
    {
        $this->mondialRelayLabelUrl = $labelUrl;
        return $this;
    }

    public function isShipped(): bool
    {
        return $this->isShipped;
    }

    public function setIsShipped(bool $isShipped): self
    {
        $this->isShipped = $isShipped;
        return $this;
    }

    public function isDebloque(): bool
    {
        return $this->debloque;
    }

    public function setDebloque(bool $debloque): self
    {
        $this->debloque = $debloque;
        return $this;
    }
}
