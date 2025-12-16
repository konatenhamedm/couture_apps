<?php

namespace App\Trait;

use App\Service\EntityManagerProvider;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

/**
 * Trait pour automatiser l'utilisation du bon environnement de base de données
 */
trait DatabaseEnvironmentTrait
{
    private ?EntityManagerProvider $entityManagerProvider = null;

    /**
     * Injection automatique du EntityManagerProvider
     */
    public function setEntityManagerProvider(EntityManagerProvider $entityManagerProvider): void
    {
        $this->entityManagerProvider = $entityManagerProvider;
    }

    /**
     * Retourne l'EntityManager pour l'environnement actuel
     */
    protected function getEntityManager(): EntityManagerInterface
    {
        if (!$this->entityManagerProvider) {
            throw new \RuntimeException('EntityManagerProvider not injected. Make sure to use the trait in a service with autowiring.');
        }
        
        return $this->entityManagerProvider->getEntityManager();
    }

    /**
     * Retourne un repository pour l'environnement actuel
     */
    protected function getRepository(string $entityClass)
    {
        return $this->getEntityManager()->getRepository($entityClass);
    }

    /**
     * Crée un QueryBuilder pour éviter le cache des repositories
     */
    protected function createQueryBuilder(): QueryBuilder
    {
        return $this->getEntityManager()->createQueryBuilder();
    }

    /**
     * Trouve toutes les entités d'une classe donnée (sans cache)
     */
    protected function findAll(string $entityClass): array
    {
        $queryBuilder = $this->createQueryBuilder();
        $queryBuilder->select('e')
                    ->from($entityClass, 'e');
        
        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * Trouve des entités par critères (sans cache)
     */
    protected function findBy(string $entityClass, array $criteria = [], array $orderBy = [], int $limit = null, int $offset = null): array
    {
        $queryBuilder = $this->createQueryBuilder();
        $queryBuilder->select('e')
                    ->from($entityClass, 'e');

        // Ajouter les critères WHERE
        $paramIndex = 1;
        foreach ($criteria as $field => $value) {
            $queryBuilder->andWhere("e.{$field} = :param{$paramIndex}")
                        ->setParameter("param{$paramIndex}", $value);
            $paramIndex++;
        }

        // Ajouter l'ORDER BY
        foreach ($orderBy as $field => $direction) {
            $queryBuilder->addOrderBy("e.{$field}", $direction);
        }

        // Ajouter LIMIT et OFFSET
        if ($limit !== null) {
            $queryBuilder->setMaxResults($limit);
        }
        if ($offset !== null) {
            $queryBuilder->setFirstResult($offset);
        }

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * Trouve une entité par son ID (sans cache)
     */
    protected function find(string $entityClass, $id)
    {
        $queryBuilder = $this->createQueryBuilder();
        $queryBuilder->select('e')
                    ->from($entityClass, 'e')
                    ->where('e.id = :id')
                    ->setParameter('id', $id);

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    /**
     * Trouve une entité par critères (sans cache) - retourne une seule entité
     */
    protected function findOneBy(string $entityClass, array $criteria = [], array $orderBy = [])
    {
        $queryBuilder = $this->createQueryBuilder();
        $queryBuilder->select('e')
                    ->from($entityClass, 'e');

        // Ajouter les critères WHERE
        $paramIndex = 1;
        foreach ($criteria as $field => $value) {
            $queryBuilder->andWhere("e.{$field} = :param{$paramIndex}")
                        ->setParameter("param{$paramIndex}", $value);
            $paramIndex++;
        }

        // Ajouter l'ORDER BY
        foreach ($orderBy as $field => $direction) {
            $queryBuilder->addOrderBy("e.{$field}", $direction);
        }

        // Limiter à un seul résultat
        $queryBuilder->setMaxResults(1);

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    /**
     * Compte les entités d'une classe donnée
     */
    protected function count(string $entityClass, array $criteria = []): int
    {
        $queryBuilder = $this->createQueryBuilder();
        $queryBuilder->select('COUNT(e.id)')
                    ->from($entityClass, 'e');

        // Ajouter les critères WHERE
        $paramIndex = 1;
        foreach ($criteria as $field => $value) {
            $queryBuilder->andWhere("e.{$field} = :param{$paramIndex}")
                        ->setParameter("param{$paramIndex}", $value);
            $paramIndex++;
        }

        return (int) $queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * Persiste et flush une entité
     */
    protected function save($entity, bool $flush = true): void
    {
        $em = $this->getEntityManager();
        $em->persist($entity);
        
        if ($flush) {
            $em->flush();
        }
    }

    /**
     * Supprime une entité
     */
    protected function remove($entity, bool $flush = true): void
    {
        $em = $this->getEntityManager();
        $em->remove($entity);
        
        if ($flush) {
            $em->flush();
        }
    }
}