<?php

namespace App\Entity;

use App\Repository\ExchangeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ExchangeRepository::class)]
class Exchange
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $status = null;

    #[ORM\Column]
    private ?\DateTime $propositionDate = null;

    #[ORM\ManyToOne(inversedBy: 'proposedExchanges')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $proposer = null;

    #[ORM\ManyToOne(inversedBy: 'receivedExchanges')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $receiver = null;

    /**
     * @var Collection<int, CollectionItem>
     */
    #[ORM\ManyToMany(targetEntity: CollectionItem::class, inversedBy: 'exchanges')]
    private Collection $items;

    public function __construct()
    {
        $this->items = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getPropositionDate(): ?\DateTime
    {
        return $this->propositionDate;
    }

    public function setPropositionDate(\DateTime $propositionDate): static
    {
        $this->propositionDate = $propositionDate;

        return $this;
    }

    public function getProposer(): ?User
    {
        return $this->proposer;
    }

    public function setProposer(?User $proposer): static
    {
        $this->proposer = $proposer;

        return $this;
    }

    public function getReceiver(): ?User
    {
        return $this->receiver;
    }

    public function setReceiver(?User $receiver): static
    {
        $this->receiver = $receiver;

        return $this;
    }

    /**
     * @return Collection<int, CollectionItem>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(CollectionItem $item): static
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
        }

        return $this;
    }

    public function removeItem(CollectionItem $item): static
    {
        $this->items->removeElement($item);

        return $this;
    }
}
