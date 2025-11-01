<?php

namespace App\Repository;

use App\Entity\Paiement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Paiement>
 */
class PaiementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Paiement::class);
    }
    public function add(Paiement $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Paiement $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function getEvolutionRevenus(\DateTime $debut, \DateTime $fin, string $groupBy = 'jour'): array
    {
        $format = match ($groupBy) {
            'jour' => '%Y-%m-%d',
            'semaine' => '%Y-%U',
            'mois' => '%Y-%m',
            default => '%Y-%m-%d'
        };

        return $this->createQueryBuilder('p')
            ->select("DATE_FORMAT(p.dateCreated, '$format') as periode")
            ->addSelect('SUM(p.montant) as montant')
            ->where('p.dateCreated BETWEEN :debut AND :fin')
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->groupBy('periode')
            ->orderBy('periode', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getRevenusParType(\DateTime $debut, \DateTime $fin): array
    {
        return $this->createQueryBuilder('p')
            ->select('p.type')
            ->addSelect('SUM(p.montant) as montant')
            ->addSelect('COUNT(p.id) as nombre')
            ->where('p.dateCreated BETWEEN :debut AND :fin')
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->groupBy('p.type')
            ->orderBy('montant', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getTopClients(\DateTime $debut, \DateTime $fin, int $limit): array
    {
        return $this->createQueryBuilder('p')
            ->select('c.id')
            ->addSelect('c.nom')
            ->addSelect('c.prenom')
            ->addSelect('SUM(p.montant) as totalDepense')
            ->addSelect('COUNT(p.id) as nombrePaiements')
            ->leftJoin('App\Entity\PaiementFacture', 'pf', 'WITH', 'pf.id = p.id')
            ->leftJoin('pf.facture', 'f')
            ->leftJoin('f.client', 'c')
            ->leftJoin('App\Entity\PaiementReservation', 'pr', 'WITH', 'pr.id = p.id')
            ->leftJoin('pr.reservation', 'r')
            ->leftJoin('r.client', 'c2')
            ->where('p.dateCreated BETWEEN :debut AND :fin')
            ->andWhere('c.id IS NOT NULL OR c2.id IS NOT NULL')
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->groupBy('c.id, c2.id')
            ->orderBy('totalDepense', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
    //    /**
    //     * @return Paiement[] Returns an array of Paiement objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Paiement
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
