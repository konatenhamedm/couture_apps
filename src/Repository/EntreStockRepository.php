<?php

namespace App\Repository;

use App\Entity\EntreStock;
use App\Service\EntityManagerProvider;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<EntreStock>
 */
class EntreStockRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry, EntityManagerProvider $entityManagerProvider)
    {
        parent::__construct($registry, EntreStock::class, $entityManagerProvider);
    }
    public function add(EntreStock $entity, bool $flush = false): void
    {
        $this->saveInEnvironment($entity, $flush);
    }

    public function remove(EntreStock $entity, bool $flush = false): void
    {
        $this->removeInEnvironment($entity, $flush);
    }
    //    /**
    //     * @return EntreStock[] Returns an array of EntreStock objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilderForEnvironment('e')
    //            ->andWhere('e.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('e.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?EntreStock
    //    {
    //        return $this->createQueryBuilderForEnvironment('e')
    //            ->andWhere('e.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
