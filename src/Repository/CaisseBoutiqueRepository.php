<?php

namespace App\Repository;

use App\Entity\CaisseBoutique;
use App\Service\EntityManagerProvider;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<CaisseBoutique>
 */
class CaisseBoutiqueRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry, EntityManagerProvider $entityManagerProvider)
    {
        parent::__construct($registry, CaisseBoutique::class, $entityManagerProvider);
    }

    public function add(CaisseBoutique $entity, bool $flush = false): void
    {
        $this->saveInEnvironment($entity, $flush);
    }

    public function remove(CaisseBoutique $entity, bool $flush = false): void
    {
        $this->removeInEnvironment($entity, $flush);
    }

    //    /**
    //     * @return CaisseBoutique[] Returns an array of CaisseBoutique objects
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

    //    public function findOneBySomeField($value): ?CaisseBoutique
    //    {
    //        return $this->createQueryBuilderForEnvironment('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
