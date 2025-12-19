<?php

namespace App\Entity;

use App\Repository\OffreRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OffreRepository::class)]
class Offre
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $petitTexte = null;

    #[ORM\Column(length: 255)]
    private ?string $grandTexte = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $texteBouton = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $lienBouton = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $dateDebut = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $dateFin = null;

    #[ORM\Column(type: 'boolean')]
    private bool $imageGauche = true;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPetitTexte(): ?string
    {
        return $this->petitTexte;
    }

    public function setPetitTexte(?string $petitTexte): self
    {
        $this->petitTexte = $petitTexte;

        return $this;
    }

    public function getGrandTexte(): ?string
    {
        return $this->grandTexte;
    }

    public function setGrandTexte(string $grandTexte): self
    {
        $this->grandTexte = $grandTexte;

        return $this;
    }

    public function getTexteBouton(): ?string
    {
        return $this->texteBouton;
    }

    public function setTexteBouton(?string $texteBouton): self
    {
        $this->texteBouton = $texteBouton;

        return $this;
    }

    public function getLienBouton(): ?string
    {
        return $this->lienBouton;
    }

    public function setLienBouton(?string $lienBouton): self
    {
        $this->lienBouton = $lienBouton;

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

    public function isImageGauche(): bool
    {
        return $this->imageGauche;
    }

    public function setImageGauche(bool $imageGauche): self
    {
        $this->imageGauche = $imageGauche;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): self
    {
        $this->image = $image;

        return $this;
    }

    public function isActive(\DateTimeImmutable $now = null): bool
    {
        $now ??= new \DateTimeImmutable();

        return $this->dateDebut !== null
            && $this->dateFin !== null
            && $this->dateDebut <= $now
            && $this->dateFin >= $now;
    }

    public function __toString(): string
    {
        return $this->grandTexte ?? 'Offre #' . $this->id;
    }
}

