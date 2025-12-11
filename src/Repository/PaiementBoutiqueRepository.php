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
}