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
    public function getRepartitionModesPaiement(int $boutiqueId = null): array
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
}