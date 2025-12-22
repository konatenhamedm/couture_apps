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
        $dateDebutImmutable = \DateTimeImmutable::createFromMutable($dateDebut);
        $dateFinImmutable = \DateTimeImmutable::createFromMutable($dateFin);
        
        return $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.entreprise = :entreprise')
            ->andWhere('r.createdAt >= :dateDebut')
            ->andWhere('r.createdAt <= :dateFin')
            ->andWhere('r.isActive = true')
            ->setParameter('entreprise', $entreprise)
            ->setParameter('dateDebut', $dateDebutImmutable)
            ->setParameter('dateFin', $dateFinImmutable)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Compte les réservations actives pour une boutique dans une période
     */
    public function countActiveByBoutiqueAndPeriod($boutique, \DateTime $dateDebut, \DateTime $dateFin): int
    {
        $dateDebutImmutable = \DateTimeImmutable::createFromMutable($dateDebut);
        $dateFinImmutable = \DateTimeImmutable::createFromMutable($dateFin);
        
        return $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.boutique = :boutique')
            ->andWhere('r.createdAt >= :dateDebut')
            ->andWhere('r.createdAt <= :dateFin')
            ->andWhere('r.isActive = true')
            ->setParameter('boutique', $boutique)
            ->setParameter('dateDebut', $dateDebutImmutable)
            ->setParameter('dateFin', $dateFinImmutable)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countActiveByBoutiqueAndPeriods($boutique, \DateTime $dateDebut, \DateTime $dateFin): int
    {
        $dateDebutImmutable = \DateTimeImmutable::createFromMutable($dateDebut);
        $dateFinImmutable = \DateTimeImmutable::createFromMutable($dateFin);
        
        return $this->createQueryBuilder('r')
            ->select('SUM(r.montant)')
            ->where('r.boutique = :boutique')
            ->andWhere('r.createdAt >= :dateDebut')
            ->andWhere('r.createdAt <= :dateFin')
            ->andWhere('r.isActive = true')
            ->setParameter('boutique', $boutique)
            ->setParameter('dateDebut', $dateDebutImmutable)
            ->setParameter('dateFin', $dateFinImmutable)
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
        
        $dateImmutable = \DateTimeImmutable::createFromMutable($date);
        $nextDayImmutable = \DateTimeImmutable::createFromMutable($nextDay);

        return $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.entreprise = :entreprise')
            ->andWhere('r.createdAt >= :date')
            ->andWhere('r.createdAt < :nextDay')
            ->setParameter('entreprise', $entreprise)
            ->setParameter('date', $dateImmutable)
            ->setParameter('nextDay', $nextDayImmutable)
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
        
        $dateImmutable = \DateTimeImmutable::createFromMutable($date);
        $nextDayImmutable = \DateTimeImmutable::createFromMutable($nextDay);

        return $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.boutique = :boutique')
            ->andWhere('r.createdAt >= :date')
            ->andWhere('r.createdAt < :nextDay')
            ->setParameter('boutique', $boutique)
            ->setParameter('date', $dateImmutable)
            ->setParameter('nextDay', $nextDayImmutable)
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

    /**
     * Trouve les réservations par plusieurs statuts
     */
    public function findByMultipleStatuses(array $statuses): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.status IN (:statuses)')
            ->andWhere('r.isActive = true')
            ->orderBy('r.id', 'DESC')
            ->setParameter('statuses', $statuses)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les réservations par entreprise et plusieurs statuts
     */
    public function findByEntrepriseAndStatuses($entreprise, array $statuses): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.entreprise = :entreprise')
            ->andWhere('r.status IN (:statuses)')
            ->andWhere('r.isActive = true')
            ->orderBy('r.id', 'DESC')
            ->setParameter('entreprise', $entreprise)
            ->setParameter('statuses', $statuses)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les réservations par boutique et plusieurs statuts
     */
    public function findByBoutiqueAndStatuses($boutique, array $statuses): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.boutique = :boutique')
            ->andWhere('r.status IN (:statuses)')
            ->andWhere('r.isActive = true')
            ->orderBy('r.id', 'DESC')
            ->setParameter('boutique', $boutique)
            ->setParameter('statuses', $statuses)
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

    /**
     * Trouve les réservations d'une boutique avec filtres simplifiés
     */
    public function findByBoutiqueWithSimpleFilters(
        int $boutiqueId,
        \DateTime $dateDebut,
        \DateTime $dateFin,
        array $statusFilters = []
    ): array {
        $qb = $this->createQueryBuilder('r')
            ->leftJoin('r.client', 'c')
            ->leftJoin('r.boutique', 'b')
            ->leftJoin('r.entreprise', 'e')
            ->where('r.boutique = :boutiqueId')
            ->andWhere('r.createdAt BETWEEN :dateDebut AND :dateFin')
            ->setParameter('boutiqueId', $boutiqueId)
            ->setParameter('dateDebut', $dateDebut)
            ->setParameter('dateFin', $dateFin);

        // Filtre par statut
        if (!empty($statusFilters)) {
            $qb->andWhere('r.status IN (:statuses)')
               ->setParameter('statuses', $statusFilters);
        }

        // Tri par défaut par date de création décroissante
        $qb->orderBy('r.createdAt', 'DESC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Trouve les réservations d'une boutique avec filtres avancés
     */
    public function findByBoutiqueWithAdvancedFilters(
        int $boutiqueId,
        \DateTime $dateDebut,
        \DateTime $dateFin,
        array $statusFilters = [],
        array $additionalFilters = [],
        string $orderBy = 'createdAt',
        string $orderDirection = 'DESC'
    ): array {
        $qb = $this->createQueryBuilder('r')
            ->leftJoin('r.client', 'c')
            ->leftJoin('r.boutique', 'b')
            ->leftJoin('r.entreprise', 'e')
            ->where('r.boutique = :boutiqueId')
            ->andWhere('r.createdAt BETWEEN :dateDebut AND :dateFin')
            ->setParameter('boutiqueId', $boutiqueId)
            ->setParameter('dateDebut', $dateDebut)
            ->setParameter('dateFin', $dateFin);

        // Filtre par statut
        if (!empty($statusFilters)) {
            $qb->andWhere('r.status IN (:statuses)')
               ->setParameter('statuses', $statusFilters);
        }

        // Filtre par client
        if (isset($additionalFilters['clientId'])) {
            $qb->andWhere('r.client = :clientId')
               ->setParameter('clientId', $additionalFilters['clientId']);
        }

        // Filtre par montant minimum
        if (isset($additionalFilters['montantMin'])) {
            $qb->andWhere('CAST(r.montant AS DECIMAL(10,2)) >= :montantMin')
               ->setParameter('montantMin', $additionalFilters['montantMin']);
        }

        // Filtre par montant maximum
        if (isset($additionalFilters['montantMax'])) {
            $qb->andWhere('CAST(r.montant AS DECIMAL(10,2)) <= :montantMax')
               ->setParameter('montantMax', $additionalFilters['montantMax']);
        }

        // Tri
        $validOrderFields = ['id', 'montant', 'dateRetrait', 'createdAt'];
        if (in_array($orderBy, $validOrderFields)) {
            if ($orderBy === 'montant') {
                $qb->orderBy('CAST(r.montant AS DECIMAL(10,2))', $orderDirection);
            } else {
                $qb->orderBy('r.' . $orderBy, $orderDirection);
            }
        } else {
            $qb->orderBy('r.createdAt', 'DESC');
        }

        return $qb->getQuery()->getResult();
    }
}
