<?php

namespace App\Repository;

use App\Entity\Operateur;
use App\Service\EntityManagerProvider;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\Constraint\Operator;

/**
 * @extends BaseRepository<Operateur>
 */
class OperateurRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry, EntityManagerProvider $entityManagerProvider)
    {
        parent::__construct($registry, Operateur::class, $entityManagerProvider);
    }
    public function add(Operateur $entity, bool $flush = false): void
    {
        $this->saveInEnvironment($entity, $flush);
    }

    public function remove(Operateur $entity, bool $flush = false): void
    {
        $this->removeInEnvironment($entity, $flush);
    }
    //    /**
    //     * @return Operateur[] Returns an array of Operateur objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilderForEnvironment('o')
    //            ->andWhere('o.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('o.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Operateur
    //    {
    //        return $this->createQueryBuilderForEnvironment('o')
    //            ->andWhere('o.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
