<?php

namespace App\Repository;

use App\Entity\Abonnement;
use App\Entity\Entreprise;
use App\Service\EntityManagerProvider;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<Abonnement>
 */
class AbonnementRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry, EntityManagerProvider $entityManagerProvider)
    {
        parent::__construct($registry, Abonnement::class, $entityManagerProvider);
    }

    public function add(Abonnement $entity, bool $flush = false): void
    {
        $this->saveInEnvironment($entity, $flush);
    }

    public function remove(Abonnement $entity, bool $flush = false): void
    {
        $this->removeInEnvironment($entity, $flush);
    }

    public function findActiveForEntreprise(Entreprise $entreprise): ?Abonnement
    {
        return $this->createQueryBuilderForEnvironment('a')
            ->andWhere('a.entreprise = :entreprise')
            ->andWhere('a.etat = :etat')
            ->andWhere('a.dateFin >= :now')
            ->setParameter('entreprise', $entreprise)
            ->setParameter('etat', true)
            ->setParameter('now', new \DateTime())
            ->orderBy('a.dateFin', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /*         public function findLastTransactionByUser($userId): ?Abonnement
    {
        return $this->createQueryBuilderForEnvironment('t')
            ->andWhere('t.user = :userId')
            ->andWhere('t.type = :state')
            ->setParameter('state', "NOUVELLE DEMANDE")
            ->setParameter('userId', $userId)
            ->orderBy('t.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    } */

    public function findInactiveForEntreprise(Entreprise $entreprise): ?Abonnement
    {
        return $this->createQueryBuilderForEnvironment('a')
            ->andWhere('a.entreprise = :entreprise')
            ->andWhere('a.etat = :etat')
            ->andWhere('a.dateFin >= :now')
            ->setParameter('entreprise', $entreprise)
            ->setParameter('etat', true)
            ->setParameter('now', new \DateTime())
            ->orderBy('a.dateFin', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    //    /**
    //     * @return Abonnement[] Returns an array of Abonnement objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilderForEnvironment('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('a.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Abonnement
    //    {
    //        return $this->createQueryBuilderForEnvironment('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
