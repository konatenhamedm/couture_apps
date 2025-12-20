<?php

namespace App\Tests\Unit\Repository\Interface;

use App\Repository\Interface\StandardRepositoryInterface;
use App\Repository\Result\PaginationResult;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class StandardRepositoryInterfaceTest extends TestCase
{
    private ReflectionClass $interfaceReflection;

    protected function setUp(): void
    {
        $this->interfaceReflection = new ReflectionClass(StandardRepositoryInterface::class);
    }

    public function testInterfaceExists(): void
    {
        $this->assertTrue($this->interfaceReflection->isInterface());
        $this->assertEquals(StandardRepositoryInterface::class, $this->interfaceReflection->getName());
    }

    public function testInterfaceDefinesCrudMethods(): void
    {
        $expectedMethods = [
            'find',
            'findAll', 
            'findBy',
            'findOneBy',
            'save',
            'remove'
        ];

        foreach ($expectedMethods as $methodName) {
            $this->assertTrue(
                $this->interfaceReflection->hasMethod($methodName),
                "Interface should define method: {$methodName}"
            );
        }
    }

    public function testInterfaceDefinesCountingAndPaginationMethods(): void
    {
        $expectedMethods = [
            'count',
            'paginate'
        ];

        foreach ($expectedMethods as $methodName) {
            $this->assertTrue(
                $this->interfaceReflection->hasMethod($methodName),
                "Interface should define method: {$methodName}"
            );
        }
    }

    public function testInterfaceDefinesValidationMethods(): void
    {
        $expectedMethods = [
            'getEntityClass'
        ];

        foreach ($expectedMethods as $methodName) {
            $this->assertTrue(
                $this->interfaceReflection->hasMethod($methodName),
                "Interface should define method: {$methodName}"
            );
        }
    }

    public function testFindMethodSignature(): void
    {
        $method = $this->interfaceReflection->getMethod('find');
        $this->assertEquals('find', $method->getName());
        $this->assertCount(1, $method->getParameters());
        
        $parameter = $method->getParameters()[0];
        $this->assertEquals('id', $parameter->getName());
        $this->assertTrue($parameter->hasType());
        $this->assertEquals('mixed', $parameter->getType()->getName());
    }

    public function testFindByMethodSignature(): void
    {
        $method = $this->interfaceReflection->getMethod('findBy');
        $parameters = $method->getParameters();
        
        $this->assertCount(4, $parameters);
        $this->assertEquals('criteria', $parameters[0]->getName());
        $this->assertEquals('orderBy', $parameters[1]->getName());
        $this->assertEquals('limit', $parameters[2]->getName());
        $this->assertEquals('offset', $parameters[3]->getName());
        
        // Check that optional parameters have default values
        $this->assertTrue($parameters[1]->isOptional());
        $this->assertTrue($parameters[2]->isOptional());
        $this->assertTrue($parameters[3]->isOptional());
    }

    public function testSaveMethodSignature(): void
    {
        $method = $this->interfaceReflection->getMethod('save');
        $parameters = $method->getParameters();
        
        $this->assertCount(2, $parameters);
        $this->assertEquals('entity', $parameters[0]->getName());
        $this->assertEquals('flush', $parameters[1]->getName());
        
        // Check that flush parameter has default value
        $this->assertTrue($parameters[1]->isOptional());
        $this->assertTrue($parameters[1]->getDefaultValue());
    }

    public function testPaginateMethodSignature(): void
    {
        $method = $this->interfaceReflection->getMethod('paginate');
        $parameters = $method->getParameters();
        
        $this->assertCount(3, $parameters);
        $this->assertEquals('page', $parameters[0]->getName());
        $this->assertEquals('limit', $parameters[1]->getName());
        $this->assertEquals('criteria', $parameters[2]->getName());
        
        // Check default values
        $this->assertTrue($parameters[0]->isOptional());
        $this->assertEquals(1, $parameters[0]->getDefaultValue());
        $this->assertTrue($parameters[1]->isOptional());
        $this->assertEquals(20, $parameters[1]->getDefaultValue());
        $this->assertTrue($parameters[2]->isOptional());
    }

    public function testCountMethodSignature(): void
    {
        $method = $this->interfaceReflection->getMethod('count');
        $parameters = $method->getParameters();
        
        $this->assertCount(1, $parameters);
        $this->assertEquals('criteria', $parameters[0]->getName());
        $this->assertTrue($parameters[0]->isOptional());
    }
}