<?php

namespace App\Repository;

use App\Entity\CaisseSuccursale;
use App\Service\EntityManagerProvider;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<CaisseSuccursale>
 */
class CaisseSuccursaleRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry, EntityManagerProvider $entityManagerProvider)
    {
        parent::__construct($registry, CaisseSuccursale::class, $entityManagerProvider);
    }

    public function add(CaisseSuccursale $entity, bool $flush = false): void
    {
        $this->saveInEnvironment($entity, $flush);
    }

    public function remove(CaisseSuccursale $entity, bool $flush = false): void
    {
        $this->removeInEnvironment($entity, $flush);
    }

    //    /**
    //     * @return CaisseSuccursale[] Returns an array of CaisseSuccursale objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilderForEnvironment('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?CaisseSuccursale
    //    {
    //        return $this->createQueryBuilderForEnvironment('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
