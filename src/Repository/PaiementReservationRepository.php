<?php

namespace App\Repository;

use App\Entity\Boutique;
use App\Entity\PaiementReservation;
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
        // Créer les dates de début et fin avec les bonnes heures
        $dateDebutStart = clone $dateDebut;
        $dateDebutStart->setTime(0, 0, 0);
        $dateFinEnd = clone $dateFin;
        $dateFinEnd->setTime(23, 59, 59);
        
        $result = $this->createQueryBuilder('pr')
            ->select('SUM(pr.montant)')
            ->leftJoin('pr.reservation', 'r')
            ->where('r.entreprise = :entreprise')
            ->andWhere('pr.createdAt >= :dateDebut')
            ->andWhere('pr.createdAt <= :dateFin')
            ->setParameter('entreprise', $entreprise)
            ->setParameter('dateDebut', $dateDebutStart)
            ->setParameter('dateFin', $dateFinEnd)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ?? 0;
    }

    /**
     * Calcule le total des paiements pour une boutique dans une période
     */
    public function sumByBoutiqueAndPeriod($boutique, \DateTime $dateDebut, \DateTime $dateFin): float
    {
        // Créer les dates de début et fin avec les bonnes heures
        $dateDebutStart = clone $dateDebut;
        $dateDebutStart->setTime(0, 0, 0);
        $dateFinEnd = clone $dateFin;
        $dateFinEnd->setTime(23, 59, 59);
        
        $result = $this->createQueryBuilder('pr')
            ->select('SUM(pr.montant)')
            ->leftJoin('pr.reservation', 'r')
            ->where('r.boutique = :boutique')
            ->andWhere('pr.createdAt >= :dateDebut')
            ->andWhere('pr.createdAt <= :dateFin')
            ->setParameter('boutique', $boutique)
            ->setParameter('dateDebut', $dateDebutStart)
            ->setParameter('dateFin', $dateFinEnd)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ?? 0;
    }
    /**
     * Calcule la somme des paiements par jour pour une entreprise
     */
    public function sumByEntrepriseAndDay($entreprise, \DateTime $date): float
    {
        $dateStart = clone $date;
        $dateStart->setTime(0, 0, 0);
        $dateEnd = clone $date;
        $dateEnd->setTime(23, 59, 59);
        
        $result = $this->createQueryBuilder('pr')
            ->select('SUM(pr.montant)')
            ->leftJoin('pr.reservation', 'r')
            ->where('r.entreprise = :entreprise')
            ->andWhere('pr.createdAt >= :dateStart')
            ->andWhere('pr.createdAt <= :dateEnd')
            ->setParameter('entreprise', $entreprise)
            ->setParameter('dateStart', $dateStart)
            ->setParameter('dateEnd', $dateEnd)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ?? 0;
    }

    /**
     * Calcule la somme des paiements par jour pour une boutique
     */
    public function sumByBoutiqueAndDay($boutique, \DateTime $date): float
    {
        $dateStart = clone $date;
        $dateStart->setTime(0, 0, 0);
        $dateEnd = clone $date;
        $dateEnd->setTime(23, 59, 59);
        
        $result = $this->createQueryBuilder('pr')
            ->select('SUM(pr.montant)')
            ->leftJoin('pr.reservation', 'r')
            ->where('r.boutique = :boutique')
            ->andWhere('pr.createdAt >= :dateStart')
            ->andWhere('pr.createdAt <= :dateEnd')
            ->setParameter('boutique', $boutique)
            ->setParameter('dateStart', $dateStart)
            ->setParameter('dateEnd', $dateEnd)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ?? 0;
    }

    /**
     * Récupère les modèles les plus réservés de la semaine pour une boutique
     * Retourne les modèles avec leur quantité totale réservée
     */
    public function findTopReservedModelsOfWeek($boutique, int $limit = 10): array
    {
        $startOfWeek = new \DateTime('monday this week');
        $endOfWeek = new \DateTime('sunday this week 23:59:59');
        
        $startOfWeekImmutable = \DateTimeImmutable::createFromMutable($startOfWeek);
        $endOfWeekImmutable = \DateTimeImmutable::createFromMutable($endOfWeek);

        return $this->createQueryBuilder('pr')
            ->select('
                m.id as modele_id,
                mo.libelle as modele_nom,
                SUM(lr.quantite) as quantite_totale,
                SUM(lr.avanceModele) as chiffre_affaires
            ')
            ->leftJoin('pr.reservation', 'r')
            ->leftJoin('r.ligneReservations', 'lr')
            ->leftJoin('lr.modele', 'm')
            ->leftJoin('m.modele', 'mo')
            ->where('r.boutique = :boutique')
            ->andWhere('pr.createdAt >= :startOfWeek')
            ->andWhere('pr.createdAt <= :endOfWeek')
            ->andWhere('pr.isActive = :active')
            ->groupBy('m.id, mo.libelle')
            ->orderBy('quantite_totale', 'DESC')
            ->addOrderBy('chiffre_affaires', 'DESC')
            ->setParameter('boutique', $boutique)
            ->setParameter('startOfWeek', $startOfWeekImmutable)
            ->setParameter('endOfWeek', $endOfWeekImmutable)
            ->setParameter('active', true)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
