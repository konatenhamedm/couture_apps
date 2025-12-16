<?php

namespace App\Repository;

use App\Entity\TypeUser;
use App\Service\EntityManagerProvider;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<TypeUser>
 */
class TypeUserRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry, EntityManagerProvider $entityManagerProvider)
    {
        parent::__construct($registry, TypeUser::class, $entityManagerProvider);
    }
    public function add(TypeUser $entity, bool $flush = false): void
    {   
        $this->saveInEnvironment($entity, $flush);
    }

    public function getTypeWithoutUser(){
        return $this->createQueryBuilderForEnvironment('t')
            ->where('t.code != :code')
            ->setParameter('code', 'SADM')
            ->getQuery()
            ->getResult();
    }

    public function remove(TypeUser $entity, bool $flush = false): void
    {
        $this->removeInEnvironment($entity, $flush);
    }
    //    /**
    //     * @return TypeUser[] Returns an array of TypeUser objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilderForEnvironment('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('t.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?TypeUser
    //    {
    //        return $this->createQueryBuilderForEnvironment('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
