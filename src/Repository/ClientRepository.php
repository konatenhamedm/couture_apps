<?php

namespace App\Repository;

use App\Entity\Client;
use App\Service\EntityManagerProvider;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<Client>
 */
class ClientRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry, EntityManagerProvider $entityManagerProvider)
    {
        parent::__construct($registry, Client::class, $entityManagerProvider);
    }
    public function add(Client $entity, bool $flush = false): void
    {
        $this->saveInEnvironment($entity, $flush);
    }

    public function remove(Client $entity, bool $flush = false): void
    {
        $this->removeInEnvironment($entity, $flush);
    }

    public function countNewClients(\DateTime $debut, \DateTime $fin): int
    {
        return $this->createQueryBuilderForEnvironment('c')
            ->select('COUNT(c.id)')
            ->where('c.createdAt BETWEEN :debut AND :fin')
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Compte les clients actifs pour une entreprise dans une période
     */
    public function countActiveByPeriod($entreprise, \DateTime $dateDebut, \DateTime $dateFin): int
    {
        $dateDebutImmutable = \DateTimeImmutable::createFromMutable($dateDebut);
        $dateFinImmutable = \DateTimeImmutable::createFromMutable($dateFin);

       
        
        return $this->createQueryBuilderForEnvironment('c')
            ->select('COUNT(DISTINCT c.id)')
            ->where('c.createdAt >= :dateDebut')
            ->andWhere('c.createdAt <= :dateFin')
            ->andWhere('c.entreprise = :entreprise')
            ->andWhere('c.isActive = :isActive')
            ->setParameter('dateDebut', $dateDebutImmutable)
            ->setParameter('dateFin', $dateFinImmutable)
            ->setParameter('entreprise', $entreprise)
            ->setParameter('isActive', 1)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Compte les clients actifs pour une boutique dans une période
     */
    public function countActiveByBoutiqueAndPeriod($boutique, \DateTime $dateDebut, \DateTime $dateFin): int
    {
        $dateDebutImmutable = \DateTimeImmutable::createFromMutable($dateDebut);
        $dateFinImmutable = \DateTimeImmutable::createFromMutable($dateFin);
        
        return $this->createQueryBuilderForEnvironment('c')
            ->select('COUNT(DISTINCT c.id)')
            ->leftJoin('c.reservations', 'r')
            ->leftJoin('c.paiementBoutiques', 'pb')
            ->where('(r.boutique = :boutique AND r.createdAt >= :dateDebut AND r.createdAt <= :dateFin) OR (pb.boutique = :boutique AND pb.createdAt >= :dateDebut AND pb.createdAt <= :dateFin)')
            ->setParameter('boutique', $boutique)
            ->setParameter('dateDebut', $dateDebutImmutable)
            ->setParameter('dateFin', $dateFinImmutable)
            ->getQuery()
            ->getSingleScalarResult();
    }

    //    /**
    //     * @return Client[] Returns an array of Client objects
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

    //    public function findOneBySomeField($value): ?Client
    //    {
    //        return $this->createQueryBuilderForEnvironment('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
