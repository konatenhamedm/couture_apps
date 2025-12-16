<?php

namespace App\Repository;

use App\Entity\Surccursale;
use App\Service\EntityManagerProvider;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<Surccursale>
 */
class SurccursaleRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry, EntityManagerProvider $entityManagerProvider)
    {
        parent::__construct($registry, Surccursale::class, $entityManagerProvider);
    }
    public function add(Surccursale $entity, bool $flush = false): void
    {
        $this->saveInEnvironment($entity, $flush);
    }

    public function remove(Surccursale $entity, bool $flush = false): void
    {
        $this->removeInEnvironment($entity, $flush);
    }

    public function countActiveByEntreprise($entreprise): int
    {
        return $this->createQueryBuilderForEnvironment('s')
            ->select('COUNT(s.id)')
            ->where('s.isActive = :active')
            ->andWhere('s.entreprise = :entreprise')
            ->setParameter('active', true)
            ->setParameter('entreprise', $entreprise)
            ->getQuery()
            ->getSingleScalarResult();
    }
    //    /**
    //     * @return Surccursale[] Returns an array of Surccursale objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilderForEnvironment('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('s.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Surccursale
    //    {
    //        return $this->createQueryBuilderForEnvironment('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
