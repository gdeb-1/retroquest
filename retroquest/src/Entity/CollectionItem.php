<?php

namespace App\Entity;

use App\Enum\Currency;
use App\Enum\CollectionItemStates;
use App\Repository\CollectionItemRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CollectionItemRepository::class)]
class CollectionItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type:'string', enumType: CollectionItemStates::class, length: 255)]
    private ?CollectionItemStates $state = null;

    #[ORM\Column]
    private ?int $acquisitionPrice = null;

    #[ORM\Column(type:'string', enumType: Currency::class, length: 255)]
    private ?Currency $currency = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $acquisitionDate = null;

    #[ORM\ManyToOne(inversedBy: 'collectionItems')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $collector = null;

    #[ORM\ManyToOne(inversedBy: 'collectionItems')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Game $game = null;

    /**
     * @var Collection<int, Exchange>
     */
    #[ORM\ManyToMany(targetEntity: Exchange::class, mappedBy: 'items')]
    private Collection $exchanges;

    public function __construct()
    {
        $this->exchanges = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getState(): ?CollectionItemStates
    {
        return $this->state;
    }

    public function setState(CollectionItemStates $state): static
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

    public function getCurrency(): ?Currency
    {
        return $this->currency;
    }

    public function setCurrency(Currency $currency): static
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

    public function getCollector(): ?User
    {
        return $this->collector;
    }

    public function setCollector(?User $collector): static
    {
        $this->collector = $collector;

        return $this;
    }

    public function getGame(): ?Game
    {
        return $this->game;
    }

    public function setGame(?Game $game): static
    {
        $this->game = $game;

        return $this;
    }

    /**
     * @return Collection<int, Exchange>
     */
    public function getExchanges(): Collection
    {
        return $this->exchanges;
    }

    public function addExchange(Exchange $exchange): static
    {
        if (!$this->exchanges->contains($exchange)) {
            $this->exchanges->add($exchange);
            $exchange->addItem($this);
        }

        return $this;
    }

    public function removeExchange(Exchange $exchange): static
    {
        if ($this->exchanges->removeElement($exchange)) {
            $exchange->removeItem($this);
        }

        return $this;
    }
}
