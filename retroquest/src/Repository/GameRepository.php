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

    public function findVisibleGames(): \Doctrine\ORM\QueryBuilder
    {
        return $this->createQueryBuilder('g')
            ->where('g.isHidden = :hidden')
            ->setParameter('hidden', false)
            ->orderBy('g.title', 'ASC');
    }

    public function countVisibleGames(): int
    {
        return (int) $this->createQueryBuilder('g')
            ->select('COUNT(g.id)')
            ->where('g.isHidden = :hidden')
            ->setParameter('hidden', false)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countFilteredCatalog(?string $search): int
    {
        $qb = $this->createQueryBuilder('g')
            ->select('COUNT(g.id)')
            ->where('g.isHidden = :hidden')
            ->setParameter('hidden', false);

        if ($search !== null && $search !== '') {
            $orX = $qb->expr()->orX(
                $qb->expr()->like('g.title', ':search'),
                $qb->expr()->like('g.console', ':search')
            );
            if (is_numeric($search)) {
                $orX->add($qb->expr()->eq('g.releaseYear', ':searchYear'));
                $qb->setParameter('searchYear', (int)$search);
            }
            $qb->andWhere($orX);
            $qb->setParameter('search', '%' . $search . '%');
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function getPaginatedCatalog(
        int $offset,
        int $limit,
        ?string $search,
        ?string $sortColumn,
        ?string $sortDir
    ): array {
        $qb = $this->createQueryBuilder('g')
            ->where('g.isHidden = :hidden')
            ->setParameter('hidden', false);

        if ($search !== null && $search !== '') {
            $orX = $qb->expr()->orX(
                $qb->expr()->like('g.title', ':search'),
                $qb->expr()->like('g.console', ':search')
            );
            if (is_numeric($search)) {
                $orX->add($qb->expr()->eq('g.releaseYear', ':searchYear'));
                $qb->setParameter('searchYear', (int)$search);
            }
            $qb->andWhere($orX);
            $qb->setParameter('search', '%' . $search . '%');
        }

        $allowedSortColumns = [
            'title' => 'g.title',
            'console' => 'g.console',
            'releaseYear' => 'g.releaseYear',
        ];
        $orderBy = $allowedSortColumns[$sortColumn] ?? 'g.title';
        $orderDir = strtoupper($sortDir ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';

        $qb->orderBy($orderBy, $orderDir);
        $qb->setFirstResult($offset);
        $qb->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }
}

