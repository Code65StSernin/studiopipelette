<?php

namespace App\Entity;

use App\Repository\EtiquetteFormatRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EtiquetteFormatRepository::class)]
class EtiquetteFormat
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'float')]
    private ?float $margeHaut = null;

    #[ORM\Column(type: 'float')]
    private ?float $margeBas = null;

    #[ORM\Column(type: 'float')]
    private ?float $margeGauche = null;

    #[ORM\Column(type: 'float')]
    private ?float $margeDroite = null;

    #[ORM\Column(type: 'float')]
    private ?float $distanceHorizontale = null;

    #[ORM\Column(type: 'float')]
    private ?float $distanceVerticale = null;

    #[ORM\Column(type: 'float')]
    private ?float $largeur = null;

    #[ORM\Column(type: 'float')]
    private ?float $hauteur = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMargeHaut(): ?float
    {
        return $this->margeHaut;
    }

    public function setMargeHaut(float $margeHaut): static
    {
        $this->margeHaut = $margeHaut;
        return $this;
    }

    public function getMargeBas(): ?float
    {
        return $this->margeBas;
    }

    public function setMargeBas(float $margeBas): static
    {
        $this->margeBas = $margeBas;
        return $this;
    }

    public function getMargeGauche(): ?float
    {
        return $this->margeGauche;
    }

    public function setMargeGauche(float $margeGauche): static
    {
        $this->margeGauche = $margeGauche;
        return $this;
    }

    public function getMargeDroite(): ?float
    {
        return $this->margeDroite;
    }

    public function setMargeDroite(float $margeDroite): static
    {
        $this->margeDroite = $margeDroite;
        return $this;
    }

    public function getDistanceHorizontale(): ?float
    {
        return $this->distanceHorizontale;
    }

    public function setDistanceHorizontale(float $distanceHorizontale): static
    {
        $this->distanceHorizontale = $distanceHorizontale;
        return $this;
    }

    public function getDistanceVerticale(): ?float
    {
        return $this->distanceVerticale;
    }

    public function setDistanceVerticale(float $distanceVerticale): static
    {
        $this->distanceVerticale = $distanceVerticale;
        return $this;
    }

    public function getLargeur(): ?float
    {
        return $this->largeur;
    }

    public function setLargeur(float $largeur): static
    {
        $this->largeur = $largeur;
        return $this;
    }

    public function getHauteur(): ?float
    {
        return $this->hauteur;
    }

    public function setHauteur(float $hauteur): static
    {
        $this->hauteur = $hauteur;
        return $this;
    }
}
