<?php

namespace App\Repository;

use App\Entity\CategorieMesure;
use App\Service\EntityManagerProvider;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<CategorieMesure>
 */
class CategorieMesureRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry, EntityManagerProvider $entityManagerProvider)
    {
        parent::__construct($registry, CategorieMesure::class, $entityManagerProvider);
    }
    public function add(CategorieMesure $entity, bool $flush = false): void
    {
        $this->saveInEnvironment($entity, $flush);
    }

    public function remove(CategorieMesure $entity, bool $flush = false): void
    {
        $this->removeInEnvironment($entity, $flush);
    }

    /**
     * Trouve toutes les catégories actives dans l'environnement actuel
     */
    public function findActiveCategories(): array
    {
        return $this->findByInEnvironment(['isActive' => true], ['libelle' => 'ASC']);
    }

    /**
     * Trouve les catégories par entreprise dans l'environnement actuel
     */
    public function findByEntreprise($entreprise): array
    {
        return $this->findByInEnvironment(['entreprise' => $entreprise, 'isActive' => true], ['libelle' => 'ASC']);
    }
//    /**
//     * @return CategorieMesure[] Returns an array of CategorieMesure objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('c.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?CategorieMesure
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
