<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class FondCaisse
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(type: 'float')]
    private float $montant = 0.0;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $montantCloture = null;

    #[ORM\Column(type: 'boolean')]
    private bool $cloture = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getMontant(): float
    {
        return $this->montant;
    }

    public function setMontant(float $montant): self
    {
        $this->montant = $montant;

        return $this;
    }

    public function getMontantCloture(): ?float
    {
        return $this->montantCloture;
    }

    public function setMontantCloture(?float $montantCloture): self
    {
        $this->montantCloture = $montantCloture;

        return $this;
    }

    public function isCloture(): bool
    {
        return $this->cloture;
    }

    public function setCloture(bool $cloture): self
    {
        $this->cloture = $cloture;

        return $this;
    }
}
