<?php

namespace App\Repository;

use App\Entity\PaiementBoutiqueLigne;
use App\Service\EntityManagerProvider;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<PaiementBoutiqueLigne>
 */
class PaiementBoutiqueLigneRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry, EntityManagerProvider $entityManagerProvider)
    {
        parent::__construct($registry, PaiementBoutiqueLigne::class, $entityManagerProvider);
    }


        public function add(PaiementBoutiqueLigne $entity, bool $flush = false): void
    {
        $this->saveInEnvironment($entity, $flush);
    }

    public function remove(PaiementBoutiqueLigne $entity, bool $flush = false): void
    {
        $this->removeInEnvironment($entity, $flush);
    }

    //    /**
    //     * @return PaiementBoutiqueLigne[] Returns an array of PaiementBoutiqueLigne objects
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

    //    public function findOneBySomeField($value): ?PaiementBoutiqueLigne
    //    {
    //        return $this->createQueryBuilderForEnvironment('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
