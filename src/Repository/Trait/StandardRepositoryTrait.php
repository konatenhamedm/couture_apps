<?php

namespace App\Repository\Trait;

use App\Repository\Result\PaginationResult;
use Doctrine\ORM\QueryBuilder;

trait StandardRepositoryTrait
{
    /**
     * Standardized save method to replace add() methods
     */
    public function save(object $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Standardized remove method (already exists in most repositories)
     */
    public function remove(object $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Pagination support
     */
    public function paginate(int $page = 1, int $limit = 20, array $criteria = []): PaginationResult
    {
        $qb = $this->createPaginatedQuery($criteria);
        $qb->setFirstResult(($page - 1) * $limit)
           ->setMaxResults($limit);
           
        return new PaginationResult(
            $qb->getQuery()->getResult(),
            $this->count($criteria),
            $page,
            $limit
        );
    }

    /**
     * Get entity class name (wrapper for getClassName)
     */
    public function getEntityClass(): string
    {
        return $this->getClassName();
    }

    /**
     * Create a query builder with filters applied
     */
    protected function createPaginatedQuery(array $criteria): QueryBuilder
    {
        $qb = $this->createQueryBuilder('e');
        return $this->applyFilters($qb, $criteria);
    }

    /**
     * Apply filters to a query builder
     */
    protected function applyFilters(QueryBuilder $qb, array $filters): QueryBuilder
    {
        foreach ($filters as $field => $value) {
            if ($value !== null) {
                $qb->andWhere("e.{$field} = :{$field}")
                   ->setParameter($field, $value);
            }
        }
        return $qb;
    }
}