<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class UnavailabilityRule
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    // Store an RRULE string or custom JSON describing recurrence
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $recurrence = null;

    // Optional time range for this rule (null = whole day)
    #[ORM\Column(type: 'time', nullable: true)]
    private ?\DateTimeInterface $timeStart = null;

    #[ORM\Column(type: 'time', nullable: true)]
    private ?\DateTimeInterface $timeEnd = null;

    #[ORM\Column(type: 'boolean')]
    private bool $active = true;

    public function getId(): ?int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function setName(string $n): self { $this->name = $n; return $this; }

    public function getRecurrence(): ?string { return $this->recurrence; }
    public function setRecurrence(?string $r): self { $this->recurrence = $r; return $this; }

    public function getTimeStart(): ?\DateTimeInterface { return $this->timeStart; }
    public function setTimeStart(?\DateTimeInterface $t): self { $this->timeStart = $t; return $this; }

    public function getTimeEnd(): ?\DateTimeInterface { return $this->timeEnd; }
    public function setTimeEnd(?\DateTimeInterface $t): self { $this->timeEnd = $t; return $this; }

    public function isActive(): bool { return $this->active; }
    public function setActive(bool $b): self { $this->active = $b; return $this; }

    public function getRecurrenceData(): array
    {
        if (!$this->recurrence) {
            return [];
        }
        return json_decode($this->recurrence, true) ?? [];
    }

    public function getRecurrenceType(): ?string
    {
        return $this->getRecurrenceData()['type'] ?? null;
    }

    public function getStartDate(): ?\DateTimeImmutable
    {
        $d = $this->getRecurrenceData()['startDate'] ?? null;
        return $d ? new \DateTimeImmutable($d) : null;
    }

    public function getEndDate(): ?\DateTimeImmutable
    {
        $d = $this->getRecurrenceData()['endDate'] ?? null;
        return $d ? new \DateTimeImmutable($d) : null;
    }

    public function getDaysOfWeek(): array
    {
        return $this->getRecurrenceData()['daysOfWeek'] ?? [];
    }

    private function updateRecurrenceKey(string $key, mixed $value): void
    {
        $data = $this->getRecurrenceData();
        if ($value === null) {
            unset($data[$key]);
        } else {
            $data[$key] = $value;
        }
        $this->recurrence = json_encode($data);
    }

    public function setRecurrenceType(?string $type): self
    {
        $this->updateRecurrenceKey('type', $type);
        return $this;
    }

    public function setStartDate(?\DateTimeInterface $date): self
    {
        $this->updateRecurrenceKey('startDate', $date ? $date->format('Y-m-d') : null);
        return $this;
    }

    public function setEndDate(?\DateTimeInterface $date): self
    {
        $this->updateRecurrenceKey('endDate', $date ? $date->format('Y-m-d') : null);
        return $this;
    }

    public function setDaysOfWeek(array $days): self
    {
        $this->updateRecurrenceKey('daysOfWeek', $days);
        return $this;
    }
}
