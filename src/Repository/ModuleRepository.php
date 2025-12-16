<?php

namespace App\Repository;

use App\Entity\Module;
use App\Service\EntityManagerProvider;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<Module>
 */
class ModuleRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry, EntityManagerProvider $entityManagerProvider)
    {
        parent::__construct($registry, Module::class, $entityManagerProvider);
    }
    public function add(Module $entity, bool $flush = false): void
    {
        $this->saveInEnvironment($entity, $flush);
    }

    public function remove(Module $entity, bool $flush = false): void
    {
        $this->removeInEnvironment($entity, $flush);
    }
    //    /**
    //     * @return Module[] Returns an array of Module objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilderForEnvironment('m')
    //            ->andWhere('m.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('m.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Module
    //    {
    //        return $this->createQueryBuilderForEnvironment('m')
    //            ->andWhere('m.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
