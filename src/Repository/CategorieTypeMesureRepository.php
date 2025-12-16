<?php

namespace App\Repository;

use App\Entity\CategorieTypeMesure;
use App\Service\EntityManagerProvider;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<CategorieTypeMesure>
 */
class CategorieTypeMesureRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry, EntityManagerProvider $entityManagerProvider)
    {
        parent::__construct($registry, CategorieTypeMesure::class, $entityManagerProvider);
    }
    public function add(CategorieTypeMesure $entity, bool $flush = false): void
    {
        $this->saveInEnvironment($entity, $flush);
    }

    public function remove(CategorieTypeMesure $entity, bool $flush = false): void
    {
        $this->removeInEnvironment($entity, $flush);
    }
    //    /**
    //     * @return CategorieTypeMesure[] Returns an array of CategorieTypeMesure objects
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

    //    public function findOneBySomeField($value): ?CategorieTypeMesure
    //    {
    //        return $this->createQueryBuilderForEnvironment('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
