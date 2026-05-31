<?php

namespace App\Repository;

use App\Entity\CollectionItem;
use App\Entity\Game;
use App\Entity\User;
use App\Enum\ExchangeStatuses;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CollectionItem>
 */
class CollectionItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CollectionItem::class);
    }

    /**
     * @param Game[] $games
     * @return array
     */
    public function findAveragePricesForGames(array $games): array
    {
        if (empty($games)) {
            return [];
        }

        return $this->createQueryBuilder('c')
            ->select('IDENTITY(c.game) as gameId', 'c.currency as currency', 'AVG(c.acquisitionPrice) as averagePrice')
            ->andWhere('c.game IN (:games)')
            ->setParameter('games', $games)
            ->groupBy('c.game', 'c.currency')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return CollectionItem[]
     */
    public function findAllWithGameAndCollector(): array
    {
        return $this->createQueryBuilder('c')
            ->select('c', 'g', 'col')
            ->join('c.game', 'g')
            ->join('c.collector', 'col')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return CollectionItem[]
     */
    public function findByCollectorWithGame(User $collector): array
    {
        return $this->createQueryBuilder('c')
            ->select('c', 'g')
            ->join('c.game', 'g')
            ->andWhere('c.collector = :collector')
            ->setParameter('collector', $collector)
            ->getQuery()
            ->getResult();
    }

    /**
     * Finds collection items available for exchange, excluding those of the current user.
     *
     * An item is available if it is not associated with another active exchange (PENDING or ACCEPTED)
     * and the game is not hidden.
     *
     * @return CollectionItem[]
     */
    public function findAvailableForExchange(User $currentUser): array
    {
        return $this->createQueryBuilder('c')
            ->select('c', 'g', 'col')
            ->join('c.game', 'g')
            ->join('c.collector', 'col')
            ->andWhere('c.collector != :currentUser')
            ->andWhere('g.isHidden = false')
            ->setParameter('currentUser', $currentUser)
            ->getQuery()
            ->getResult();
    }

    public function countAvailableForExchange(User $currentUser): int
    {
        return $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->join('c.game', 'g')
            ->join('c.collector', 'col')            ->andWhere('c.collector != :currentUser')
            ->andWhere('g.isHidden = false')
            ->setParameter('currentUser', $currentUser)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return CollectionItem[]
     */
    public function findAvailableForExchangeByCollector(User $collector): array
    {
        return $this->createQueryBuilder('ci')
            ->select('ci', 'g')
            ->join('ci.game', 'g')
            ->leftJoin('ci.exchanges', 'e', 'WITH', 'e.status IN (:activeStatuses)')
            ->andWhere('ci.collector = :collector')
            ->andWhere('g.isHidden = false')
            ->andWhere('e.id IS NULL')
            ->setParameter('activeStatuses', [
                ExchangeStatuses::PENDING
            ])
            ->setParameter('collector', $collector)
            ->getQuery()
            ->getResult();
    }
}

