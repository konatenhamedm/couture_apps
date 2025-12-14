<?php

namespace App\Repository;

use App\Entity\PaiementFacture;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PaiementFacture>
 */
class PaiementFactureRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PaiementFacture::class);
    }

     public function add(PaiementFacture $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(PaiementFacture $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Trouve les paiements par boutique
     */
    public function findByBoutique(int $boutiqueId): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.facture', 'f')
            ->leftJoin('f.client', 'c')
            ->where('f.boutique = :boutiqueId')
            ->setParameter('boutiqueId', $boutiqueId)
            ->orderBy('p.date', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Statistiques des paiements par période
     */
    public function getStatsByPeriod(\DateTime $dateDebut, \DateTime $dateFin, int $boutiqueId = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->select('COUNT(p.id) as nombre, SUM(p.montant) as total')
            ->leftJoin('p.facture', 'f')
            ->where('p.date BETWEEN :dateDebut AND :dateFin')
            ->setParameter('dateDebut', $dateDebut)
            ->setParameter('dateFin', $dateFin);

        if ($boutiqueId) {
            $qb->andWhere('f.boutique = :boutiqueId')
                ->setParameter('boutiqueId', $boutiqueId);
        }

        return $qb->getQuery()->getSingleResult();
    }

    /**
     * Répartition par mode de paiement
     */
    public function getRepartitionModesPaiement( $boutiqueId = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->select('p.modePaiement, COUNT(p.id) as nombre, SUM(p.montant) as total')
            ->leftJoin('p.facture', 'f')
            ->groupBy('p.modePaiement')
            ->orderBy('total', 'DESC');

        if ($boutiqueId) {
            $qb->where('f.boutique = :boutiqueId')
                ->setParameter('boutiqueId', $boutiqueId);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Trouve les paiements facture par période
     */
    public function findByPeriod(\DateTime $dateDebut, \DateTime $dateFin, $succursaleId): array
    {
        return $this->createQueryBuilder('p')
            ->innerJoin('b.facture', 'f')
            ->innerJoin('f.succursale', 's')
            ->where('p.createdAt >= :dateDebut')
            ->andWhere('p.createdAt <= :dateFin')
            ->andWhere("s.id = :id")
            ->setParameter('dateDebut', $dateDebut)
            ->setParameter('dateFin', $dateFin)
            ->setParameter('id', $succursaleId)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte tous les paiements facture
     */
    public function countAll(): int
    {
        return $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Calcule le total des paiements facture pour une entreprise dans une période
     */
    public function sumByEntrepriseAndPeriod($entreprise, \DateTime $dateDebut, \DateTime $dateFin): float
    {
        $dateDebutImmutable = \DateTimeImmutable::createFromMutable($dateDebut);
        $dateFinImmutable = \DateTimeImmutable::createFromMutable($dateFin);
        
        $result = $this->createQueryBuilder('pf')
            ->select('SUM(pf.montant)')
            ->leftJoin('pf.facture', 'f')
            ->where('f.entreprise = :entreprise')
            ->andWhere('pf.createdAt >= :dateDebut')
            ->andWhere('pf.createdAt <= :dateFin')
            ->setParameter('entreprise', $entreprise)
            ->setParameter('dateDebut', $dateDebutImmutable)
            ->setParameter('dateFin', $dateFinImmutable)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ?? 0;
    }
}
