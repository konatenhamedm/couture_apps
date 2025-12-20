<?php

namespace App\Repository\Result;

class PaginationResult
{
    public function __construct(
        private array $items,
        private int $totalCount,
        private int $currentPage,
        private int $itemsPerPage
    ) {}
    
    public function getItems(): array
    {
        return $this->items;
    }
    
    public function getTotalCount(): int
    {
        return $this->totalCount;
    }
    
    public function getCurrentPage(): int
    {
        return $this->currentPage;
    }
    
    public function getItemsPerPage(): int
    {
        return $this->itemsPerPage;
    }
    
    public function getTotalPages(): int
    {
        return (int) ceil($this->totalCount / $this->itemsPerPage);
    }
    
    public function hasNextPage(): bool
    {
        return $this->currentPage < $this->getTotalPages();
    }
    
    public function hasPreviousPage(): bool
    {
        return $this->currentPage > 1;
    }
}