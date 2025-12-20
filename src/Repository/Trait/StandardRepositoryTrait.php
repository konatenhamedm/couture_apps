<?php

namespace App\Repository\Trait;

use App\Repository\Result\PaginationResult;
use App\Repository\Exception\QueryExecutionException;
use App\Repository\Exception\InvalidParameterException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query;

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
     * Enhanced pagination support with sorting
     */
    public function paginate(int $page = 1, int $limit = 20, array $criteria = []): PaginationResult
    {
        // Validate pagination parameters
        if ($page < 1) {
            throw InvalidParameterException::invalidPaginationParameter(
                'page',
                $page,
                static::class,
                'paginate'
            );
        }
        
        if ($limit < 1 || $limit > 1000) {
            throw InvalidParameterException::outOfRange(
                'limit',
                $limit,
                1,
                1000,
                static::class,
                'paginate'
            );
        }
        
        $qb = $this->createPaginatedQuery($criteria);
        
        // Apply sorting if specified in criteria
        if (isset($criteria['_sort'])) {
            $sortField = $criteria['_sort'];
            $sortDirection = $criteria['_order'] ?? 'ASC';
            $qb->orderBy("e.{$sortField}", $sortDirection);
            
            // Remove sort criteria from filters
            unset($criteria['_sort'], $criteria['_order']);
        }
        
        $qb->setFirstResult(($page - 1) * $limit)
           ->setMaxResults($limit);
           
        return new PaginationResult(
            $this->executeQuery($qb->getQuery()),
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
     * Enhanced findBy with better parameter handling
     */
    public function findByWithOptions(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array
    {
        $qb = $this->createQueryBuilder('e');
        
        // Apply filters
        $this->applyFilters($qb, $criteria);
        
        // Apply ordering
        if ($orderBy) {
            foreach ($orderBy as $field => $direction) {
                $qb->addOrderBy("e.{$field}", $direction);
            }
        }
        
        // Apply limit and offset
        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }
        
        if ($offset !== null) {
            $qb->setFirstResult($offset);
        }
        
        return $qb->getQuery()->getResult();
    }

    /**
     * Find entities with advanced filtering support
     */
    public function findWithFilters(array $filters = []): array
    {
        $qb = $this->createQueryBuilder('e');
        $this->applyAdvancedFilters($qb, $filters);
        
        return $qb->getQuery()->getResult();
    }

    /**
     * Count entities with filters
     */
    public function countWithFilters(array $filters = []): int
    {
        $qb = $this->createQueryBuilder('e')
            ->select('COUNT(e.id)');
            
        $this->applyAdvancedFilters($qb, $filters);
        
        return (int) $qb->getQuery()->getSingleScalarResult();
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
     * Apply basic filters to a query builder
     */
    protected function applyFilters(QueryBuilder $qb, array $filters): QueryBuilder
    {
        foreach ($filters as $field => $value) {
            if ($value !== null && !str_starts_with($field, '_')) {
                if (is_array($value)) {
                    $qb->andWhere("e.{$field} IN (:{$field})")
                       ->setParameter($field, $value);
                } else {
                    $qb->andWhere("e.{$field} = :{$field}")
                       ->setParameter($field, $value);
                }
            }
        }
        return $qb;
    }

    /**
     * Apply advanced filters with operators
     */
    protected function applyAdvancedFilters(QueryBuilder $qb, array $filters): QueryBuilder
    {
        foreach ($filters as $field => $condition) {
            if (str_starts_with($field, '_')) {
                continue; // Skip meta fields
            }

            if (is_array($condition) && isset($condition['operator'])) {
                $this->applyFilterWithOperator($qb, $field, $condition);
            } else {
                // Simple equality filter
                if ($condition !== null) {
                    $qb->andWhere("e.{$field} = :{$field}")
                       ->setParameter($field, $condition);
                }
            }
        }
        
        return $qb;
    }

    /**
     * Apply filter with specific operator
     */
    protected function applyFilterWithOperator(QueryBuilder $qb, string $field, array $condition): void
    {
        $operator = $condition['operator'];
        $value = $condition['value'];
        $paramName = str_replace('.', '_', $field);

        switch ($operator) {
            case 'eq':
                $qb->andWhere("e.{$field} = :{$paramName}")
                   ->setParameter($paramName, $value);
                break;
                
            case 'neq':
                $qb->andWhere("e.{$field} != :{$paramName}")
                   ->setParameter($paramName, $value);
                break;
                
            case 'gt':
                $qb->andWhere("e.{$field} > :{$paramName}")
                   ->setParameter($paramName, $value);
                break;
                
            case 'gte':
                $qb->andWhere("e.{$field} >= :{$paramName}")
                   ->setParameter($paramName, $value);
                break;
                
            case 'lt':
                $qb->andWhere("e.{$field} < :{$paramName}")
                   ->setParameter($paramName, $value);
                break;
                
            case 'lte':
                $qb->andWhere("e.{$field} <= :{$paramName}")
                   ->setParameter($paramName, $value);
                break;
                
            case 'like':
                $qb->andWhere("e.{$field} LIKE :{$paramName}")
                   ->setParameter($paramName, "%{$value}%");
                break;
                
            case 'in':
                $qb->andWhere("e.{$field} IN (:{$paramName})")
                   ->setParameter($paramName, $value);
                break;
                
            case 'not_in':
                $qb->andWhere("e.{$field} NOT IN (:{$paramName})")
                   ->setParameter($paramName, $value);
                break;
                
            case 'between':
                if (is_array($value) && count($value) === 2) {
                    $qb->andWhere("e.{$field} BETWEEN :{$paramName}_start AND :{$paramName}_end")
                       ->setParameter("{$paramName}_start", $value[0])
                       ->setParameter("{$paramName}_end", $value[1]);
                }
                break;
                
            case 'is_null':
                $qb->andWhere("e.{$field} IS NULL");
                break;
                
            case 'is_not_null':
                $qb->andWhere("e.{$field} IS NOT NULL");
                break;
        }
    }

    /**
     * Validate query parameters to prevent injection
     */
    protected function validateQueryParameters(array $parameters): array
    {
        $validated = [];
        
        foreach ($parameters as $key => $value) {
            // Remove potentially dangerous characters from field names
            $cleanKey = preg_replace('/[^a-zA-Z0-9_.]/', '', $key);
            
            if ($cleanKey === $key) {
                $validated[$cleanKey] = $value;
            }
        }
        
        return $validated;
    }

    /**
     * Execute query with error handling
     */
    protected function executeQuery(Query $query): array
    {
        try {
            return $query->getResult();
        } catch (\Exception $e) {
            // Log the error and re-throw with more context
            error_log("Repository query error in " . static::class . ": " . $e->getMessage());
            
            throw QueryExecutionException::selectFailed(
                $query->getDQL(),
                $query->getParameters(),
                $e,
                static::class,
                debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'] ?? 'unknown'
            );
        }
    }

    /**
     * Execute scalar query with error handling
     */
    protected function executeScalarQuery(Query $query): mixed
    {
        try {
            return $query->getSingleScalarResult();
        } catch (\Exception $e) {
            error_log("Repository scalar query error in " . static::class . ": " . $e->getMessage());
            
            throw QueryExecutionException::countFailed(
                $query->getDQL(),
                $query->getParameters(),
                $e,
                static::class,
                debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'] ?? 'unknown'
            );
        }
    }
}