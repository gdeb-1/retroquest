<?php

namespace App\Repository;

use App\Entity\CollectionItem;
use App\Entity\Game;
use App\Entity\User;
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
}
