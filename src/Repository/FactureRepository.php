<?php

namespace App\Repository;

use App\Entity\Facture;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Facture>
 */
class FactureRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Facture::class);
    }
    public function add(Facture $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Facture $entity, bool $flush = false): void
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
                FROM facture 
                WHERE created_at BETWEEN ? AND ? 
                GROUP BY periode 
                ORDER BY periode ASC";

        $conn = $this->getEntityManager()->getConnection();
        return $conn->executeQuery($sql, [$format, $debut->format('Y-m-d H:i:s'), $fin->format('Y-m-d H:i:s')])->fetchAllAssociative();
    }

    public function countByDateRange(\DateTime $debut, \DateTime $fin): int
    {
        return $this->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->where('f.createdAt BETWEEN :debut AND :fin')
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Compte les factures par entreprise et période
     */
    public function countByEntrepriseAndPeriod($entreprise, \DateTime $dateDebut, \DateTime $dateFin): int
    {
        $dateDebutImmutable = \DateTimeImmutable::createFromMutable($dateDebut);
        $dateFinImmutable = \DateTimeImmutable::createFromMutable($dateFin);
        
        return $this->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->where('f.entreprise = :entreprise')
            ->andWhere('f.createdAt >= :dateDebut')
            ->andWhere('f.createdAt <= :dateFin')
            ->setParameter('entreprise', $entreprise)
            ->setParameter('dateDebut', $dateDebutImmutable)
            ->setParameter('dateFin', $dateFinImmutable)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Compte les factures par jour pour une entreprise
     */
    public function countByEntrepriseAndDay($entreprise, \DateTime $date): int
    {
        $nextDay = clone $date;
        $nextDay->add(new \DateInterval('P1D'));
        
        $dateImmutable = \DateTimeImmutable::createFromMutable($date);
        $nextDayImmutable = \DateTimeImmutable::createFromMutable($nextDay);

        return $this->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->where('f.entreprise = :entreprise')
            ->andWhere('f.createdAt >= :date')
            ->andWhere('f.createdAt < :nextDay')
            ->setParameter('entreprise', $entreprise)
            ->setParameter('date', $dateImmutable)
            ->setParameter('nextDay', $nextDayImmutable)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Récupère les dernières factures pour une entreprise
     */
    public function findLatestByEntreprise($entreprise, int $limit = 5): array
    {
        return $this->createQueryBuilder('f')
            ->where('f.entreprise = :entreprise')
            ->orderBy('f.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setParameter('entreprise', $entreprise)
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return Facture[] Returns an array of Facture objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('f')
    //            ->andWhere('f.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('f.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    /**
     * Récupère les factures dont la date de retrait est proche et qui ne sont pas encore honorées
     * Une facture est considérée comme non honorée si le total des paiements < montant total
     */
    public function findUpcomingUnpaidInvoices($succursale, int $limit = 10): array
    {
        $today = new \DateTime();
        
        // Récupérer les factures avec date de retrait proche
        return $this->createQueryBuilder('f')
            ->leftJoin('f.paiementFactures', 'pf')
            ->where('f.succursale = :succursale')
            ->andWhere('f.dateRetrait >= :today')
            ->andWhere('f.isActive = :active')
            ->groupBy('f.id')
            ->having('COALESCE(SUM(pf.montant), 0) < f.MontantTotal')
            ->orderBy('f.dateRetrait', 'ASC')
            ->setParameter('succursale', $succursale)
            ->setParameter('today', $today)
            ->setParameter('active', true)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    //    public function findOneBySomeField($value): ?Facture
    //    {
    //        return $this->createQueryBuilder('f')
    //            ->andWhere('f.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
