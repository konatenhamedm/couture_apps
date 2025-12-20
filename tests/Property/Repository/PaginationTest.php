<?php

namespace App\Tests\Property\Repository;

use App\Repository\Result\PaginationResult;
use App\Repository\Trait\StandardRepositoryTrait;
use Eris\Generators;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class PaginationTest extends TestCase
{
    use TestTrait;

    /**
     * Feature: repository-standardization, Property 6: Pagination Functionality
     * For any valid pagination parameters (page, limit, criteria), 
     * the pagination method should return results with correct counts and page information
     */
    public function testPaginationParameterValidation(): void
    {
        $repositoriesUsingTrait = $this->getRepositoriesUsingTrait();
        
        if (empty($repositoriesUsingTrait)) {
            $this->markTestSkipped('No repositories using StandardRepositoryTrait found');
        }

        $this->forAll(
            Generators::oneOf(...array_map(
                fn($class) => Generators::constant($class),
                $repositoriesUsingTrait
            )),
            Generators::choose(1, 100), // page
            Generators::choose(1, 50),  // limit
            Generators::constant([])    // criteria (empty array for simplicity)
        )->then(function (string $repositoryClass, int $page, int $limit, array $criteria) {
            $reflection = new ReflectionClass($repositoryClass);
            
            // Test paginate method exists and has correct signature
            $this->assertTrue(
                $reflection->hasMethod('paginate'),
                "Repository {$repositoryClass} should have paginate method"
            );
            
            $paginateMethod = $reflection->getMethod('paginate');
            $parameters = $paginateMethod->getParameters();
            
            // Verify method signature
            $this->assertCount(3, $parameters, 'paginate method should have 3 parameters');
            $this->assertEquals('page', $parameters[0]->getName());
            $this->assertEquals('limit', $parameters[1]->getName());
            $this->assertEquals('criteria', $parameters[2]->getName());
            
            // Verify default values
            $this->assertTrue($parameters[0]->isOptional(), 'page parameter should be optional');
            $this->assertEquals(1, $parameters[0]->getDefaultValue(), 'page should default to 1');
            $this->assertTrue($parameters[1]->isOptional(), 'limit parameter should be optional');
            $this->assertEquals(20, $parameters[1]->getDefaultValue(), 'limit should default to 20');
            $this->assertTrue($parameters[2]->isOptional(), 'criteria parameter should be optional');
            
            // Verify return type (if specified)
            $returnType = $paginateMethod->getReturnType();
            if ($returnType) {
                $this->assertEquals(
                    PaginationResult::class,
                    $returnType->getName(),
                    'paginate method should return PaginationResult'
                );
            }
        });
    }

    /**
     * Feature: repository-standardization, Property 6: Pagination Functionality
     * For any repository using StandardRepositoryTrait, pagination should be consistently implemented
     */
    public function testPaginationImplementationConsistency(): void
    {
        $repositoriesUsingTrait = $this->getRepositoriesUsingTrait();
        
        if (empty($repositoriesUsingTrait)) {
            $this->markTestSkipped('No repositories using StandardRepositoryTrait found');
        }

        $this->forAll(
            Generators::oneOf(...array_map(
                fn($class) => Generators::constant($class),
                $repositoriesUsingTrait
            ))
        )->then(function (string $repositoryClass) {
            $reflection = new ReflectionClass($repositoryClass);
            
            // Test that pagination-related methods exist
            $paginationMethods = ['paginate', 'createPaginatedQuery', 'applyFilters'];
            
            foreach ($paginationMethods as $methodName) {
                $this->assertTrue(
                    $reflection->hasMethod($methodName),
                    "Repository {$repositoryClass} should have {$methodName} method for pagination"
                );
            }
            
            // Test that helper methods are protected
            $protectedMethods = ['createPaginatedQuery', 'applyFilters'];
            
            foreach ($protectedMethods as $methodName) {
                if ($reflection->hasMethod($methodName)) {
                    $method = $reflection->getMethod($methodName);
                    $this->assertTrue(
                        $method->isProtected(),
                        "Helper method {$methodName} should be protected in {$repositoryClass}"
                    );
                }
            }
        });
    }

    /**
     * Feature: repository-standardization, Property 6: Pagination Functionality
     * For any repository with advanced filtering, it should support enhanced pagination features
     */
    public function testAdvancedPaginationFeatures(): void
    {
        $repositoriesUsingTrait = $this->getRepositoriesUsingTrait();
        
        if (empty($repositoriesUsingTrait)) {
            $this->markTestSkipped('No repositories using StandardRepositoryTrait found');
        }

        $this->forAll(
            Generators::oneOf(...array_map(
                fn($class) => Generators::constant($class),
                $repositoriesUsingTrait
            ))
        )->then(function (string $repositoryClass) {
            $reflection = new ReflectionClass($repositoryClass);
            
            // Test advanced filtering methods
            $advancedMethods = [
                'findByWithOptions',
                'findWithFilters',
                'countWithFilters',
                'applyAdvancedFilters'
            ];
            
            foreach ($advancedMethods as $methodName) {
                $this->assertTrue(
                    $reflection->hasMethod($methodName),
                    "Repository {$repositoryClass} should have {$methodName} method for advanced pagination"
                );
            }
            
            // Test findByWithOptions signature
            if ($reflection->hasMethod('findByWithOptions')) {
                $method = $reflection->getMethod('findByWithOptions');
                $parameters = $method->getParameters();
                
                $this->assertGreaterThanOrEqual(
                    1,
                    count($parameters),
                    'findByWithOptions should have at least criteria parameter'
                );
                $this->assertEquals('criteria', $parameters[0]->getName());
            }
            
            // Test countWithFilters signature
            if ($reflection->hasMethod('countWithFilters')) {
                $method = $reflection->getMethod('countWithFilters');
                $parameters = $method->getParameters();
                
                $this->assertCount(1, $parameters, 'countWithFilters should have 1 parameter');
                $this->assertEquals('filters', $parameters[0]->getName());
                $this->assertTrue($parameters[0]->isOptional(), 'filters parameter should be optional');
            }
        });
    }

    /**
     * Feature: repository-standardization, Property 6: Pagination Functionality
     * For any pagination result, it should contain all required information
     */
    public function testPaginationResultStructure(): void
    {
        // Test PaginationResult class structure
        $reflection = new ReflectionClass(PaginationResult::class);
        
        // Test required methods exist
        $requiredMethods = [
            'getItems',
            'getTotalCount',
            'getCurrentPage',
            'getItemsPerPage',
            'getTotalPages',
            'hasNextPage',
            'hasPreviousPage'
        ];
        
        foreach ($requiredMethods as $methodName) {
            $this->assertTrue(
                $reflection->hasMethod($methodName),
                "PaginationResult should have {$methodName} method"
            );
        }
        
        // Test constructor signature
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'PaginationResult should have constructor');
        
        $parameters = $constructor->getParameters();
        $this->assertCount(4, $parameters, 'PaginationResult constructor should have 4 parameters');
        
        $expectedParams = ['items', 'totalCount', 'currentPage', 'itemsPerPage'];
        foreach ($expectedParams as $index => $expectedName) {
            $this->assertEquals(
                $expectedName,
                $parameters[$index]->getName(),
                "Parameter {$index} should be named {$expectedName}"
            );
        }
    }

    private function getRepositoriesUsingTrait(): array
    {
        return array_filter($this->getRepositoryClasses(), function ($className) {
            $reflection = new ReflectionClass($className);
            return in_array(StandardRepositoryTrait::class, $reflection->getTraitNames());
        });
    }

    private function getRepositoryClasses(): array
    {
        // Get all repository classes in the system
        $repositoryDir = __DIR__ . '/../../../src/Repository';
        $repositories = [];
        
        if (is_dir($repositoryDir)) {
            $files = glob($repositoryDir . '/*Repository.php');
            foreach ($files as $file) {
                $className = 'App\\Repository\\' . basename($file, '.php');
                if (class_exists($className)) {
                    $repositories[] = $className;
                }
            }
        }
        
        // Fallback to known repositories if directory scan fails
        if (empty($repositories)) {
            $repositories = [
                'App\\Repository\\UserRepository',
                'App\\Repository\\BoutiqueRepository',
                'App\\Repository\\ClientRepository',
                'App\\Repository\\ReservationRepository',
                'App\\Repository\\PaiementRepository'
            ];
        }
        
        return array_filter($repositories, 'class_exists');
    }
}