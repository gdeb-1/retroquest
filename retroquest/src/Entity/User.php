<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    /**
     * @var Collection<int, CollectionItem>
     */
    #[ORM\OneToMany(targetEntity: CollectionItem::class, mappedBy: 'collector', orphanRemoval: true)]
    private Collection $collectionItems;

    /**
     * @var Collection<int, Review>
     */
    #[ORM\OneToMany(targetEntity: Review::class, mappedBy: 'author', orphanRemoval: true)]
    private Collection $reviews;

    /**
     * @var Collection<int, Exchange>
     */
    #[ORM\OneToMany(targetEntity: Exchange::class, mappedBy: 'proposer', orphanRemoval: true)]
    private Collection $proposedExchanges;

    /**
     * @var Collection<int, Exchange>
     */
    #[ORM\OneToMany(targetEntity: Exchange::class, mappedBy: 'receiver', orphanRemoval: true)]
    private Collection $receivedExchanges;

    public function __construct()
    {
        $this->collectionItems = new ArrayCollection();
        $this->reviews = new ArrayCollection();
        $this->proposedExchanges = new ArrayCollection();
        $this->receivedExchanges = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Ensure the session doesn't contain actual password hashes by CRC32C-hashing them, as supported since Symfony 7.3.
     */
    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0".self::class."\0password"] = hash('crc32c', $this->password);

        return $data;
    }

    #[\Deprecated]
    public function eraseCredentials(): void
    {
        // @deprecated, to be removed when upgrading to Symfony 8
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
            $collectionItem->setCollector($this);
        }

        return $this;
    }

    public function removeCollectionItem(CollectionItem $collectionItem): static
    {
        if ($this->collectionItems->removeElement($collectionItem)) {
            // set the owning side to null (unless already changed)
            if ($collectionItem->getCollector() === $this) {
                $collectionItem->setCollector(null);
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
            $review->setAuthor($this);
        }

        return $this;
    }

    public function removeReview(Review $review): static
    {
        if ($this->reviews->removeElement($review)) {
            // set the owning side to null (unless already changed)
            if ($review->getAuthor() === $this) {
                $review->setAuthor(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Exchange>
     */
    public function getProposedExchanges(): Collection
    {
        return $this->proposedExchanges;
    }

    public function addProposedExchange(Exchange $proposedExchange): static
    {
        if (!$this->proposedExchanges->contains($proposedExchange)) {
            $this->proposedExchanges->add($proposedExchange);
            $proposedExchange->setProposer($this);
        }

        return $this;
    }

    public function removeProposedExchange(Exchange $proposedExchange): static
    {
        if ($this->proposedExchanges->removeElement($proposedExchange)) {
            // set the owning side to null (unless already changed)
            if ($proposedExchange->getProposer() === $this) {
                $proposedExchange->setProposer(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Exchange>
     */
    public function getReceivedExchanges(): Collection
    {
        return $this->receivedExchanges;
    }

    public function addReceivedExchange(Exchange $receivedExchange): static
    {
        if (!$this->receivedExchanges->contains($receivedExchange)) {
            $this->receivedExchanges->add($receivedExchange);
            $receivedExchange->setReceiver($this);
        }

        return $this;
    }

    public function removeReceivedExchange(Exchange $receivedExchange): static
    {
        if ($this->receivedExchanges->removeElement($receivedExchange)) {
            // set the owning side to null (unless already changed)
            if ($receivedExchange->getReceiver() === $this) {
                $receivedExchange->setReceiver(null);
            }
        }

        return $this;
    }
}
