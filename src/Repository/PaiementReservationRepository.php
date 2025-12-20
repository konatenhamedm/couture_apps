<?php

namespace App\Repository;

use App\Entity\Boutique;
use App\Entity\PaiementReservation;
use App\Service\DateRangeBuilder;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PaiementReservation>
 */
class PaiementReservationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PaiementReservation::class);
    }

    public function add(PaiementReservation $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(PaiementReservation $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Trouve les paiements réservation par période
     */
    public function findByPeriod(\DateTime $dateDebut, \DateTime $dateFin): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.createdAt >= :dateDebut')
            ->andWhere('p.createdAt <= :dateFin')
            ->setParameter('dateDebut', $dateDebut)
            ->setParameter('dateFin', $dateFin)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les paiements réservation par boutique et période
     */
    public function findByBoutiqueAndPeriod($boutique, \DateTime $dateDebut, \DateTime $dateFin): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.reservation', 'r')
            ->andWhere('r.boutique = :boutique')
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
     * Compte tous les paiements réservation
     */
    public function countAll(): int
    {
        return $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Compte tous les paiements réservation pour une boutique
     */
    public function countByBoutique($boutique): int
    {
        return $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->leftJoin('p.reservation', 'r')
            ->where('r.boutique = :boutique')
            ->setParameter('boutique', $boutique)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Récupère tous les paiements réservation d'une boutique avec leurs dates (pour debug)
     */
    public function findAllByBoutiqueWithDates($boutique): array
    {
        return $this->createQueryBuilder('p')
            ->select('p.id', 'p.montant', 'p.reference', 'p.createdAt', 'p.isActive')
            ->leftJoin('p.reservation', 'r')
            ->where('r.boutique = :boutique')
            ->setParameter('boutique', $boutique)
            ->orderBy('p.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve tous les paiements réservation d'une boutique sans filtre de date
     */
    public function findAllByBoutique($boutique): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.reservation', 'r')
            ->where('r.boutique = :boutique')
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
        [$startDate, $endDate] = DateRangeBuilder::periodRange($dateDebut, $dateFin);
        
        $result = $this->createQueryBuilder('pr')
            ->select('COALESCE(SUM(pr.montant), 0)')
            ->leftJoin('pr.reservation', 'r')
            ->where('r.entreprise = :entreprise')
            ->andWhere('pr.createdAt >= :dateDebut')
            ->andWhere('pr.createdAt <= :dateFin')
            ->setParameter('entreprise', $entreprise)
            ->setParameter('dateDebut', DateRangeBuilder::formatForDQL($startDate))
            ->setParameter('dateFin', DateRangeBuilder::formatForDQL($endDate))
            ->getQuery()
            ->getSingleScalarResult();

        return (float) $result;
    }

    /**
     * Calcule le total des paiements pour une boutique dans une période
     */
    public function sumByBoutiqueAndPeriod($boutique, \DateTime $dateDebut, \DateTime $dateFin): float
    {
        [$startDate, $endDate] = DateRangeBuilder::periodRange($dateDebut, $dateFin);
        
        $result = $this->createQueryBuilder('pr')
            ->select('COALESCE(SUM(pr.montant), 0)')
            ->leftJoin('pr.reservation', 'r')
            ->where('r.boutique = :boutique')
            ->andWhere('pr.createdAt >= :dateDebut')
            ->andWhere('pr.createdAt <= :dateFin')
            ->setParameter('boutique', $boutique)
            ->setParameter('dateDebut', DateRangeBuilder::formatForDQL($startDate))
            ->setParameter('dateFin', DateRangeBuilder::formatForDQL($endDate))
            ->getQuery()
            ->getSingleScalarResult();

        return (float) $result;
    }
    /**
     * Calcule la somme des paiements par jour pour une entreprise
     */
    public function sumByEntrepriseAndDay($entreprise, \DateTime $date): float
    {
        [$startDate, $endDate] = DateRangeBuilder::dayRange($date);
        
        $result = $this->createQueryBuilder('pr')
            ->select('COALESCE(SUM(pr.montant), 0)')
            ->leftJoin('pr.reservation', 'r')
            ->where('r.entreprise = :entreprise')
            ->andWhere('pr.createdAt >= :dateStart')
            ->andWhere('pr.createdAt <= :dateEnd')
            ->setParameter('entreprise', $entreprise)
            ->setParameter('dateStart', DateRangeBuilder::formatForDQL($startDate))
            ->setParameter('dateEnd', DateRangeBuilder::formatForDQL($endDate))
            ->getQuery()
            ->getSingleScalarResult();

        return (float) $result;
    }

    /**
     * Calcule la somme des paiements par jour pour une boutique
     */
    public function sumByBoutiqueAndDay($boutique, \DateTime $date): float
    {
        [$startDate, $endDate] = DateRangeBuilder::dayRange($date);
        
        $result = $this->createQueryBuilder('pr')
            ->select('COALESCE(SUM(pr.montant), 0)')
            ->leftJoin('pr.reservation', 'r')
            ->where('r.boutique = :boutique')
            ->andWhere('pr.createdAt >= :dateStart')
            ->andWhere('pr.createdAt <= :dateEnd')
            ->setParameter('boutique', $boutique)
            ->setParameter('dateStart', DateRangeBuilder::formatForDQL($startDate))
            ->setParameter('dateEnd', DateRangeBuilder::formatForDQL($endDate))
            ->getQuery()
            ->getSingleScalarResult();

        return (float) $result;
    }

    /**
     * Compte les paiements par jour pour une entreprise
     */
    public function countByEntrepriseAndDay($entreprise, \DateTime $date): int
    {
        [$startDate, $endDate] = DateRangeBuilder::dayRange($date);
        
        return $this->createQueryBuilder('pr')
            ->select('COUNT(pr.id)')
            ->leftJoin('pr.reservation', 'r')
            ->where('r.entreprise = :entreprise')
            ->andWhere('pr.createdAt >= :dateStart')
            ->andWhere('pr.createdAt <= :dateEnd')
            ->setParameter('entreprise', $entreprise)
            ->setParameter('dateStart', DateRangeBuilder::formatForDQL($startDate))
            ->setParameter('dateEnd', DateRangeBuilder::formatForDQL($endDate))
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Compte les paiements par jour pour une boutique
     */
    public function countByBoutiqueAndDay($boutique, \DateTime $date): int
    {
        [$startDate, $endDate] = DateRangeBuilder::dayRange($date);
        
        return $this->createQueryBuilder('pr')
            ->select('COUNT(pr.id)')
            ->leftJoin('pr.reservation', 'r')
            ->where('r.boutique = :boutique')
            ->andWhere('pr.createdAt >= :dateStart')
            ->andWhere('pr.createdAt <= :dateEnd')
            ->setParameter('boutique', $boutique)
            ->setParameter('dateStart', DateRangeBuilder::formatForDQL($startDate))
            ->setParameter('dateEnd', DateRangeBuilder::formatForDQL($endDate))
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Récupère les modèles les plus réservés du dernier mois pour une boutique
     * Retourne les modèles avec leur quantité totale réservée
     */
    public function findTopReservedModelsOfWeek($boutique, int $limit = 10): array
    {
        // Utiliser le dernier mois (30 jours)
        $endDate = new \DateTime('now');
        $startDate = new \DateTime('-30 days');
        
        $startDateImmutable = \DateTimeImmutable::createFromMutable($startDate);
        $endDateImmutable = \DateTimeImmutable::createFromMutable($endDate);

        return $this->createQueryBuilder('pr')
            ->select('
                IDENTITY(m.modele) as modele_id,
                mo.libelle as modele_nom,
                SUM(lr.quantite) as quantite_totale,
                SUM(lr.avanceModele) as chiffre_affaires
            ')
            ->innerJoin('pr.reservation', 'r')
            ->innerJoin('r.ligneReservations', 'lr')
            ->innerJoin('lr.modele', 'm')
            ->innerJoin('m.modele', 'mo')
            ->where('r.boutique = :boutique')
            ->andWhere('pr.createdAt >= :startDate')
            ->andWhere('pr.createdAt <= :endDate')
            ->groupBy('mo.id, mo.libelle')
            ->orderBy('quantite_totale', 'DESC')
            ->addOrderBy('chiffre_affaires', 'DESC')
            ->setParameter('boutique', $boutique)
            ->setParameter('startDate', $startDateImmutable)
            ->setParameter('endDate', $endDateImmutable)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
