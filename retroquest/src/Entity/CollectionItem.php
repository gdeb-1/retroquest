<?php

namespace App\Entity;

use App\Repository\CollectionItemRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CollectionItemRepository::class)]
class CollectionItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $state = null;

    #[ORM\Column]
    private ?int $acquisitionPrice = null;

    #[ORM\Column(length: 255)]
    private ?string $currency = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $acquisitionDate = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getState(): ?string
    {
        return $this->state;
    }

    public function setState(string $state): static
    {
        $this->state = $state;

        return $this;
    }

    public function getAcquisitionPrice(): ?int
    {
        return $this->acquisitionPrice;
    }

    public function setAcquisitionPrice(int $acquisitionPrice): static
    {
        $this->acquisitionPrice = $acquisitionPrice;

        return $this;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): static
    {
        $this->currency = $currency;

        return $this;
    }

    public function getAcquisitionDate(): ?\DateTime
    {
        return $this->acquisitionDate;
    }

    public function setAcquisitionDate(\DateTime $acquisitionDate): static
    {
        $this->acquisitionDate = $acquisitionDate;

        return $this;
    }
}
