<?php

namespace App\Tests\Unit\Repository\Exception;

use App\Repository\Exception\RepositoryException;
use PHPUnit\Framework\TestCase;

class RepositoryExceptionTest extends TestCase
{
    public function testBasicExceptionCreation(): void
    {
        $exception = new RepositoryException(
            'Test message',
            123,
            null,
            'TestRepository',
            'testMethod',
            ['key' => 'value']
        );

        $this->assertEquals('Test message', $exception->getMessage());
        $this->assertEquals(123, $exception->getCode());
        $this->assertEquals('TestRepository', $exception->getRepositoryClass());
        $this->assertEquals('testMethod', $exception->getMethodName());
        $this->assertEquals(['key' => 'value'], $exception->getContext());
    }

    public function testWithContextStaticMethod(): void
    {
        $exception = RepositoryException::withContext(
            'Context message',
            'MyRepository',
            'myMethod',
            ['param' => 'value']
        );

        $this->assertEquals('Context message', $exception->getMessage());
        $this->assertEquals('MyRepository', $exception->getRepositoryClass());
        $this->assertEquals('myMethod', $exception->getMethodName());
        $this->assertEquals(['param' => 'value'], $exception->getContext());
    }

    public function testFormattedMessage(): void
    {
        $exception = new RepositoryException(
            'Base message',
            0,
            null,
            'TestRepository',
            'testMethod',
            ['key' => 'value']
        );

        $formatted = $exception->getFormattedMessage();
        
        $this->assertStringContainsString('Base message', $formatted);
        $this->assertStringContainsString('[Repository: TestRepository]', $formatted);
        $this->assertStringContainsString('[Method: testMethod]', $formatted);
        $this->assertStringContainsString('[Context: {"key":"value"}]', $formatted);
    }

    public function testFormattedMessageWithoutContext(): void
    {
        $exception = new RepositoryException('Simple message');
        
        $formatted = $exception->getFormattedMessage();
        
        $this->assertEquals('Simple message', $formatted);
    }

    public function testExceptionInheritance(): void
    {
        $exception = new RepositoryException();
        
        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertInstanceOf(\Throwable::class, $exception);
    }

    public function testWithPreviousException(): void
    {
        $previous = new \RuntimeException('Previous error');
        $exception = RepositoryException::withContext(
            'New error',
            'TestRepo',
            'testMethod',
            [],
            $previous
        );

        $this->assertSame($previous, $exception->getPrevious());
    }
}