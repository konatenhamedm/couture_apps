<?php

namespace App\Tests\Unit\Repository\Exception;

use App\Repository\Exception\EntityValidationException;
use App\Repository\Exception\RepositoryException;
use PHPUnit\Framework\TestCase;

class EntityValidationExceptionTest extends TestCase
{
    public function testBasicCreation(): void
    {
        $entity = new \stdClass();
        $errors = ['field1' => 'error1', 'field2' => 'error2'];
        
        $exception = new EntityValidationException(
            'Validation failed',
            $errors,
            $entity,
            0,
            null,
            'TestRepository',
            'save'
        );

        $this->assertEquals('Validation failed', $exception->getMessage());
        $this->assertEquals($errors, $exception->getValidationErrors());
        $this->assertSame($entity, $exception->getEntity());
        $this->assertEquals('TestRepository', $exception->getRepositoryClass());
        $this->assertEquals('save', $exception->getMethodName());
    }

    public function testInvalidEntityTypeFactory(): void
    {
        $exception = EntityValidationException::invalidEntityType(
            'User',
            'stdClass',
            'UserRepository',
            'save'
        );

        $this->assertStringContainsString('Expected entity of type User, got stdClass', $exception->getMessage());
        $this->assertEquals('UserRepository', $exception->getRepositoryClass());
        $this->assertEquals('save', $exception->getMethodName());
        
        $errors = $exception->getValidationErrors();
        $this->assertEquals('User', $errors['expected_type']);
        $this->assertEquals('stdClass', $errors['actual_type']);
    }

    public function testValidationFailedFactory(): void
    {
        $entity = new \stdClass();
        $errors = ['name' => 'Name is required', 'email' => 'Invalid email'];
        
        $exception = EntityValidationException::validationFailed(
            $errors,
            $entity,
            'UserRepository',
            'save'
        );

        $this->assertStringContainsString('Entity validation failed: name, email', $exception->getMessage());
        $this->assertEquals($errors, $exception->getValidationErrors());
        $this->assertSame($entity, $exception->getEntity());
    }

    public function testInheritance(): void
    {
        $exception = new EntityValidationException();
        
        $this->assertInstanceOf(RepositoryException::class, $exception);
        $this->assertInstanceOf(\Exception::class, $exception);
    }

    public function testEmptyValidationErrors(): void
    {
        $exception = new EntityValidationException('Test message');
        
        $this->assertEquals([], $exception->getValidationErrors());
        $this->assertNull($exception->getEntity());
    }
}