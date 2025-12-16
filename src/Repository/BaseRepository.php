<?php

namespace App\Repository;

use App\Service\EntityManagerProvider;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository de base qui utilise automatiquement le bon environnement de base de données
 */
abstract class BaseRepository extends ServiceEntityRepository
{
    private EntityManagerProvider $entityManagerProvider;

    public function __construct(ManagerRegistry $registry, string $entityClass, EntityManagerProvider $entityManagerProvider)
    {
        parent::__construct($registry, $entityClass);
        $this->entityManagerProvider = $entityManagerProvider;
    }

    /**
     * Retourne l'EntityManager pour l'environnement actuel
     */
    protected function getEntityManagerForEnvironment(): EntityManagerInterface
    {
        return $this->entityManagerProvider->getEntityManager();
    }

    /**
     * Crée un QueryBuilder pour l'environnement actuel
     */
    public function createQueryBuilderForEnvironment(string $alias, $indexBy = null): QueryBuilder
    {
        return $this->getEntityManagerForEnvironment()->createQueryBuilder()
            ->select($alias)
            ->from($this->getEntityName(), $alias, $indexBy);
    }

    /**
     * Trouve toutes les entités dans l'environnement actuel
     */
    public function findAllInEnvironment(): array
    {
        return $this->createQueryBuilderForEnvironment('e')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve des entités par critères dans l'environnement actuel
     */
    public function findByInEnvironment(array $criteria, array $orderBy = [], $limit = null, $offset = null): array
    {
        $qb = $this->createQueryBuilderForEnvironment('e');

        // Ajouter les critères WHERE
        $paramIndex = 1;
        foreach ($criteria as $field => $value) {
            $qb->andWhere("e.{$field} = :param{$paramIndex}")
               ->setParameter("param{$paramIndex}", $value);
            $paramIndex++;
        }

        // Ajouter l'ORDER BY
        foreach ($orderBy as $field => $direction) {
            $qb->addOrderBy("e.{$field}", $direction);
        }

        // Ajouter LIMIT et OFFSET
        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }
        if ($offset !== null) {
            $qb->setFirstResult($offset);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Trouve une entité par critères dans l'environnement actuel
     */
    public function findOneByInEnvironment(array $criteria, array $orderBy = [])
    {
        $qb = $this->createQueryBuilderForEnvironment('e');

        // Ajouter les critères WHERE
        $paramIndex = 1;
        foreach ($criteria as $field => $value) {
            $qb->andWhere("e.{$field} = :param{$paramIndex}")
               ->setParameter("param{$paramIndex}", $value);
            $paramIndex++;
        }

        // Ajouter l'ORDER BY
        foreach ($orderBy as $field => $direction) {
            $qb->addOrderBy("e.{$field}", $direction);
        }

        $qb->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Trouve une entité par ID dans l'environnement actuel
     */
    public function findInEnvironment($id)
    {
        return $this->createQueryBuilderForEnvironment('e')
            ->where('e.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Compte les entités dans l'environnement actuel
     */
    public function countInEnvironment(array $criteria = []): int
    {
        $qb = $this->getEntityManagerForEnvironment()->createQueryBuilder()
            ->select('COUNT(e.id)')
            ->from($this->getEntityName(), 'e');

        // Ajouter les critères WHERE
        $paramIndex = 1;
        foreach ($criteria as $field => $value) {
            $qb->andWhere("e.{$field} = :param{$paramIndex}")
               ->setParameter("param{$paramIndex}", $value);
            $paramIndex++;
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Persiste et flush une entité dans l'environnement actuel
     */
    public function saveInEnvironment($entity, bool $flush = true): void
    {
        $em = $this->getEntityManagerForEnvironment();
        $em->persist($entity);
        
        if ($flush) {
            $em->flush();
        }
    }

    /**
     * Supprime une entité dans l'environnement actuel
     */
    public function removeInEnvironment($entity, bool $flush = true): void
    {
        $em = $this->getEntityManagerForEnvironment();
        $em->remove($entity);
        
        if ($flush) {
            $em->flush();
        }
    }
}