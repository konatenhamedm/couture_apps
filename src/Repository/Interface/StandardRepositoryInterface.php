<?php

namespace App\Repository\Interface;

use App\Repository\Result\PaginationResult;

interface StandardRepositoryInterface
{
    // Core CRUD operations (already available in ServiceEntityRepository)
    public function find(mixed $id): ?object;
    public function findAll(): array;
    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array;
    public function findOneBy(array $criteria): ?object;
    
    // Standardized save/remove methods (to replace add/remove)
    public function save(object $entity, bool $flush = true): void;
    public function remove(object $entity, bool $flush = true): void;
    
    // Counting and pagination (count already exists in ServiceEntityRepository)
    public function count(array $criteria = []): int;
    public function paginate(int $page = 1, int $limit = 20, array $criteria = []): PaginationResult;
    
    // Entity class information (getClassName already exists in ServiceEntityRepository)
    public function getEntityClass(): string;
}