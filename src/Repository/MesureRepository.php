<?php

namespace App\Repository;

use App\Entity\Mesure;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Mesure>
 */
class MesureRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Mesure::class);
    }
    public function add(Mesure $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Mesure $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Compte les mesures par entreprise et période
     */
    public function countByEntrepriseAndPeriod($entreprise, \DateTime $dateDebut, \DateTime $dateFin): int
    {
        $dateDebutImmutable = \DateTimeImmutable::createFromMutable($dateDebut);
        $dateFinImmutable = \DateTimeImmutable::createFromMutable($dateFin);
        
        return $this->createQueryBuilder('m')
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

        return $this->createQueryBuilder('m')
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

    /**
     * Compte les mesures en cours par succursale
     */
    public function countEnCoursBySuccursale($succursale): int
    {
        return $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->leftJoin('m.facture', 'f')
            ->where('f.succursale = :succursale')
            ->andWhere('m.isActive = :active')
            ->andWhere('f.dateRetrait > :today')
            ->setParameter('succursale', $succursale)
            ->setParameter('active', true)
            ->setParameter('today', new \DateTime())
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Compte les mesures par succursale et période
     */
    public function countBySuccursaleAndPeriod($succursale, \DateTime $dateDebut, \DateTime $dateFin): int
    {
        $dateDebutImmutable = \DateTimeImmutable::createFromMutable($dateDebut);
        $dateFinImmutable = \DateTimeImmutable::createFromMutable($dateFin);
        
        return $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->leftJoin('m.facture', 'f')
            ->where('f.succursale = :succursale')
            ->andWhere('m.createdAt >= :dateDebut')
            ->andWhere('m.createdAt <= :dateFin')
            ->setParameter('succursale', $succursale)
            ->setParameter('dateDebut', $dateDebutImmutable)
            ->setParameter('dateFin', $dateFinImmutable)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Compte les mesures par jour pour une succursale
     */
    public function countBySuccursaleAndDay($succursale, \DateTime $date): int
    {
        $nextDay = clone $date;
        $nextDay->add(new \DateInterval('P1D'));
        
        $dateImmutable = \DateTimeImmutable::createFromMutable($date);
        $nextDayImmutable = \DateTimeImmutable::createFromMutable($nextDay);

        return $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->leftJoin('m.facture', 'f')
            ->where('f.succursale = :succursale')
            ->andWhere('m.createdAt >= :date')
            ->andWhere('m.createdAt < :nextDay')
            ->setParameter('succursale', $succursale)
            ->setParameter('date', $dateImmutable)
            ->setParameter('nextDay', $nextDayImmutable)
            ->getQuery()
            ->getSingleScalarResult();
    }

    //    /**
    //     * @return Mesure[] Returns an array of Mesure objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('m')
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
    //        return $this->createQueryBuilder('m')
    //            ->andWhere('m.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
