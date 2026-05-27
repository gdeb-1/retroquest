<?php

namespace App\Entity;

use App\Repository\GameRepository;
use App\Enum\GameConsoles;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GameRepository::class)]
class Game
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column]
    private ?int $releaseYear = null;

    #[ORM\Column]
    private ?bool $isHidden = null;

    #[ORM\Column(type: 'string', enumType: GameConsoles::class, length: 255)]
    private ?GameConsoles $console = null;

    /**
     * @var Collection<int, CollectionItem>
     */
    #[ORM\OneToMany(targetEntity: CollectionItem::class, mappedBy: 'game', orphanRemoval: true)]
    private Collection $collectionItems;

    /**
     * @var Collection<int, Review>
     */
    #[ORM\OneToMany(targetEntity: Review::class, mappedBy: 'game', orphanRemoval: true)]
    private Collection $reviews;

    public function __construct()
    {
        $this->collectionItems = new ArrayCollection();
        $this->reviews = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getReleaseYear(): ?int
    {
        return $this->releaseYear;
    }

    public function setReleaseYear(int $releaseYear): static
    {
        $this->releaseYear = $releaseYear;

        return $this;
    }

    public function isHidden(): ?bool
    {
        return $this->isHidden;
    }

    public function setIsHidden(bool $isHidden): static
    {
        $this->isHidden = $isHidden;

        return $this;
    }

    public function getConsole(): ?GameConsoles
    {
        return $this->console;
    }

    public function setConsole(GameConsoles $console): static
    {
        $this->console = $console;

        return $this;
    }

    /**
     * @return Collection<int, CollectionItem>
     */
    public function getCollectionItems(): Collection
    {
        return $this->collectionItems;
    }

    public function addCollectionItem(CollectionItem $collectionItem): static
    {
        if (!$this->collectionItems->contains($collectionItem)) {
            $this->collectionItems->add($collectionItem);
            $collectionItem->setGame($this);
        }

        return $this;
    }

    public function removeCollectionItem(CollectionItem $collectionItem): static
    {
        if ($this->collectionItems->removeElement($collectionItem)) {
            // set the owning side to null (unless already changed)
            if ($collectionItem->getGame() === $this) {
                $collectionItem->setGame(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Review>
     */
    public function getReviews(): Collection
    {
        return $this->reviews;
    }

    public function addReview(Review $review): static
    {
        if (!$this->reviews->contains($review)) {
            $this->reviews->add($review);
            $review->setGame($this);
        }

        return $this;
    }

    public function removeReview(Review $review): static
    {
        if ($this->reviews->removeElement($review)) {
            // set the owning side to null (unless already changed)
            if ($review->getGame() === $this) {
                $review->setGame(null);
            }
        }

        return $this;
    }
}
