<?php

namespace App\Repository;

use App\Entity\Exchange;
use App\Entity\CollectionItem;
use App\Entity\User;
use App\Enum\ExchangeStatuses;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Exchange>
 */
class ExchangeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Exchange::class);
    }

    /**
     * @return Exchange[]
     */
    public function findPendingExchangesForCollectionItemBetweenUsers(CollectionItem $item, User $user1, User $user2): array
    {
        return $this->createQueryBuilder('e')
            ->join('e.items', 'i')
            ->andWhere('e.status = :status')
            ->andWhere('i = :item')
            ->andWhere(
                '(e.proposer = :user1 AND e.receiver = :user2) OR (e.proposer = :user2 AND e.receiver = :user1)'
            )
            ->setParameter('status', ExchangeStatuses::PENDING)
            ->setParameter('item', $item)
            ->setParameter('user1', $user1)
            ->setParameter('user2', $user2)
            ->orderBy('e.propositionDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Exchange[]
     */
    public function findSentExchanges(User $proposer): array
    {
        return $this->createQueryBuilder('e')
            ->leftJoin('e.items', 'i')
            ->leftJoin('i.game', 'g')
            ->leftJoin('i.collector', 'c')
            ->andWhere('e.proposer = :proposer')
            ->setParameter('proposer', $proposer)
            ->orderBy('e.propositionDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Exchange[]
     */
    public function findReceivedExchanges(User $receiver): array
    {
        return $this->createQueryBuilder('e')
            ->leftJoin('e.items', 'i')
            ->leftJoin('i.game', 'g')
            ->leftJoin('i.collector', 'c')
            ->andWhere('e.receiver = :receiver')
            ->setParameter('receiver', $receiver)
            ->orderBy('e.propositionDate', 'DESC')
            ->getQuery()
            ->getResult();
    }




    //    /**
    //     * @return Exchange[] Returns an array of Exchange objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('e')
    //            ->andWhere('e.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('e.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Exchange
    //    {
    //        return $this->createQueryBuilder('e')
    //            ->andWhere('e.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
