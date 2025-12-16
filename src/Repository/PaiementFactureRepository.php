<?php

namespace App\Repository;

use App\Entity\PaiementFacture;
use App\Service\EntityManagerProvider;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<PaiementFacture>
 */
class PaiementFactureRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry, EntityManagerProvider $entityManagerProvider)
    {
        parent::__construct($registry, PaiementFacture::class, $entityManagerProvider);
    }

     public function add(PaiementFacture $entity, bool $flush = false): void
    {
        $this->saveInEnvironment($entity, $flush);
    }

    public function remove(PaiementFacture $entity, bool $flush = false): void
    {
        $this->removeInEnvironment($entity, $flush);
    }

    /**
     * Trouve les paiements par boutique
     */
    public function findByBoutique(int $boutiqueId): array
    {
        return $this->createQueryBuilderForEnvironment('p')
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
        $qb = $this->createQueryBuilderForEnvironment('p')
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
        $qb = $this->createQueryBuilderForEnvironment('p')
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
        return $this->createQueryBuilderForEnvironment('p')
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
        return $this->createQueryBuilderForEnvironment('p')
            ->select('COUNT(p.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Calcule le total des paiements facture pour une entreprise dans une période
     */
    public function sumByEntrepriseAndPeriod($entreprise, \DateTime $dateDebut, \DateTime $dateFin): float
    {
        $result = $this->createQueryBuilderForEnvironment('pf')
            ->select('SUM(pf.montant)')
            ->leftJoin('pf.facture', 'f')
            ->where('f.entreprise = :entreprise')
            ->andWhere('DATE(pf.createdAt) >= DATE(:dateDebut)')
            ->andWhere('DATE(pf.createdAt) <= DATE(:dateFin)')
            ->setParameter('entreprise', $entreprise)
            ->setParameter('dateDebut', $dateDebut->format('Y-m-d'))
            ->setParameter('dateFin', $dateFin->format('Y-m-d'))
            ->getQuery()
            ->getSingleScalarResult();

        return $result ?? 0;
    }
    /**
     * Calcule la somme des paiements facture par jour pour une entreprise
     */
    public function sumByEntrepriseAndDay($entreprise, \DateTime $date): float
    {
        $result = $this->createQueryBuilderForEnvironment('pf')
            ->select('SUM(pf.montant)')
            ->leftJoin('pf.facture', 'f')
            ->where('f.entreprise = :entreprise')
            ->andWhere('DATE(pf.createdAt) = DATE(:date)')
            ->setParameter('entreprise', $entreprise)
            ->setParameter('date', $date->format('Y-m-d'))
            ->getQuery()
            ->getSingleScalarResult();

        return $result ?? 0;
    }
}
