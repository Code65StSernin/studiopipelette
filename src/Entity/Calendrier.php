<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Calendrier
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    /**
     * Jour concerné (date sans heure)
     */
    #[ORM\Column(type: 'date')]
    private \DateTimeInterface $date;

    /**
     * Liste des créneaux pour ce jour.
     * Chaque créneau est un tableau associatif contenant :
     * - key: string (20 chars alphanum)
     * - start: string (HH:MM)
     * - end: string (HH:MM)
     * - prestations: array of integer ids
     */
    #[ORM\Column(type: 'json')]
    private array $creneaux = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDate(): \DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): self
    {
        // Normalize to date-only by setting time to 00:00:00 on immutable instances
        if ($date instanceof \DateTimeImmutable) {
            $this->date = $date->setTime(0, 0, 0);
        } else {
            $this->date = (clone $date)->setTime(0, 0, 0);
        }

        return $this;
    }

    public function getCreneaux(): array
    {
        return $this->creneaux;
    }

    public function setCreneaux(array $creneaux): self
    {
        $this->creneaux = $creneaux;

        return $this;
    }

    public function addCreneau(array $creneau): self
    {
        $this->creneaux[] = $creneau;
        return $this;
    }
}
