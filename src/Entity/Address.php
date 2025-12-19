<?php

namespace App\Entity;

use App\Repository\AddressRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AddressRepository::class)]
class Address
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 5)]
    #[Assert\NotBlank(message: 'Le numéro de rue est obligatoire')]
    #[Assert\Length(max: 5, maxMessage: 'Le numéro ne peut pas dépasser {{ limit }} caractères')]
    private ?string $streetNumber = null;

    #[ORM\Column(length: 90)]
    #[Assert\NotBlank(message: 'La voie est obligatoire')]
    #[Assert\Length(max: 90, maxMessage: 'La voie ne peut pas dépasser {{ limit }} caractères')]
    private ?string $street = null;

    #[ORM\Column(length: 90, nullable: true)]
    #[Assert\Length(max: 90, maxMessage: 'Le complément ne peut pas dépasser {{ limit }} caractères')]
    private ?string $complement = null;

    #[ORM\Column(length: 5)]
    #[Assert\NotBlank(message: 'Le code postal est obligatoire')]
    #[Assert\Length(min: 5, max: 5, exactMessage: 'Le code postal doit contenir exactement {{ limit }} caractères')]
    #[Assert\Regex(pattern: '/^[0-9]{5}$/', message: 'Le code postal doit contenir 5 chiffres')]
    private ?string $postalCode = null;

    #[ORM\Column(length: 30)]
    #[Assert\NotBlank(message: 'La ville est obligatoire')]
    #[Assert\Length(max: 30, maxMessage: 'La ville ne peut pas dépasser {{ limit }} caractères')]
    private ?string $city = null;

    #[ORM\Column]
    private ?bool $isDefault = false;

    #[ORM\ManyToOne(inversedBy: 'addresses')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStreetNumber(): ?string
    {
        return $this->streetNumber;
    }

    public function setStreetNumber(string $streetNumber): static
    {
        $this->streetNumber = $streetNumber;

        return $this;
    }

    public function getStreet(): ?string
    {
        return $this->street;
    }

    public function setStreet(string $street): static
    {
        $this->street = $street;

        return $this;
    }

    public function getComplement(): ?string
    {
        return $this->complement;
    }

    public function setComplement(?string $complement): static
    {
        $this->complement = $complement;

        return $this;
    }

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function setPostalCode(string $postalCode): static
    {
        $this->postalCode = $postalCode;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(string $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function isDefault(): ?bool
    {
        return $this->isDefault;
    }

    public function setIsDefault(bool $isDefault): static
    {
        $this->isDefault = $isDefault;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getFullAddress(): string
    {
        $address = $this->streetNumber . ' ' . $this->street;
        if ($this->complement) {
            $address .= ', ' . $this->complement;
        }
        $address .= ', ' . $this->postalCode . ' ' . $this->city;
        
        return $address;
    }
}
