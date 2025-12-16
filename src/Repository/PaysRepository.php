<?php

namespace App\Repository;

use App\Entity\Pays;
use App\Service\EntityManagerProvider;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<Pays>
 */
class PaysRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry, EntityManagerProvider $entityManagerProvider)
    {
        parent::__construct($registry, Pays::class, $entityManagerProvider);
    }
    public function add(Pays $entity, bool $flush = false): void
    {
        $this->saveInEnvironment($entity, $flush);
    }

    public function remove(Pays $entity, bool $flush = false): void
    {
        $this->removeInEnvironment($entity, $flush);
    }

    /**
     * Trouve tous les pays actifs dans l'environnement actuel
     */
    public function findActivePays(): array
    {
        return $this->findByInEnvironment(['isActive' => true], ['libelle' => 'ASC']);
    }

    /**
     * Trouve un pays par son code dans l'environnement actuel
     */
    public function findByCode(string $code): ?Pays
    {
        return $this->findOneByInEnvironment(['code' => $code]);
    }
    //    /**
    //     * @return Pays[] Returns an array of Pays objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Pays
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
