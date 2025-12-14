<?php

namespace App\Repository;

use App\Entity\Boutique;
use App\Entity\PaiementReservation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PaiementReservation>
 */
class PaiementReservationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PaiementReservation::class);
    }

    public function add(PaiementReservation $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(PaiementReservation $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Trouve les paiements réservation par période
     */
    public function findByPeriod(\DateTime $dateDebut, \DateTime $dateFin): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.createdAt >= :dateDebut')
            ->andWhere('p.createdAt <= :dateFin')
            ->setParameter('dateDebut', $dateDebut)
            ->setParameter('dateFin', $dateFin)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les paiements réservation par boutique et période
     */
    public function findByBoutiqueAndPeriod($boutique, \DateTime $dateDebut, \DateTime $dateFin): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.reservation', 'r')
            ->andWhere('r.boutique = :boutique')
            ->andWhere('p.createdAt >= :dateDebut')
            ->andWhere('p.createdAt <= :dateFin')
            ->setParameter('boutique', $boutique)
            ->setParameter('dateDebut', $dateDebut)
            ->setParameter('dateFin', $dateFin)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte tous les paiements réservation
     */
    public function countAll(): int
    {
        return $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Compte tous les paiements réservation pour une boutique
     */
    public function countByBoutique($boutique): int
    {
        return $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->leftJoin('p.reservation', 'r')
            ->where('r.boutique = :boutique')
            ->setParameter('boutique', $boutique)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Récupère tous les paiements réservation d'une boutique avec leurs dates (pour debug)
     */
    public function findAllByBoutiqueWithDates($boutique): array
    {
        return $this->createQueryBuilder('p')
            ->select('p.id', 'p.montant', 'p.reference', 'p.createdAt', 'p.isActive')
            ->leftJoin('p.reservation', 'r')
            ->where('r.boutique = :boutique')
            ->setParameter('boutique', $boutique)
            ->orderBy('p.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve tous les paiements réservation d'une boutique sans filtre de date
     */
    public function findAllByBoutique($boutique): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.reservation', 'r')
            ->where('r.boutique = :boutique')
            ->setParameter('boutique', $boutique)
            ->orderBy('p.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Calcule le total des paiements pour une entreprise dans une période
     */
    public function sumByEntrepriseAndPeriod($entreprise, \DateTime $dateDebut, \DateTime $dateFin): float
    {
        // S'assurer que dateDebut commence à 00:00:00 et dateFin finit à 23:59:59
        $dateDebutClone = clone $dateDebut;
        $dateDebutClone->setTime(0, 0, 0);
        $dateFinClone = clone $dateFin;
        $dateFinClone->setTime(23, 59, 59);
        
        $dateDebutImmutable = \DateTimeImmutable::createFromMutable($dateDebutClone);
        $dateFinImmutable = \DateTimeImmutable::createFromMutable($dateFinClone);
        
        $result = $this->createQueryBuilder('pr')
            ->select('SUM(pr.montant)')
            ->leftJoin('pr.reservation', 'r')
            ->where('r.entreprise = :entreprise')
            ->andWhere('pr.createdAt >= :dateDebut')
            ->andWhere('pr.createdAt <= :dateFin')
            ->setParameter('entreprise', $entreprise)
            ->setParameter('dateDebut', $dateDebutImmutable)
            ->setParameter('dateFin', $dateFinImmutable)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ?? 0;
    }

    /**
     * Calcule le total des paiements pour une boutique dans une période
     */
    public function sumByBoutiqueAndPeriod($boutique, \DateTime $dateDebut, \DateTime $dateFin): float
    {
        // S'assurer que dateDebut commence à 00:00:00 et dateFin finit à 23:59:59
        $dateDebutClone = clone $dateDebut;
        $dateDebutClone->setTime(0, 0, 0);
        $dateFinClone = clone $dateFin;
        $dateFinClone->setTime(23, 59, 59);
        
        $dateDebutImmutable = \DateTimeImmutable::createFromMutable($dateDebutClone);
        $dateFinImmutable = \DateTimeImmutable::createFromMutable($dateFinClone);
        
        $result = $this->createQueryBuilder('pr')
            ->select('SUM(pr.montant)')
            ->leftJoin('pr.reservation', 'r')
            ->where('r.boutique = :boutique')
            ->andWhere('pr.createdAt >= :dateDebut')
            ->andWhere('pr.createdAt <= :dateFin')
            ->setParameter('boutique', $boutique)
            ->setParameter('dateDebut', $dateDebutImmutable)
            ->setParameter('dateFin', $dateFinImmutable)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ?? 0;
    }
    /**
     * Calcule la somme des paiements par jour pour une entreprise
     */
    public function sumByEntrepriseAndDay($entreprise, \DateTime $date): float
    {
        $nextDay = clone $date;
        $nextDay->add(new \DateInterval('P1D'));
        
        $dateDebutClone = clone $date;
        $dateDebutClone->setTime(0, 0, 0);
        $dateFinClone = clone $nextDay;
        $dateFinClone->setTime(0, 0, 0);
        
        $dateImmutable = \DateTimeImmutable::createFromMutable($dateDebutClone);
        $nextDayImmutable = \DateTimeImmutable::createFromMutable($dateFinClone);

        $result = $this->createQueryBuilder('pr')
            ->select('SUM(pr.montant)')
            ->leftJoin('pr.reservation', 'r')
            ->where('r.entreprise = :entreprise')
            ->andWhere('pr.createdAt >= :date')
            ->andWhere('pr.createdAt < :nextDay')
            ->setParameter('entreprise', $entreprise)
            ->setParameter('date', $dateImmutable)
            ->setParameter('nextDay', $nextDayImmutable)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ?? 0;
    }

    /**
     * Calcule la somme des paiements par jour pour une boutique
     */
    public function sumByBoutiqueAndDay($boutique, \DateTime $date): float
    {
        $nextDay = clone $date;
        $nextDay->add(new \DateInterval('P1D'));
        
        $dateDebutClone = clone $date;
        $dateDebutClone->setTime(0, 0, 0);
        $dateFinClone = clone $nextDay;
        $dateFinClone->setTime(0, 0, 0);
        
        $dateImmutable = \DateTimeImmutable::createFromMutable($dateDebutClone);
        $nextDayImmutable = \DateTimeImmutable::createFromMutable($dateFinClone);

        $result = $this->createQueryBuilder('pr')
            ->select('SUM(pr.montant)')
            ->leftJoin('pr.reservation', 'r')
            ->where('r.boutique = :boutique')
            ->andWhere('pr.createdAt >= :date')
            ->andWhere('pr.createdAt < :nextDay')
            ->setParameter('boutique', $boutique)
            ->setParameter('date', $dateImmutable)
            ->setParameter('nextDay', $nextDayImmutable)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ?? 0;
    }
}
