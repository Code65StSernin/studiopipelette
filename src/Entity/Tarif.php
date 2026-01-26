<?php
namespace App\Entity;

use App\Repository\TarifRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TarifRepository::class)]
class Tarif
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $nom = null;

    // Stored as string for decimal precision
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $tarif = '0.00';

    #[ORM\Column(type: 'integer')]
    private int $dureeMinutes = 0;

    #[ORM\ManyToOne(targetEntity: CategorieVente::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?CategorieVente $categorieVente = null;

    #[ORM\ManyToOne(targetEntity: SousCategorieVente::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?SousCategorieVente $sousCategorieVente = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $image = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function __toString(): string
    {
        return $this->nom ?? '';
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $nom;

        return $this;
    }

    public function getTarif(): string
    {
        return $this->tarif;
    }

    public function setTarif(string $tarif): self
    {
        $this->tarif = $tarif;

        return $this;
    }

    public function getDureeMinutes(): int
    {
        return $this->dureeMinutes;
    }

    public function setDureeMinutes(int $dureeMinutes): self
    {
        $this->dureeMinutes = $dureeMinutes;

        return $this;
    }

    public function getCategorieVente(): ?CategorieVente
    {
        return $this->categorieVente;
    }

    public function setCategorieVente(?CategorieVente $categorieVente): self
    {
        $this->categorieVente = $categorieVente;

        return $this;
    }

    public function getSousCategorieVente(): ?SousCategorieVente
    {
        return $this->sousCategorieVente;
    }

    public function setSousCategorieVente(?SousCategorieVente $sousCategorieVente): self
    {
        $this->sousCategorieVente = $sousCategorieVente;

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
}
