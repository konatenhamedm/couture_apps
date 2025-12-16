<?php

namespace App\Repository;

use App\Entity\Mesure;
use App\Service\EntityManagerProvider;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<Mesure>
 */
class MesureRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry, EntityManagerProvider $entityManagerProvider)
    {
        parent::__construct($registry, Mesure::class, $entityManagerProvider);
    }
    public function add(Mesure $entity, bool $flush = false): void
    {
        $this->saveInEnvironment($entity, $flush);
    }

    public function remove(Mesure $entity, bool $flush = false): void
    {
        $this->removeInEnvironment($entity, $flush);
    }

    /**
     * Compte les mesures par entreprise et période
     */
    public function countByEntrepriseAndPeriod($entreprise, \DateTime $dateDebut, \DateTime $dateFin): int
    {
        $dateDebutImmutable = \DateTimeImmutable::createFromMutable($dateDebut);
        $dateFinImmutable = \DateTimeImmutable::createFromMutable($dateFin);
        
        return $this->createQueryBuilderForEnvironment('m')
            ->select('COUNT(m.id)')
            ->leftJoin('m.facture', 'f')
            ->where('f.entreprise = :entreprise')
            ->andWhere('m.createdAt >= :dateDebut')
            ->andWhere('m.createdAt <= :dateFin')
            ->setParameter('entreprise', $entreprise)
            ->setParameter('dateDebut', $dateDebutImmutable)
            ->setParameter('dateFin', $dateFinImmutable)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Récupère les 10 meilleures ventes de la semaine pour une boutique
     * Retourne les types de mesures les plus vendus avec leur nombre de ventes
     */
    public function findTopSellingOfWeek($boutique, int $limit = 10): array
    {
        $startOfWeek = new \DateTime('monday this week');
        $endOfWeek = new \DateTime('sunday this week 23:59:59');
        
        $startOfWeekImmutable = \DateTimeImmutable::createFromMutable($startOfWeek);
        $endOfWeekImmutable = \DateTimeImmutable::createFromMutable($endOfWeek);

        return $this->createQueryBuilderForEnvironment('m')
            ->select('tm.id, tm.libelle, COUNT(m.id) as nombreVentes, SUM(CAST(m.montant AS DECIMAL(10,2))) as chiffreAffaires')
            ->leftJoin('m.facture', 'f')
            ->leftJoin('m.typeMesure', 'tm')
            ->where('f.entreprise = :boutique')
            ->andWhere('m.createdAt >= :startOfWeek')
            ->andWhere('m.createdAt <= :endOfWeek')
            ->andWhere('m.isActive = :active')
            ->groupBy('tm.id, tm.libelle')
            ->orderBy('nombreVentes', 'DESC')
            ->addOrderBy('chiffreAffaires', 'DESC')
            ->setParameter('boutique', $boutique)
            ->setParameter('startOfWeek', $startOfWeekImmutable)
            ->setParameter('endOfWeek', $endOfWeekImmutable)
            ->setParameter('active', true)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return Mesure[] Returns an array of Mesure objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilderForEnvironment('m')
    //            ->andWhere('m.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('m.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Mesure
    //    {
    //        return $this->createQueryBuilderForEnvironment('m')
    //            ->andWhere('m.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
