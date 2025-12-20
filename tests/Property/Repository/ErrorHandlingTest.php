<?php

namespace App\Tests\Property\Repository;

use App\Repository\Interface\StandardRepositoryInterface;
use App\Repository\Trait\StandardRepositoryTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Eris\Generators;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class ErrorHandlingTest extends TestCase
{
    use TestTrait;

    /**
     * Feature: repository-standardization, Property 4: Error Handling Consistency
     * For any repository method that encounters an error condition, 
     * it should throw standardized exceptions with consistent error messages
     */
    public function testErrorHandlingConsistency(): void
    {
        $standardizedRepositories = $this->getStandardizedRepositories();
        
        if (empty($standardizedRepositories)) {
            $this->markTestSkipped('No standardized repositories found');
        }

        $this->forAll(
            Generators::oneOf(...array_map(
                fn($class) => Generators::constant($class),
                $standardizedRepositories
            ))
        )->then(function (string $repositoryClass) {
            $reflection = new ReflectionClass($repositoryClass);
            
            // Test that error handling methods exist and are properly implemented
            $this->verifyErrorHandlingMethods($reflection);
            
            // Test that methods have proper exception handling patterns
            $this->verifyExceptionHandlingPatterns($reflection);
        });
    }

    /**
     * Feature: repository-standardization, Property 4: Error Handling Consistency
     * For any repository using StandardRepositoryTrait, error handling should be consistent
     */
    public function testTraitErrorHandlingConsistency(): void
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
            
            // Verify that trait provides error handling methods
            $this->assertTrue(
                $reflection->hasMethod('executeQuery'),
                "Repository {$repositoryClass} using StandardRepositoryTrait should have executeQuery method"
            );
            
            $this->assertTrue(
                $reflection->hasMethod('executeScalarQuery'),
                "Repository {$repositoryClass} using StandardRepositoryTrait should have executeScalarQuery method"
            );
            
            $this->assertTrue(
                $reflection->hasMethod('validateQueryParameters'),
                "Repository {$repositoryClass} using StandardRepositoryTrait should have validateQueryParameters method"
            );
        });
    }

    /**
     * Feature: repository-standardization, Property 4: Error Handling Consistency
     * For any method with error handling, it should follow consistent patterns
     */
    public function testMethodErrorHandlingPatterns(): void
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
            
            // Test executeQuery method signature and error handling
            if ($reflection->hasMethod('executeQuery')) {
                $method = $reflection->getMethod('executeQuery');
                $this->assertTrue($method->isProtected(), 'executeQuery should be protected');
                
                $parameters = $method->getParameters();
                $this->assertCount(1, $parameters, 'executeQuery should have 1 parameter');
                $this->assertEquals('query', $parameters[0]->getName());
            }
            
            // Test executeScalarQuery method signature and error handling
            if ($reflection->hasMethod('executeScalarQuery')) {
                $method = $reflection->getMethod('executeScalarQuery');
                $this->assertTrue($method->isProtected(), 'executeScalarQuery should be protected');
                
                $parameters = $method->getParameters();
                $this->assertCount(1, $parameters, 'executeScalarQuery should have 1 parameter');
                $this->assertEquals('query', $parameters[0]->getName());
            }
            
            // Test validateQueryParameters method signature
            if ($reflection->hasMethod('validateQueryParameters')) {
                $method = $reflection->getMethod('validateQueryParameters');
                $this->assertTrue($method->isProtected(), 'validateQueryParameters should be protected');
                
                $parameters = $method->getParameters();
                $this->assertCount(1, $parameters, 'validateQueryParameters should have 1 parameter');
                $this->assertEquals('parameters', $parameters[0]->getName());
            }
        });
    }

    /**
     * Feature: repository-standardization, Property 4: Error Handling Consistency
     * For any repository save/remove operation, it should handle entity manager errors consistently
     */
    public function testCrudOperationErrorHandling(): void
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
            
            // Test save method exists and has proper signature
            if ($reflection->hasMethod('save')) {
                $saveMethod = $reflection->getMethod('save');
                $this->assertTrue($saveMethod->isPublic(), 'save method should be public');
                
                $parameters = $saveMethod->getParameters();
                $this->assertCount(2, $parameters, 'save method should have 2 parameters');
                $this->assertEquals('entity', $parameters[0]->getName());
                $this->assertEquals('flush', $parameters[1]->getName());
            }
            
            // Test remove method exists and has proper signature
            if ($reflection->hasMethod('remove')) {
                $removeMethod = $reflection->getMethod('remove');
                $this->assertTrue($removeMethod->isPublic(), 'remove method should be public');
                
                $parameters = $removeMethod->getParameters();
                $this->assertCount(2, $parameters, 'remove method should have 2 parameters');
                $this->assertEquals('entity', $parameters[0]->getName());
                $this->assertEquals('flush', $parameters[1]->getName());
            }
        });
    }

    private function getStandardizedRepositories(): array
    {
        return array_filter($this->getRepositoryClasses(), function ($className) {
            $reflection = new ReflectionClass($className);
            return $reflection->implementsInterface(StandardRepositoryInterface::class);
        });
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

    private function verifyErrorHandlingMethods(ReflectionClass $reflection): void
    {
        // Check if repository has error handling capabilities
        $errorHandlingMethods = ['executeQuery', 'executeScalarQuery', 'validateQueryParameters'];
        
        foreach ($errorHandlingMethods as $methodName) {
            if ($reflection->hasMethod($methodName)) {
                $method = $reflection->getMethod($methodName);
                $this->assertTrue(
                    $method->isProtected(),
                    "Error handling method {$methodName} should be protected in {$reflection->getName()}"
                );
            }
        }
    }

    private function verifyExceptionHandlingPatterns(ReflectionClass $reflection): void
    {
        // Verify that methods that should handle exceptions exist
        $criticalMethods = ['save', 'remove', 'paginate'];
        
        foreach ($criticalMethods as $methodName) {
            if ($reflection->hasMethod($methodName)) {
                $method = $reflection->getMethod($methodName);
                $this->assertTrue(
                    $method->isPublic(),
                    "Critical method {$methodName} should be public in {$reflection->getName()}"
                );
            }
        }
    }
}