<?php

namespace App\Tests\Unit\Repository\Result;

use App\Repository\Result\PaginationResult;
use PHPUnit\Framework\TestCase;

class PaginationResultTest extends TestCase
{
    public function testPaginationResultCreation(): void
    {
        $items = ['item1', 'item2', 'item3'];
        $totalCount = 100;
        $currentPage = 2;
        $itemsPerPage = 10;

        $result = new PaginationResult($items, $totalCount, $currentPage, $itemsPerPage);

        $this->assertEquals($items, $result->getItems());
        $this->assertEquals($totalCount, $result->getTotalCount());
        $this->assertEquals($currentPage, $result->getCurrentPage());
        $this->assertEquals($itemsPerPage, $result->getItemsPerPage());
    }

    public function testGetTotalPages(): void
    {
        $result = new PaginationResult([], 100, 1, 10);
        $this->assertEquals(10, $result->getTotalPages());

        $result = new PaginationResult([], 95, 1, 10);
        $this->assertEquals(10, $result->getTotalPages());

        $result = new PaginationResult([], 0, 1, 10);
        $this->assertEquals(0, $result->getTotalPages());
    }

    public function testHasNextPage(): void
    {
        // Has next page
        $result = new PaginationResult([], 100, 5, 10);
        $this->assertTrue($result->hasNextPage());

        // No next page (last page)
        $result = new PaginationResult([], 100, 10, 10);
        $this->assertFalse($result->hasNextPage());

        // No next page (beyond last page)
        $result = new PaginationResult([], 100, 11, 10);
        $this->assertFalse($result->hasNextPage());
    }

    public function testHasPreviousPage(): void
    {
        // Has previous page
        $result = new PaginationResult([], 100, 5, 10);
        $this->assertTrue($result->hasPreviousPage());

        // No previous page (first page)
        $result = new PaginationResult([], 100, 1, 10);
        $this->assertFalse($result->hasPreviousPage());

        // No previous page (page 0 - edge case)
        $result = new PaginationResult([], 100, 0, 10);
        $this->assertFalse($result->hasPreviousPage());
    }

    public function testEdgeCases(): void
    {
        // Empty result set
        $result = new PaginationResult([], 0, 1, 10);
        $this->assertEquals([], $result->getItems());
        $this->assertEquals(0, $result->getTotalCount());
        $this->assertEquals(0, $result->getTotalPages());
        $this->assertFalse($result->hasNextPage());
        $this->assertFalse($result->hasPreviousPage());

        // Single item
        $result = new PaginationResult(['item'], 1, 1, 10);
        $this->assertEquals(['item'], $result->getItems());
        $this->assertEquals(1, $result->getTotalCount());
        $this->assertEquals(1, $result->getTotalPages());
        $this->assertFalse($result->hasNextPage());
        $this->assertFalse($result->hasPreviousPage());
    }
}