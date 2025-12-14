<?php

namespace App\Repository;

use App\Entity\PaiementBoutique;
use App\Entity\Boutique;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PaiementBoutique>
 */
class PaiementBoutiqueRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PaiementBoutique::class);
    }

     public function add(PaiementBoutique $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(PaiementBoutique $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Trouve les paiements boutique par boutique et période
     */
    public function findByBoutiqueAndPeriod(Boutique $boutique, \DateTime $dateDebut, \DateTime $dateFin): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.boutique = :boutique')
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
     * Compte tous les paiements boutique pour une boutique
     */
    public function countByBoutique(Boutique $boutique): int
    {
        return $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.boutique = :boutique')
            ->setParameter('boutique', $boutique)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Récupère tous les paiements d'une boutique avec leurs dates (pour debug)
     */
    public function findAllByBoutiqueWithDates(Boutique $boutique): array
    {
        return $this->createQueryBuilder('p')
            ->select('p.id', 'p.montant', 'p.reference', 'p.createdAt', 'p.isActive')
            ->where('p.boutique = :boutique')
            ->setParameter('boutique', $boutique)
            ->orderBy('p.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve tous les paiements d'une boutique sans filtre de date
     */
    public function findAllByBoutique(Boutique $boutique): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.boutique = :boutique')
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
        $result = $this->createQueryBuilder('pb')
            ->select('SUM(pb.montant)')
            ->leftJoin('pb.boutique', 'b')
            ->where('b.entreprise = :entreprise')
            ->andWhere('pb.createdAt BETWEEN :dateDebut AND :dateFin')
            ->setParameter('entreprise', $entreprise)
            ->setParameter('dateDebut', $dateDebut)
            ->setParameter('dateFin', $dateFin)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ?? 0;
    }

    /**
     * Calcule le total des paiements pour une boutique dans une période
     */
    public function sumByBoutiqueAndPeriod(Boutique $boutique, \DateTime $dateDebut, \DateTime $dateFin): float
    {
        $result = $this->createQueryBuilder('pb')
            ->select('SUM(pb.montant)')
            ->where('pb.boutique = :boutique')
            ->andWhere('pb.createdAt BETWEEN :dateDebut AND :dateFin')
            ->setParameter('boutique', $boutique)
            ->setParameter('dateDebut', $dateDebut)
            ->setParameter('dateFin', $dateFin)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ?? 0;
    }

    /**
     * Compte les paiements par jour pour une entreprise
     */
    public function countByEntrepriseAndDay($entreprise, \DateTime $date): int
    {
        $nextDay = clone $date;
        $nextDay->add(new \DateInterval('P1D'));

        return $this->createQueryBuilder('pb')
            ->select('COUNT(pb.id)')
            ->leftJoin('pb.boutique', 'b')
            ->where('b.entreprise = :entreprise')
            ->andWhere('pb.createdAt >= :date')
            ->andWhere('pb.createdAt < :nextDay')
            ->setParameter('entreprise', $entreprise)
            ->setParameter('date', $date)
            ->setParameter('nextDay', $nextDay)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Compte les paiements par jour pour une boutique
     */
    public function countByBoutiqueAndDay(Boutique $boutique, \DateTime $date): int
    {
        $nextDay = clone $date;
        $nextDay->add(new \DateInterval('P1D'));

        return $this->createQueryBuilder('pb')
            ->select('COUNT(pb.id)')
            ->where('pb.boutique = :boutique')
            ->andWhere('pb.createdAt >= :date')
            ->andWhere('pb.createdAt < :nextDay')
            ->setParameter('boutique', $boutique)
            ->setParameter('date', $date)
            ->setParameter('nextDay', $nextDay)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Récupère les derniers paiements pour une entreprise
     */
    public function findLatestByEntreprise($entreprise, int $limit = 5): array
    {
        return $this->createQueryBuilder('pb')
            ->leftJoin('pb.boutique', 'b')
            ->where('b.entreprise = :entreprise')
            ->orderBy('pb.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setParameter('entreprise', $entreprise)
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les derniers paiements pour une boutique
     */
    public function findLatestByBoutique(Boutique $boutique, int $limit = 5): array
    {
        return $this->createQueryBuilder('pb')
            ->where('pb.boutique = :boutique')
            ->orderBy('pb.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setParameter('boutique', $boutique)
            ->getQuery()
            ->getResult();
    }
}