<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Creneau
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'date')]
    private \DateTimeInterface $date;

    #[ORM\Column(type: 'time')]
    private \DateTimeInterface $startTime;

    #[ORM\Column(type: 'time')]
    private \DateTimeInterface $endTime;

    #[ORM\Column(type: 'string', length: 20, unique: true)]
    private string $slotKey;

    #[ORM\Column(type: 'boolean')]
    private bool $isBlocked = false;

    #[ORM\Column(type: 'integer')]
    private int $capacity = 1;

    public function getId(): ?int { return $this->id; }

    public function getDate(): \DateTimeInterface { return $this->date; }
    public function setDate(\DateTimeInterface $date): self { $this->date = $date; return $this; }

    public function getStartTime(): \DateTimeInterface { return $this->startTime; }
    public function setStartTime(\DateTimeInterface $t): self { $this->startTime = $t; return $this; }

    public function getEndTime(): \DateTimeInterface { return $this->endTime; }
    public function setEndTime(\DateTimeInterface $t): self { $this->endTime = $t; return $this; }

    public function getSlotKey(): string { return $this->slotKey; }
    public function setSlotKey(string $k): self { $this->slotKey = $k; return $this; }

    public function isBlocked(): bool { return $this->isBlocked; }
    public function setIsBlocked(bool $b): self { $this->isBlocked = $b; return $this; }

    public function getCapacity(): int { return $this->capacity; }
    public function setCapacity(int $c): self { $this->capacity = $c; return $this; }
}
