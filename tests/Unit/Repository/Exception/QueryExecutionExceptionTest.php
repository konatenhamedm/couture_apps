<?php

namespace App\Tests\Unit\Repository\Exception;

use App\Repository\Exception\QueryExecutionException;
use App\Repository\Exception\RepositoryException;
use PHPUnit\Framework\TestCase;

class QueryExecutionExceptionTest extends TestCase
{
    public function testBasicCreation(): void
    {
        $exception = new QueryExecutionException(
            'Query failed',
            'SELECT',
            'SELECT u FROM User u',
            ['id' => 1],
            0,
            null,
            'UserRepository',
            'findById'
        );

        $this->assertEquals('Query failed', $exception->getMessage());
        $this->assertEquals('SELECT', $exception->getQueryType());
        $this->assertEquals('SELECT u FROM User u', $exception->getDql());
        $this->assertEquals(['id' => 1], $exception->getParameters());
    }

    public function testSelectFailedFactory(): void
    {
        $previous = new \RuntimeException('Database error');
        $dql = 'SELECT u FROM User u WHERE u.id = :id';
        $params = ['id' => 1];
        
        $exception = QueryExecutionException::selectFailed(
            $dql,
            $params,
            $previous,
            'UserRepository',
            'findById'
        );

        $this->assertStringContainsString('SELECT query execution failed', $exception->getMessage());
        $this->assertStringContainsString('Database error', $exception->getMessage());
        $this->assertEquals('SELECT', $exception->getQueryType());
        $this->assertEquals($dql, $exception->getDql());
        $this->assertEquals($params, $exception->getParameters());
        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testCountFailedFactory(): void
    {
        $previous = new \RuntimeException('Count error');
        $dql = 'SELECT COUNT(u) FROM User u';
        
        $exception = QueryExecutionException::countFailed(
            $dql,
            [],
            $previous,
            'UserRepository',
            'countAll'
        );

        $this->assertStringContainsString('COUNT query execution failed', $exception->getMessage());
        $this->assertEquals('COUNT', $exception->getQueryType());
        $this->assertEquals($dql, $exception->getDql());
    }

    public function testUpdateFailedFactory(): void
    {
        $previous = new \RuntimeException('Update error');
        $dql = 'UPDATE User u SET u.status = :status WHERE u.id = :id';
        $params = ['status' => 'active', 'id' => 1];
        
        $exception = QueryExecutionException::updateFailed(
            $dql,
            $params,
            $previous,
            'UserRepository',
            'updateStatus'
        );

        $this->assertStringContainsString('UPDATE query execution failed', $exception->getMessage());
        $this->assertEquals('UPDATE', $exception->getQueryType());
    }

    public function testDeleteFailedFactory(): void
    {
        $previous = new \RuntimeException('Delete error');
        $dql = 'DELETE FROM User u WHERE u.id = :id';
        $params = ['id' => 1];
        
        $exception = QueryExecutionException::deleteFailed(
            $dql,
            $params,
            $previous,
            'UserRepository',
            'deleteById'
        );

        $this->assertStringContainsString('DELETE query execution failed', $exception->getMessage());
        $this->assertEquals('DELETE', $exception->getQueryType());
    }

    public function testInheritance(): void
    {
        $exception = new QueryExecutionException();
        
        $this->assertInstanceOf(RepositoryException::class, $exception);
    }
}