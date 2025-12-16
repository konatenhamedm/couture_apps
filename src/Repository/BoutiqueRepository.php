<?php

namespace App\Repository;

use App\Entity\Boutique;
use App\Service\EntityManagerProvider;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<Boutique>
 */
class BoutiqueRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry, EntityManagerProvider $entityManagerProvider)
    {
        parent::__construct($registry, Boutique::class, $entityManagerProvider);
    }
    public function add(Boutique $entity, bool $flush = false): void
    {
        $this->saveInEnvironment($entity, $flush);
    }

    public function remove(Boutique $entity, bool $flush = false): void
    {
        $this->removeInEnvironment($entity, $flush);
    }
    public function countActiveByEntreprise($entreprise): int
    {
        return $this->createQueryBuilderForEnvironment('b')
            ->select('COUNT(b.id)')
            ->where('b.isActive = :active')
            ->andWhere('b.entreprise = :entreprise')
            ->setParameter('active', true)
            ->setParameter('entreprise', $entreprise)
            ->getQuery()
            ->getSingleScalarResult();
    }
    //    /**
    //     * @return Boutique[] Returns an array of Boutique objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('b')
    //            ->andWhere('b.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('b.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Boutique
    //    {
    //        return $this->createQueryBuilder('b')
    //            ->andWhere('b.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
