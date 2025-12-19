<?php

namespace App\Entity;

use App\Repository\CodeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CodeRepository::class)]
class Code
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    private ?string $code = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $dateDebut = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $dateFin = null;

    #[ORM\Column(type: 'float')]
    private float $pourcentageRemise = 0.0;

    #[ORM\Column(type: 'boolean')]
    private bool $usageUnique = false;

    #[ORM\Column(type: 'boolean')]
    private bool $dejaUtilise = false;

    #[ORM\Column(type: 'boolean')]
    private bool $premiereCommandeSeulement = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = strtoupper($code);

        return $this;
    }

    public function getDateDebut(): ?\DateTimeImmutable
    {
        return $this->dateDebut;
    }

    public function setDateDebut(\DateTimeImmutable $dateDebut): self
    {
        $this->dateDebut = $dateDebut;

        return $this;
    }

    public function getDateFin(): ?\DateTimeImmutable
    {
        return $this->dateFin;
    }

    public function setDateFin(\DateTimeImmutable $dateFin): self
    {
        $this->dateFin = $dateFin;

        return $this;
    }

    public function getPourcentageRemise(): float
    {
        return $this->pourcentageRemise;
    }

    public function setPourcentageRemise(float $pourcentageRemise): self
    {
        $this->pourcentageRemise = $pourcentageRemise;

        return $this;
    }

    public function isUsageUnique(): bool
    {
        return $this->usageUnique;
    }

    public function setUsageUnique(bool $usageUnique): self
    {
        $this->usageUnique = $usageUnique;

        return $this;
    }

    public function isDejaUtilise(): bool
    {
        return $this->dejaUtilise;
    }

    public function setDejaUtilise(bool $dejaUtilise): self
    {
        $this->dejaUtilise = $dejaUtilise;

        return $this;
    }

    public function isPremiereCommandeSeulement(): bool
    {
        return $this->premiereCommandeSeulement;
    }

    public function setPremiereCommandeSeulement(bool $premiereCommandeSeulement): self
    {
        $this->premiereCommandeSeulement = $premiereCommandeSeulement;

        return $this;
    }

    public function __toString(): string
    {
        return (string) $this->code;
    }
}

