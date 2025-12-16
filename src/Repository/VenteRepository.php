<?php

namespace App\Repository;

use App\Entity\Vente;
use App\Service\EntityManagerProvider;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<Vente>
 */
class VenteRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry, EntityManagerProvider $entityManagerProvider)
    {
        parent::__construct($registry, Vente::class, $entityManagerProvider);
    }

    /**
     * Trouve les ventes par boutique avec pagination
     */
    public function findByBoutiqueWithPagination(int $boutiqueId, int $page = 1, int $limit = 10): array
    {
        return $this->createQueryBuilderForEnvironment('v')
            ->leftJoin('v.client', 'c')
            ->leftJoin('v.ligneVentes', 'lv')
            ->where('v.boutique = :boutiqueId')
            ->setParameter('boutiqueId', $boutiqueId)
            ->orderBy('v.date', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Statistiques des ventes par période
     */
    public function getStatsByPeriod(\DateTime $dateDebut, \DateTime $dateFin, int $boutiqueId = null): array
    {
        $qb = $this->createQueryBuilderForEnvironment('v')
            ->select('COUNT(v.id) as nombre, SUM(v.montant) as total')
            ->where('v.date BETWEEN :dateDebut AND :dateFin')
            ->setParameter('dateDebut', $dateDebut)
            ->setParameter('dateFin', $dateFin);

        if ($boutiqueId) {
            $qb->andWhere('v.boutique = :boutiqueId')
               ->setParameter('boutiqueId', $boutiqueId);
        }

        return $qb->getQuery()->getSingleResult();
    }

    /**
     * Top des produits vendus
     */
    public function getTopProduits(int $limit = 10, int $boutiqueId = null): array
    {
        $qb = $this->createQueryBuilderForEnvironment('v')
            ->select('lv.produit, SUM(lv.quantite) as totalQuantite, SUM(lv.total) as totalMontant')
            ->leftJoin('v.ligneVentes', 'lv')
            ->groupBy('lv.produit')
            ->orderBy('totalQuantite', 'DESC')
            ->setMaxResults($limit);

        if ($boutiqueId) {
            $qb->where('v.boutique = :boutiqueId')
               ->setParameter('boutiqueId', $boutiqueId);
        }

        return $qb->getQuery()->getResult();
    }
}