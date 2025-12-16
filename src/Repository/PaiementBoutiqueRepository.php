<?php

namespace App\Repository;

use App\Entity\PaiementBoutique;
use App\Entity\Boutique;
use App\Service\EntityManagerProvider;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<PaiementBoutique>
 */
class PaiementBoutiqueRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry, EntityManagerProvider $entityManagerProvider)
    {
        parent::__construct($registry, PaiementBoutique::class, $entityManagerProvider);
    }

     public function add(PaiementBoutique $entity, bool $flush = false): void
    {
        $this->saveInEnvironment($entity, $flush);
    }

    public function remove(PaiementBoutique $entity, bool $flush = false): void
    {
        $this->removeInEnvironment($entity, $flush);
    }

    /**
     * Trouve les paiements boutique par boutique et période
     */
    public function findByBoutiqueAndPeriod(Boutique $boutique, \DateTime $dateDebut, \DateTime $dateFin): array
    {
        return $this->createQueryBuilderForEnvironment('p')
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
        return $this->createQueryBuilderForEnvironment('p')
            ->select('COUNT(p.id)')
            ->where('p.boutique = :boutique')
            ->setParameter('boutique', $boutique)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Récupère tous les paiements d'une boutique avec leurs dates (pour debug)
     */
    public function findAllByBoutiqueWithDates(Boutique $boutique): array
    {
        return $this->createQueryBuilderForEnvironment('p')
            ->select('p.id', 'p.montant', 'p.reference', 'p.createdAt', 'p.isActive')
            ->where('p.boutique = :boutique')
            ->setParameter('boutique', $boutique)
            ->orderBy('p.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve tous les paiements d'une boutique sans filtre de date
     */
    public function findAllByBoutique(Boutique $boutique): array
    {
        return $this->createQueryBuilderForEnvironment('p')
            ->where('p.boutique = :boutique')
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
        $result = $this->createQueryBuilderForEnvironment('pb')
            ->select('SUM(pb.montant)')
            ->leftJoin('pb.boutique', 'b')
            ->where('b.entreprise = :entreprise')
            ->andWhere('DATE(pb.createdAt) >= DATE(:dateDebut)')
            ->andWhere('DATE(pb.createdAt) <= DATE(:dateFin)')
            ->setParameter('entreprise', $entreprise)
            ->setParameter('dateDebut', $dateDebut->format('Y-m-d'))
            ->setParameter('dateFin', $dateFin->format('Y-m-d'))
            ->getQuery()
            ->getSingleScalarResult();

        return $result ?? 0;
    }

    /**
     * Calcule le total des paiements pour une boutique dans une période
     */
    public function sumByBoutiqueAndPeriod(Boutique $boutique, \DateTime $dateDebut, \DateTime $dateFin): float
    {
        $result = $this->createQueryBuilderForEnvironment('pb')
            ->select('SUM(pb.montant)')
            ->where('pb.boutique = :boutique')
            ->andWhere('DATE(pb.createdAt) >= DATE(:dateDebut)')
            ->andWhere('DATE(pb.createdAt) <= DATE(:dateFin)')
            ->setParameter('boutique', $boutique)
            ->setParameter('dateDebut', $dateDebut->format('Y-m-d'))
            ->setParameter('dateFin', $dateFin->format('Y-m-d'))
            ->getQuery()
            ->getSingleScalarResult();

        return $result ?? 0;
    }

    /**
     * Compte les paiements par jour pour une entreprise
     */
    public function countByEntrepriseAndDay($entreprise, \DateTime $date): int
    {
        return $this->createQueryBuilderForEnvironment('pb')
            ->select('COUNT(pb.id)')
            ->leftJoin('pb.boutique', 'b')
            ->where('b.entreprise = :entreprise')
            ->andWhere('DATE(pb.createdAt) = DATE(:date)')
            ->setParameter('entreprise', $entreprise)
            ->setParameter('date', $date->format('Y-m-d'))
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Compte les paiements par jour pour une boutique
     */
    public function countByBoutiqueAndDay(Boutique $boutique, \DateTime $date): int
    {
        return $this->createQueryBuilderForEnvironment('pb')
            ->select('COUNT(pb.id)')
            ->where('pb.boutique = :boutique')
            ->andWhere('DATE(pb.createdAt) = DATE(:date)')
            ->setParameter('boutique', $boutique)
            ->setParameter('date', $date->format('Y-m-d'))
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Récupère les derniers paiements pour une entreprise
     */
    public function findLatestByEntreprise($entreprise, int $limit = 5): array
    {
        return $this->createQueryBuilderForEnvironment('pb')
            ->leftJoin('pb.boutique', 'b')
            ->where('b.entreprise = :entreprise')
            ->orderBy('pb.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setParameter('entreprise', $entreprise)
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les derniers paiements pour une boutique
     */
    public function findLatestByBoutique(Boutique $boutique, int $limit = 5): array
    {
        return $this->createQueryBuilderForEnvironment('pb')
            ->where('pb.boutique = :boutique')
            ->orderBy('pb.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setParameter('boutique', $boutique)
            ->getQuery()
            ->getResult();
    }
    /**
     * Calcule la somme des paiements par jour pour une entreprise
     */
    public function sumByEntrepriseAndDay($entreprise, \DateTime $date): float
    {
        $result = $this->createQueryBuilderForEnvironment('pb')
            ->select('SUM(pb.montant)')
            ->leftJoin('pb.boutique', 'b')
            ->where('b.entreprise = :entreprise')
            ->andWhere('DATE(pb.createdAt) = DATE(:date)')
            ->setParameter('entreprise', $entreprise)
            ->setParameter('date', $date->format('Y-m-d'))
            ->getQuery()
            ->getSingleScalarResult();

        return $result ?? 0;
    }

    /**
     * Calcule la somme des paiements par jour pour une boutique
     */
    public function sumByBoutiqueAndDay(Boutique $boutique, \DateTime $date): float
    {
        $result = $this->createQueryBuilderForEnvironment('pb')
            ->select('SUM(pb.montant)')
            ->where('pb.boutique = :boutique')
            ->andWhere('DATE(pb.createdAt) = DATE(:date)')
            ->setParameter('boutique', $boutique)
            ->setParameter('date', $date->format('Y-m-d'))
            ->getQuery()
            ->getSingleScalarResult();

        return $result ?? 0;
    }

    /**
     * Récupère les meilleures ventes du dernier mois pour une boutique
     * Retourne les modèles les plus vendus avec leur quantité totale et chiffre d'affaires
     */
    public function findTopSellingModelsOfWeek($boutique, int $limit = 10): array
    {
        // Utiliser le dernier mois (30 jours)
        $endDate = new \DateTime('now');
        $startDate = new \DateTime('-30 days');
        
        $startDateImmutable = \DateTimeImmutable::createFromMutable($startDate);
        $endDateImmutable = \DateTimeImmutable::createFromMutable($endDate);

        return $this->createQueryBuilderForEnvironment('pb')
            ->select('
                IDENTITY(m.modele) as modele_id,
                mo.libelle as modele_nom,
                SUM(pbl.quantite) as quantite_totale,
                SUM(pbl.montant) as chiffre_affaires
            ')
            ->innerJoin('pb.paiementBoutiqueLignes', 'pbl')
            ->innerJoin('pbl.modeleBoutique', 'm')
            ->innerJoin('m.modele', 'mo')
            ->where('pb.boutique = :boutique')
            ->andWhere('pb.createdAt >= :startDate')
            ->andWhere('pb.createdAt <= :endDate')
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