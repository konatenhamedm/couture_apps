<?php

namespace App\Repository;

use App\Entity\Fichier;
use App\Service\EntityManagerProvider;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<Fichier>
 *
 * @method Fichier|null find($id, $lockMode = null, $lockVersion = null)
 * @method Fichier|null findOneBy(array $criteria, array $orderBy = null)
 * @method Fichier[]    findAll()
 * @method Fichier[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FichierRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry, EntityManagerProvider $entityManagerProvider)
    {
        parent::__construct($registry, Fichier::class, $entityManagerProvider);
    }

    public function save(Fichier $entity, bool $flush = false): void
    {
        $this->saveInEnvironment($entity, $flush);
    }

    public function remove(Fichier $entity, bool $flush = false): void
    {
        $this->removeInEnvironment($entity, $flush);
    }

//    /**
//     * @return Fichier[] Returns an array of Fichier objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilderForEnvironment('f')
//            ->andWhere('f.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('f.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Fichier
//    {
//        return $this->createQueryBuilderForEnvironment('f')
//            ->andWhere('f.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
