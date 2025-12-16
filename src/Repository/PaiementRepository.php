<?php

namespace App\Repository;

use App\Entity\Paiement;
use App\Service\EntityManagerProvider;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<Paiement>
 */
class PaiementRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry, EntityManagerProvider $entityManagerProvider)
    {
        parent::__construct($registry, Paiement::class, $entityManagerProvider);
    }
    public function add(Paiement $entity, bool $flush = false): void
    {
        $this->saveInEnvironment($entity, $flush);
    }

    public function remove(Paiement $entity, bool $flush = false): void
    {
        $this->removeInEnvironment($entity, $flush);
    }

    public function getEvolutionRevenus(\DateTime $debut, \DateTime $fin, string $groupBy = 'jour'): array
    {
        $format = match ($groupBy) {
            'jour' => '%Y-%m-%d',
            'semaine' => '%Y-%u',
            'mois' => '%Y-%m',
            default => '%Y-%m-%d'
        };

        $sql = "SELECT DATE_FORMAT(created_at, ?) as periode, SUM(montant) as montant 
                FROM paiement 
                WHERE created_at BETWEEN ? AND ? 
                GROUP BY periode 
                ORDER BY periode ASC";

        $conn = $this->getEntityManager()->getConnection();
        return $conn->executeQuery($sql, [$format, $debut->format('Y-m-d H:i:s'), $fin->format('Y-m-d H:i:s')])->fetchAllAssociative();
    }

    public function getRevenusParType(\DateTime $debut, \DateTime $fin): array
    {
        $sql = "SELECT discr as type, SUM(montant) as montant, COUNT(id) as nombre 
                FROM paiement 
                WHERE created_at BETWEEN ? AND ? 
                GROUP BY discr 
                ORDER BY montant DESC";

        $conn = $this->getEntityManager()->getConnection();
        return $conn->executeQuery($sql, [$debut->format('Y-m-d H:i:s'), $fin->format('Y-m-d H:i:s')])->fetchAllAssociative();
    }

    public function getTopClients(\DateTime $debut, \DateTime $fin, int $limit): array
    {
        // Clients from PaiementFacture
        $clientsFacture = $this->getEntityManager()->createQueryBuilder()
            ->select('c.id, c.nom, c.prenom, SUM(p.montant) as totalDepense, COUNT(p.id) as nombrePaiements')
            ->from('App\Entity\PaiementFacture', 'pf')
            ->join('pf.facture', 'f')
            ->join('f.client', 'c')
            ->join('App\Entity\Paiement', 'p', 'WITH', 'p.id = pf.id')
            ->where('p.createdAt BETWEEN :debut AND :fin')
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->groupBy('c.id')
            ->getQuery()
            ->getResult();

        // Clients from PaiementReservation
        $clientsReservation = $this->getEntityManager()->createQueryBuilder()
            ->select('c.id, c.nom, c.prenom, SUM(p.montant) as totalDepense, COUNT(p.id) as nombrePaiements')
            ->from('App\Entity\PaiementReservation', 'pr')
            ->join('pr.reservation', 'r')
            ->join('r.client', 'c')
            ->join('App\Entity\Paiement', 'p', 'WITH', 'p.id = pr.id')
            ->where('p.createdAt BETWEEN :debut AND :fin')
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->groupBy('c.id')
            ->getQuery()
            ->getResult();

        // Merge and sort results
        $merged = [];
        foreach (array_merge($clientsFacture, $clientsReservation) as $client) {
            $id = $client['id'];
            if (isset($merged[$id])) {
                $merged[$id]['totalDepense'] += $client['totalDepense'];
                $merged[$id]['nombrePaiements'] += $client['nombrePaiements'];
            } else {
                $merged[$id] = $client;
            }
        }

        usort($merged, fn($a, $b) => $b['totalDepense'] <=> $a['totalDepense']);
        return array_slice($merged, 0, $limit);
    }

    public function sumMontantByDateRange(\DateTime $debut, \DateTime $fin): float
    {
        $result = $this->createQueryBuilderForEnvironment('p')
            ->select('SUM(p.montant)')
            ->where('p.createdAt BETWEEN :debut AND :fin')
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->getQuery()
            ->getSingleScalarResult();
        
        return (float) ($result ?? 0);
    }
    //    /**
    //     * @return Paiement[] Returns an array of Paiement objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilderForEnvironment('p')
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
    //        return $this->createQueryBuilderForEnvironment('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
