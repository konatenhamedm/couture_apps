<?php

namespace App\Tests\Property\Repository\Validation;

use App\Repository\Validation\Rules\NamingConventionRule;
use Eris\Generator;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

/**
 * Property 2: Method Naming Convention Consistency
 * 
 * This test validates that repository method naming conventions are consistently
 * applied and that the naming rule correctly identifies violations.
 */
class MethodNamingConventionTest extends TestCase
{
    use TestTrait;

    public function testValidMethodNamesPassValidation(): void
    {
        $this->forAll(
            Generator\elements([
                'find', 'findBy', 'findOneBy', 'findAll',
                'get', 'getBy', 'getOne', 'getAll',
                'create', 'createNew', 'createFrom',
                'save', 'saveEntity', 'saveAll',
                'remove', 'removeBy', 'removeAll',
                'delete', 'deleteBy', 'deleteAll',
                'update', 'updateBy', 'updateAll',
                'count', 'countBy', 'countAll',
                'exists', 'existsBy',
                'has', 'hasBy',
                'is', 'isValid', 'isActive',
                'search', 'searchBy',
                'filter', 'filterBy',
                'paginate'
            ]),
            Generator\elements(['Users', 'Products', 'Orders', 'Items', 'Data'])
        )->then(function (string $prefix, string $suffix) {
            $methodName = $prefix . $suffix;
            $rule = new NamingConventionRule();
            
            $mockClass = $this->createMockRepositoryClass('TestRepository', [$methodName]);
            $result = $rule->validate($mockClass);
            
            // Valid method names should not generate naming convention warnings
            $namingWarnings = array_filter($result->getWarnings(), function ($warning) use ($methodName) {
                return str_contains($warning, $methodName) && 
                       str_contains($warning, 'does not follow standard naming conventions');
            });
            
            $this->assertEmpty($namingWarnings, "Method '$methodName' should be valid but generated warnings: " . implode(', ', $namingWarnings));
        });
    }

    public function testInvalidMethodNamesGenerateWarnings(): void
    {
        $this->forAll(
            Generator\elements([
                'process', 'handle', 'execute', 'run', 'perform',
                'doSomething', 'makeStuff', 'buildThing',
                'randomMethod', 'weirdFunction', 'strangeAction'
            ])
        )->then(function (string $methodName) {
            $rule = new NamingConventionRule();
            
            $mockClass = $this->createMockRepositoryClass('TestRepository', [$methodName]);
            $result = $rule->validate($mockClass);
            
            // Invalid method names should generate warnings
            $namingWarnings = array_filter($result->getWarnings(), function ($warning) use ($methodName) {
                return str_contains($warning, $methodName) && 
                       str_contains($warning, 'does not follow standard naming conventions');
            });
            
            $this->assertNotEmpty($namingWarnings, "Method '$methodName' should generate naming convention warnings");
        });
    }

    public function testDeprecatedMethodPrefixesGenerateWarnings(): void
    {
        $this->forAll(
            Generator\elements(['User', 'Product', 'Order', 'Item']),
            Generator\elements(['', 'New', 'Entity', 'Record'])
        )->then(function (string $entityName, string $suffix) {
            $methodName = 'add' . $entityName . $suffix;
            $rule = new NamingConventionRule();
            
            $mockClass = $this->createMockRepositoryClass('TestRepository', [$methodName]);
            $result = $rule->validate($mockClass);
            
            // Methods with 'add' prefix should generate deprecation warnings
            $deprecationWarnings = array_filter($result->getWarnings(), function ($warning) {
                return str_contains($warning, 'deprecated prefix "add"');
            });
            
            $this->assertNotEmpty($deprecationWarnings, "Method '$methodName' should generate deprecation warning for 'add' prefix");
        });
    }

    public function testRepositoryClassNamingConvention(): void
    {
        $this->forAll(
            Generator\elements([
                ['className' => 'UserRepository', 'shouldPass' => true],
                ['className' => 'ProductRepository', 'shouldPass' => true],
                ['className' => 'OrderRepository', 'shouldPass' => true],
                ['className' => 'UserRepo', 'shouldPass' => false],
                ['className' => 'UserService', 'shouldPass' => false],
                ['className' => 'UserManager', 'shouldPass' => false],
                ['className' => 'User', 'shouldPass' => false]
            ])
        )->then(function (array $testCase) {
            $rule = new NamingConventionRule();
            
            $mockClass = $this->createMockRepositoryClass($testCase['className'], []);
            $result = $rule->validate($mockClass);
            
            $classNameErrors = array_filter($result->getErrors(), function ($error) {
                return str_contains($error, 'should end with "Repository"');
            });
            
            if ($testCase['shouldPass']) {
                $this->assertEmpty($classNameErrors, "Class '{$testCase['className']}' should pass naming validation");
            } else {
                $this->assertNotEmpty($classNameErrors, "Class '{$testCase['className']}' should fail naming validation");
            }
        });
    }

    public function testReturnTypePatternValidation(): void
    {
        $this->forAll(
            Generator\elements([
                ['methodName' => 'findByStatus', 'returnType' => 'array', 'shouldWarn' => false],
                ['methodName' => 'findByStatus', 'returnType' => 'User', 'shouldWarn' => true],
                ['methodName' => 'findOneByEmail', 'returnType' => 'User|null', 'shouldWarn' => false],
                ['methodName' => 'findOneByEmail', 'returnType' => 'array', 'shouldWarn' => true],
                ['methodName' => 'countActive', 'returnType' => 'int', 'shouldWarn' => false],
                ['methodName' => 'countActive', 'returnType' => 'string', 'shouldWarn' => true],
                ['methodName' => 'existsById', 'returnType' => 'bool', 'shouldWarn' => false],
                ['methodName' => 'existsById', 'returnType' => 'int', 'shouldWarn' => true],
                ['methodName' => 'hasPermission', 'returnType' => 'bool', 'shouldWarn' => false],
                ['methodName' => 'hasPermission', 'returnType' => 'array', 'shouldWarn' => true],
                ['methodName' => 'isActive', 'returnType' => 'bool', 'shouldWarn' => false],
                ['methodName' => 'isActive', 'returnType' => 'string', 'shouldWarn' => true]
            ])
        )->then(function (array $testCase) {
            $rule = new NamingConventionRule();
            
            $mockClass = $this->createMockRepositoryClassWithReturnTypes(
                'TestRepository', 
                [$testCase['methodName'] => $testCase['returnType']]
            );
            $result = $rule->validate($mockClass);
            
            $returnTypeWarnings = array_filter($result->getWarnings(), function ($warning) use ($testCase) {
                return str_contains($warning, $testCase['methodName']) && 
                       str_contains($warning, 'should return');
            });
            
            if ($testCase['shouldWarn']) {
                $this->assertNotEmpty($returnTypeWarnings, 
                    "Method '{$testCase['methodName']}' with return type '{$testCase['returnType']}' should generate warnings");
            } else {
                $this->assertEmpty($returnTypeWarnings, 
                    "Method '{$testCase['methodName']}' with return type '{$testCase['returnType']}' should not generate warnings");
            }
        });
    }

    public function testNamingRuleConsistencyAcrossMultipleMethods(): void
    {
        $this->forAll(
            Generator\choose(1, 10), // Number of methods
            Generator\choose(0, 1)   // Mix valid/invalid methods
        )->then(function (int $methodCount, int $mixType) {
            $rule = new NamingConventionRule();
            
            $methods = [];
            $expectedWarnings = 0;
            
            for ($i = 0; $i < $methodCount; $i++) {
                if ($mixType === 0) {
                    // All valid methods
                    $methods[] = 'findBy' . chr(65 + $i); // findByA, findByB, etc.
                } else {
                    // Mix of valid and invalid
                    if ($i % 2 === 0) {
                        $methods[] = 'findBy' . chr(65 + $i);
                    } else {
                        $methods[] = 'process' . chr(65 + $i);
                        $expectedWarnings++;
                    }
                }
            }
            
            $mockClass = $this->createMockRepositoryClass('TestRepository', $methods);
            $result = $rule->validate($mockClass);
            
            $namingWarnings = array_filter($result->getWarnings(), function ($warning) {
                return str_contains($warning, 'does not follow standard naming conventions');
            });
            
            $this->assertCount($expectedWarnings, $namingWarnings, 
                "Expected $expectedWarnings naming warnings, got " . count($namingWarnings));
        });
    }

    private function createMockRepositoryClass(string $className, array $methodNames): ReflectionClass
    {
        $mockClass = $this->createMock(ReflectionClass::class);
        $mockClass->method('getName')->willReturn($className);
        
        $mockMethods = [];
        foreach ($methodNames as $methodName) {
            $mockMethod = $this->createMock(ReflectionMethod::class);
            $mockMethod->method('getName')->willReturn($methodName);
            $mockMethod->method('isPublic')->willReturn(true);
            $mockMethod->method('hasReturnType')->willReturn(false);
            $mockMethods[] = $mockMethod;
        }
        
        $mockClass->method('getMethods')->willReturn($mockMethods);
        
        return $mockClass;
    }

    private function createMockRepositoryClassWithReturnTypes(string $className, array $methodsWithReturnTypes): ReflectionClass
    {
        $mockClass = $this->createMock(ReflectionClass::class);
        $mockClass->method('getName')->willReturn($className);
        
        $mockMethods = [];
        foreach ($methodsWithReturnTypes as $methodName => $returnType) {
            $mockMethod = $this->createMock(ReflectionMethod::class);
            $mockMethod->method('getName')->willReturn($methodName);
            $mockMethod->method('isPublic')->willReturn(true);
            $mockMethod->method('hasReturnType')->willReturn(true);
            
            $mockReturnType = $this->createMock(\ReflectionType::class);
            $mockReturnType->method('__toString')->willReturn($returnType);
            $mockMethod->method('getReturnType')->willReturn($mockReturnType);
            
            $mockMethods[] = $mockMethod;
        }
        
        $mockClass->method('getMethods')->willReturn($mockMethods);
        
        return $mockClass;
    }
}