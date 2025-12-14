<?php

namespace App\Repository;

use App\Entity\Reservation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Reservation>
 */
class ReservationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reservation::class);
    }

    public function add(Reservation $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Reservation $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
    public function getEvolutionCommandes(\DateTime $debut, \DateTime $fin, string $groupBy = 'jour'): array
    {
        $format = match ($groupBy) {
            'jour' => '%Y-%m-%d',
            'semaine' => '%Y-%u',
            'mois' => '%Y-%m',
            default => '%Y-%m-%d'
        };

        $sql = "SELECT DATE_FORMAT(created_at, ?) as periode, COUNT(id) as nombre 
                FROM reservation 
                WHERE created_at BETWEEN ? AND ? 
                GROUP BY periode 
                ORDER BY periode ASC";

        $conn = $this->getEntityManager()->getConnection();
        return $conn->executeQuery($sql, [$format, $debut->format('Y-m-d H:i:s'), $fin->format('Y-m-d H:i:s')])->fetchAllAssociative();
    }

    public function countByDateRange(\DateTime $debut, \DateTime $fin): int
    {
        return $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.createdAt BETWEEN :debut AND :fin')
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Compte les réservations actives pour une entreprise dans une période
     */
    public function countActiveByEntrepriseAndPeriod($entreprise, \DateTime $dateDebut, \DateTime $dateFin): int
    {
        return $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.entreprise = :entreprise')
            ->andWhere('r.createdAt BETWEEN :dateDebut AND :dateFin')
            ->andWhere('r.isActive = true')
            ->setParameter('entreprise', $entreprise)
            ->setParameter('dateDebut', $dateDebut)
            ->setParameter('dateFin', $dateFin)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Compte les réservations actives pour une boutique dans une période
     */
    public function countActiveByBoutiqueAndPeriod($boutique, \DateTime $dateDebut, \DateTime $dateFin): int
    {
        return $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.boutique = :boutique')
            ->andWhere('r.createdAt BETWEEN :dateDebut AND :dateFin')
            ->andWhere('r.isActive = true')
            ->setParameter('boutique', $boutique)
            ->setParameter('dateDebut', $dateDebut)
            ->setParameter('dateFin', $dateFin)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Compte les commandes en cours pour une entreprise
     */
    public function countCommandesEnCoursByEntreprise($entreprise): int
    {
        return $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.entreprise = :entreprise')
            ->andWhere('r.dateRetrait > :now')
            ->andWhere('r.isActive = true')
            ->setParameter('entreprise', $entreprise)
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Compte les commandes en cours pour une boutique
     */
    public function countCommandesEnCoursByBoutique($boutique): int
    {
        return $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.boutique = :boutique')
            ->andWhere('r.dateRetrait > :now')
            ->andWhere('r.isActive = true')
            ->setParameter('boutique', $boutique)
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Compte les réservations par jour pour une entreprise
     */
    public function countByEntrepriseAndDay($entreprise, \DateTime $date): int
    {
        $nextDay = clone $date;
        $nextDay->add(new \DateInterval('P1D'));

        return $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.entreprise = :entreprise')
            ->andWhere('r.createdAt >= :date')
            ->andWhere('r.createdAt < :nextDay')
            ->setParameter('entreprise', $entreprise)
            ->setParameter('date', $date)
            ->setParameter('nextDay', $nextDay)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Compte les réservations par jour pour une boutique
     */
    public function countByBoutiqueAndDay($boutique, \DateTime $date): int
    {
        $nextDay = clone $date;
        $nextDay->add(new \DateInterval('P1D'));

        return $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.boutique = :boutique')
            ->andWhere('r.createdAt >= :date')
            ->andWhere('r.createdAt < :nextDay')
            ->setParameter('boutique', $boutique)
            ->setParameter('date', $date)
            ->setParameter('nextDay', $nextDay)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Récupère les dernières réservations pour une entreprise
     */
    public function findLatestByEntreprise($entreprise, int $limit = 5): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.entreprise = :entreprise')
            ->andWhere('r.isActive = true')
            ->orderBy('r.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setParameter('entreprise', $entreprise)
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les dernières réservations pour une boutique
     */
    public function findLatestByBoutique($boutique, int $limit = 5): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.boutique = :boutique')
            ->andWhere('r.isActive = true')
            ->orderBy('r.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setParameter('boutique', $boutique)
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return Reservation[] Returns an array of Reservation objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('r.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Reservation
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
