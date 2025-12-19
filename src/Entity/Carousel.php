<?php

namespace App\Entity;

use App\Repository\CarouselRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CarouselRepository::class)]
class Carousel
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    private ?string $petitTitre = null;

    #[ORM\Column(length: 255)]
    private ?string $grandTitre = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $texteBouton = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $lienBouton = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $dateDebut = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $dateFin = null;

    #[ORM\Column(length: 255)]
    private ?string $image = null; // nom de fichier de l'image

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPetitTitre(): ?string
    {
        return $this->petitTitre;
    }

    public function setPetitTitre(string $petitTitre): self
    {
        $this->petitTitre = $petitTitre;

        return $this;
    }

    public function getGrandTitre(): ?string
    {
        return $this->grandTitre;
    }

    public function setGrandTitre(string $grandTitre): self
    {
        $this->grandTitre = $grandTitre;

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

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(string $image): self
    {
        $this->image = $image;

        return $this;
    }

    public function isActive(\DateTimeImmutable $date = null): bool
    {
        $date ??= new \DateTimeImmutable();

        return $this->dateDebut <= $date && $this->dateFin >= $date;
    }
}
