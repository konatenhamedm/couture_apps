<?php

namespace App\Tests\Property\Repository\Validation;

use App\Repository\Validation\Rules\ReturnTypeRule;
use Eris\Generator;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

/**
 * Property 9: Return Type Consistency
 * 
 * This test validates that repository methods have consistent return types
 * based on their naming patterns and that the return type rule correctly
 * identifies inconsistencies.
 */
class ReturnTypeConsistencyTest extends TestCase
{
    use TestTrait;

    public function testFindMethodsReturnArrayTypes(): void
    {
        $this->forAll(
            Generator\elements(['findByStatus', 'findByCategory', 'findByDate', 'findActive', 'findAllActive']),
            Generator\elements(['array', 'User[]', 'Product[]', 'array<User>'])
        )->then(function (string $methodName, string $returnType) {
            $rule = new ReturnTypeRule();
            
            $mockClass = $this->createMockRepositoryClassWithReturnType(
                'TestRepository',
                $methodName,
                $returnType
            );
            
            $result = $rule->validate($mockClass);
            
            // Array-like return types should not generate warnings for find methods
            $returnTypeWarnings = $this->getReturnTypeWarnings($result, $methodName);
            
            if (str_contains($returnType, 'array') || str_contains($returnType, '[]') || str_contains($returnType, 'Collection')) {
                $this->assertEmpty($returnTypeWarnings, 
                    "Method '$methodName' with return type '$returnType' should not generate warnings");
            }
        });
    }

    public function testFindOneMethodsReturnSingleEntityTypes(): void
    {
        $this->forAll(
            Generator\elements(['findOneByEmail', 'findOneById', 'findOneBySlug', 'findOne']),
            Generator\elements(['User|null', '?User', 'Product|null', '?Product', 'null|Order'])
        )->then(function (string $methodName, string $returnType) {
            $rule = new ReturnTypeRule();
            
            $mockClass = $this->createMockRepositoryClassWithReturnType(
                'TestRepository',
                $methodName,
                $returnType
            );
            
            $result = $rule->validate($mockClass);
            
            // Nullable entity types should not generate warnings for findOne methods
            $returnTypeWarnings = $this->getReturnTypeWarnings($result, $methodName);
            
            $this->assertEmpty($returnTypeWarnings, 
                "Method '$methodName' with return type '$returnType' should not generate warnings");
        });
    }

    public function testCountMethodsReturnIntType(): void
    {
        $this->forAll(
            Generator\elements(['countActive', 'countByStatus', 'countInactive', 'countPublished']),
            Generator\elements(['int', 'string', 'float', 'array', 'bool'])
        )->then(function (string $methodName, string $returnType) {
            $rule = new ReturnTypeRule();
            
            $mockClass = $this->createMockRepositoryClassWithReturnType(
                'TestRepository',
                $methodName,
                $returnType
            );
            
            $result = $rule->validate($mockClass);
            
            $returnTypeErrors = $this->getReturnTypeErrors($result, $methodName);
            
            if ($returnType === 'int') {
                $this->assertEmpty($returnTypeErrors, 
                    "Method '$methodName' with return type 'int' should not generate errors");
            } else {
                $this->assertNotEmpty($returnTypeErrors, 
                    "Method '$methodName' with return type '$returnType' should generate errors");
            }
        });
    }

    public function testBooleanMethodsReturnBoolType(): void
    {
        $this->forAll(
            Generator\elements([
                'exists', 'existsById', 'existsByEmail',
                'has', 'hasPermission', 'hasRole',
                'is', 'isActive', 'isValid', 'isPublished'
            ]),
            Generator\elements(['bool', 'int', 'string', 'array', '?bool'])
        )->then(function (string $methodName, string $returnType) {
            $rule = new ReturnTypeRule();
            
            $mockClass = $this->createMockRepositoryClassWithReturnType(
                'TestRepository',
                $methodName,
                $returnType
            );
            
            $result = $rule->validate($mockClass);
            
            $returnTypeErrors = $this->getReturnTypeErrors($result, $methodName);
            
            if ($returnType === 'bool') {
                $this->assertEmpty($returnTypeErrors, 
                    "Method '$methodName' with return type 'bool' should not generate errors");
            } else {
                $this->assertNotEmpty($returnTypeErrors, 
                    "Method '$methodName' with return type '$returnType' should generate errors");
            }
        });
    }

    public function testVoidMethodsReturnVoidType(): void
    {
        $this->forAll(
            Generator\elements(['save', 'saveEntity', 'remove', 'removeEntity', 'delete', 'deleteById']),
            Generator\elements(['void', 'int', 'string', 'bool', 'array', 'User'])
        )->then(function (string $methodName, string $returnType) {
            $rule = new ReturnTypeRule();
            
            $mockClass = $this->createMockRepositoryClassWithReturnType(
                'TestRepository',
                $methodName,
                $returnType
            );
            
            $result = $rule->validate($mockClass);
            
            $returnTypeWarnings = $this->getReturnTypeWarnings($result, $methodName);
            
            if ($returnType === 'void') {
                $this->assertEmpty($returnTypeWarnings, 
                    "Method '$methodName' with return type 'void' should not generate warnings");
            } else {
                $this->assertNotEmpty($returnTypeWarnings, 
                    "Method '$methodName' with return type '$returnType' should generate warnings");
            }
        });
    }

    public function testSpecialMethodReturnTypes(): void
    {
        $this->forAll(
            Generator\elements([
                ['method' => 'paginate', 'correctType' => 'PaginationResult', 'incorrectTypes' => ['array', 'Collection', 'int']],
                ['method' => 'getEntityClass', 'correctType' => 'string', 'incorrectTypes' => ['int', 'array', 'bool']]
            ])
        )->then(function (array $testCase) {
            $rule = new ReturnTypeRule();
            
            // Test correct return type
            $mockClass = $this->createMockRepositoryClassWithReturnType(
                'TestRepository',
                $testCase['method'],
                $testCase['correctType']
            );
            
            $result = $rule->validate($mockClass);
            $errors = $this->getReturnTypeErrors($result, $testCase['method']);
            
            $this->assertEmpty($errors, 
                "Method '{$testCase['method']}' with correct return type '{$testCase['correctType']}' should not generate errors");
            
            // Test incorrect return types
            foreach ($testCase['incorrectTypes'] as $incorrectType) {
                $mockClass = $this->createMockRepositoryClassWithReturnType(
                    'TestRepository',
                    $testCase['method'],
                    $incorrectType
                );
                
                $result = $rule->validate($mockClass);
                $errors = $this->getReturnTypeErrors($result, $testCase['method']);
                
                $this->assertNotEmpty($errors, 
                    "Method '{$testCase['method']}' with incorrect return type '$incorrectType' should generate errors");
            }
        });
    }

    public function testMissingReturnTypeDeclarations(): void
    {
        $this->forAll(
            Generator\elements(['findAllActive', 'findByIdCustom', 'saveEntity', 'removeEntity', 'countActive', 'existsActive']),
            Generator\bool()
        )->then(function (string $methodName, bool $hasReturnType) {
            $rule = new ReturnTypeRule();
            
            $mockClass = $this->createMockRepositoryClassWithOptionalReturnType(
                'TestRepository',
                $methodName,
                $hasReturnType,
                $hasReturnType ? 'array' : null
            );
            
            $result = $rule->validate($mockClass);
            
            $missingReturnTypeWarnings = array_filter($result->getWarnings(), function ($warning) use ($methodName) {
                return str_contains($warning, $methodName) && 
                       str_contains($warning, 'missing return type declaration');
            });
            
            if ($hasReturnType) {
                $this->assertEmpty($missingReturnTypeWarnings, 
                    "Method '$methodName' with return type should not generate missing return type warnings");
            } else {
                $this->assertNotEmpty($missingReturnTypeWarnings, 
                    "Method '$methodName' without return type should generate missing return type warnings");
            }
        });
    }

    public function testReturnTypeConsistencyAcrossMethodPatterns(): void
    {
        $this->forAll(
            Generator\choose(2, 5), // Number of similar methods
            Generator\elements(['findBy', 'countBy', 'existsBy', 'removeBy'])
        )->then(function (int $methodCount, string $methodPrefix) {
            $rule = new ReturnTypeRule();
            
            $methods = [];
            $expectedReturnType = $this->getExpectedReturnTypeForPrefix($methodPrefix);
            
            for ($i = 0; $i < $methodCount; $i++) {
                $methods[$methodPrefix . chr(65 + $i)] = $expectedReturnType; // findByA, findByB, etc.
            }
            
            $mockClass = $this->createMockRepositoryClassWithMultipleReturnTypes(
                'TestRepository',
                $methods
            );
            
            $result = $rule->validate($mockClass);
            
            // All methods with same prefix should have consistent return type expectations
            foreach ($methods as $methodName => $returnType) {
                $methodErrors = $this->getReturnTypeErrors($result, $methodName);
                $methodWarnings = $this->getReturnTypeWarnings($result, $methodName);
                
                $this->assertEmpty($methodErrors, 
                    "Method '$methodName' with expected return type '$returnType' should not generate errors");
            }
        });
    }

    private function getExpectedReturnTypeForPrefix(string $prefix): string
    {
        return match ($prefix) {
            'findBy' => 'array',
            'countBy' => 'int',
            'existsBy' => 'bool',
            'removeBy' => 'void',
            default => 'mixed'
        };
    }

    private function getReturnTypeWarnings(object $result, string $methodName): array
    {
        return array_filter($result->getWarnings(), function ($warning) use ($methodName) {
            return str_contains($warning, $methodName) && str_contains($warning, 'should return');
        });
    }

    private function getReturnTypeErrors(object $result, string $methodName): array
    {
        return array_filter($result->getErrors(), function ($error) use ($methodName) {
            return str_contains($error, $methodName) && str_contains($error, 'should return');
        });
    }

    private function createMockRepositoryClassWithReturnType(string $className, string $methodName, string $returnType): ReflectionClass
    {
        $mockClass = $this->createMock(ReflectionClass::class);
        $mockClass->method('getName')->willReturn($className);
        
        $mockMethod = $this->createMock(ReflectionMethod::class);
        $mockMethod->method('getName')->willReturn($methodName);
        $mockMethod->method('isPublic')->willReturn(true);
        $mockMethod->method('hasReturnType')->willReturn(true);
        
        $mockReturnType = $this->createMock(\ReflectionType::class);
        $mockReturnType->method('__toString')->willReturn($returnType);
        $mockMethod->method('getReturnType')->willReturn($mockReturnType);
        
        $mockClass->method('getMethods')->willReturn([$mockMethod]);
        
        return $mockClass;
    }

    private function createMockRepositoryClassWithOptionalReturnType(
        string $className, 
        string $methodName, 
        bool $hasReturnType, 
        ?string $returnType
    ): ReflectionClass {
        $mockClass = $this->createMock(ReflectionClass::class);
        $mockClass->method('getName')->willReturn($className);
        
        $mockMethod = $this->createMock(ReflectionMethod::class);
        $mockMethod->method('getName')->willReturn($methodName);
        $mockMethod->method('isPublic')->willReturn(true);
        $mockMethod->method('hasReturnType')->willReturn($hasReturnType);
        
        if ($hasReturnType && $returnType) {
            $mockReturnType = $this->createMock(\ReflectionType::class);
            $mockReturnType->method('__toString')->willReturn($returnType);
            $mockMethod->method('getReturnType')->willReturn($mockReturnType);
        } else {
            $mockMethod->method('getReturnType')->willReturn(null);
        }
        
        $mockClass->method('getMethods')->willReturn([$mockMethod]);
        
        return $mockClass;
    }

    private function createMockRepositoryClassWithMultipleReturnTypes(string $className, array $methodsWithReturnTypes): ReflectionClass
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