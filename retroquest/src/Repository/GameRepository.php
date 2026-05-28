<?php

namespace App\Repository;

use App\Entity\Game;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Game>
 */
class GameRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Game::class);
    }

    /**
     * @return array
     */
    public function findPopularGames(int $days = 30, int $limit = 12): array
    {
        $dateLimit = new \DateTime(sprintf('-%d days', $days));

        return $this->createQueryBuilder('g')
            ->select('g', 'COUNT(c.id) as additionsCount')
            ->innerJoin('g.collectionItems', 'c')
            ->andWhere('c.acquisitionDate >= :dateLimit')
            ->andWhere('g.isHidden = :isHidden')
            ->setParameter('dateLimit', $dateLimit)
            ->setParameter('isHidden', false)
            ->groupBy('g.id')
            ->orderBy('additionsCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}

