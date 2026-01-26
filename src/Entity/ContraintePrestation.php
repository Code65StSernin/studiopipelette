<?php

namespace App\Entity;

use App\Repository\ContraintePrestationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ContraintePrestationRepository::class)]
#[ORM\HasLifecycleCallbacks]
class ContraintePrestation
{
    // Jours de la semaine
    public const JOUR_LUNDI = 'lundi';
    public const JOUR_MARDI = 'mardi';
    public const JOUR_MERCREDI = 'mercredi';
    public const JOUR_JEUDI = 'jeudi';
    public const JOUR_VENDREDI = 'vendredi';
    public const JOUR_SAMEDI = 'samedi';
    public const JOUR_DIMANCHE = 'dimanche';

    public const JOURS_DISPONIBLES = [
        self::JOUR_LUNDI => 'Lundi',
        self::JOUR_MARDI => 'Mardi',
        self::JOUR_MERCREDI => 'Mercredi',
        self::JOUR_JEUDI => 'Jeudi',
        self::JOUR_VENDREDI => 'Vendredi',
        self::JOUR_SAMEDI => 'Samedi',
        self::JOUR_DIMANCHE => 'Dimanche',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nom = null;

    /**
     * @var Collection<int, Tarif>
     */
    #[ORM\ManyToMany(targetEntity: Tarif::class)]
    #[ORM\JoinTable(name: 'contrainte_prestation_tarif')]
    #[Assert\Count(min: 1, minMessage: 'Au moins une prestation doit être sélectionnée')]
    private Collection $tarifs;

    /**
     * Jours de la semaine où les prestations ne peuvent pas être réalisées
     * Format: ['lundi', 'mardi', ...]
     *
     * @var array<string>
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $joursInterdits = null;

    /**
     * Limite du nombre de fois qu'une prestation peut être réalisée par jour
     * null = pas de limite
     */
    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Assert\Positive(message: 'La limite par jour doit être un nombre positif')]
    private ?int $limiteParJour = null;

    #[ORM\Column]
    private bool $actif = true;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->tarifs = new ArrayCollection();
        $this->joursInterdits = [];
        $this->createdAt = new \DateTime();
        $this->actif = true;
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(?string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    /**
     * @return Collection<int, Tarif>
     */
    public function getTarifs(): Collection
    {
        return $this->tarifs;
    }

    public function addTarif(Tarif $tarif): static
    {
        if (!$this->tarifs->contains($tarif)) {
            $this->tarifs->add($tarif);
        }

        return $this;
    }

    public function removeTarif(Tarif $tarif): static
    {
        $this->tarifs->removeElement($tarif);

        return $this;
    }

    /**
     * @return array<string>|null
     */
    public function getJoursInterdits(): ?array
    {
        return $this->joursInterdits;
    }

    /**
     * @param array<string>|null $joursInterdits
     */
    public function setJoursInterdits(?array $joursInterdits): static
    {
        $this->joursInterdits = $joursInterdits;

        return $this;
    }

    public function getLimiteParJour(): ?int
    {
        return $this->limiteParJour;
    }

    public function setLimiteParJour(?int $limiteParJour): static
    {
        $this->limiteParJour = $limiteParJour;

        return $this;
    }

    public function isActif(): bool
    {
        return $this->actif;
    }

    public function setActif(bool $actif): static
    {
        $this->actif = $actif;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Retourne les jours interdits formatés pour l'affichage
     */
    public function getJoursInterditsFormates(): string
    {
        if (!$this->joursInterdits || empty($this->joursInterdits)) {
            return 'Aucun';
        }

        $joursLabels = [];
        foreach ($this->joursInterdits as $jour) {
            $joursLabels[] = self::JOURS_DISPONIBLES[$jour] ?? $jour;
        }

        return implode(', ', $joursLabels);
    }

    /**
     * Retourne un résumé de la contrainte pour l'affichage
     */
    public function getResume(): string
    {
        $parts = [];

        if ($this->nom) {
            $parts[] = $this->nom;
        }

        $tarifsCount = $this->tarifs->count();
        $parts[] = sprintf('%d prestation(s)', $tarifsCount);

        if ($this->joursInterdits && !empty($this->joursInterdits)) {
            $parts[] = sprintf('Interdites: %s', $this->getJoursInterditsFormates());
        }

        if ($this->limiteParJour !== null) {
            $parts[] = sprintf('Max %d/jour', $this->limiteParJour);
        }

        return implode(' | ', $parts);
    }

    public function __toString(): string
    {
        return $this->nom ?? sprintf('Contrainte #%d', $this->id);
    }
}

