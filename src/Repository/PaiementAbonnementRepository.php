<?php

namespace App\Repository;

use App\Entity\PaiementAbonnement;
use App\Service\EntityManagerProvider;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<PaiementAbonnement>
 */
class PaiementAbonnementRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry, EntityManagerProvider $entityManagerProvider)
    {
        parent::__construct($registry, PaiementAbonnement::class, $entityManagerProvider);
    }

    public function add(PaiementAbonnement $entity, bool $flush = false): void
    {
        $this->saveInEnvironment($entity, $flush);
    }

    public function remove(PaiementAbonnement $entity, bool $flush = false): void
    {
        $this->removeInEnvironment($entity, $flush);
    }

    //    /**
    //     * @return PaiementAbonnement[] Returns an array of PaiementAbonnement objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilderForEnvironment('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?PaiementAbonnement
    //    {
    //        return $this->createQueryBuilderForEnvironment('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
