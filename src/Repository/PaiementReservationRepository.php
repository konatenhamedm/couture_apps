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
    public function findByBoutiqueAndPeriod(Boutique $boutique, \DateTime $dateDebut, \DateTime $dateFin): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.reservation', 'r')
            ->where('r.boutique = :boutique')
            /* ->andWhere('p.createdAt >= :dateDebut')
            ->andWhere('p.createdAt <= :dateFin') */
            ->setParameter('boutique', $boutique)/* 
            ->setParameter('dateDebut', $dateDebut)
            ->setParameter('dateFin', $dateFin) */
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
}
