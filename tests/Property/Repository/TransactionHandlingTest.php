<?php

namespace App\Tests\Property\Repository;

use App\Repository\Trait\StandardRepositoryTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Eris\Generators;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class TransactionHandlingTest extends TestCase
{
    use TestTrait;

    /**
     * Feature: repository-standardization, Property 5: Transaction Handling Consistency
     * For any database operation performed through repositories using StandardRepositoryTrait,
     * transaction state should be handled consistently across all operations
     */
    public function testTransactionHandlingConsistency(): void
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
            
            // Test save method transaction handling
            if ($reflection->hasMethod('save')) {
                $saveMethod = $reflection->getMethod('save');
                $parameters = $saveMethod->getParameters();
                
                // Should have flush parameter for transaction control
                $this->assertCount(2, $parameters, 'save method should have flush parameter for transaction control');
                $this->assertEquals('flush', $parameters[1]->getName());
                $this->assertTrue($parameters[1]->isOptional(), 'flush parameter should be optional');
                $this->assertTrue($parameters[1]->getDefaultValue(), 'flush should default to true');
            }
            
            // Test remove method transaction handling
            if ($reflection->hasMethod('remove')) {
                $removeMethod = $reflection->getMethod('remove');
                $parameters = $removeMethod->getParameters();
                
                // Should have flush parameter for transaction control
                $this->assertCount(2, $parameters, 'remove method should have flush parameter for transaction control');
                $this->assertEquals('flush', $parameters[1]->getName());
                $this->assertTrue($parameters[1]->isOptional(), 'flush parameter should be optional');
                $this->assertTrue($parameters[1]->getDefaultValue(), 'flush should default to true');
            }
        });
    }

    /**
     * Feature: repository-standardization, Property 5: Transaction Handling Consistency
     * For any repository method that modifies data, it should provide transaction control
     */
    public function testDataModificationTransactionControl(): void
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
            
            // Check that data modification methods exist and have proper transaction control
            $dataModificationMethods = ['save', 'remove'];
            
            foreach ($dataModificationMethods as $methodName) {
                if ($reflection->hasMethod($methodName)) {
                    $method = $reflection->getMethod($methodName);
                    
                    // Method should be public
                    $this->assertTrue(
                        $method->isPublic(),
                        "Data modification method {$methodName} should be public in {$repositoryClass}"
                    );
                    
                    // Method should have parameters for entity and flush control
                    $parameters = $method->getParameters();
                    $this->assertGreaterThanOrEqual(
                        2,
                        count($parameters),
                        "Data modification method {$methodName} should have at least 2 parameters in {$repositoryClass}"
                    );
                }
            }
        });
    }

    /**
     * Feature: repository-standardization, Property 5: Transaction Handling Consistency
     * For any repository with legacy add/remove methods, they should delegate to standardized methods
     */
    public function testLegacyMethodTransactionDelegation(): void
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
            
            // If legacy add method exists, it should have proper signature
            if ($reflection->hasMethod('add')) {
                $addMethod = $reflection->getMethod('add');
                $parameters = $addMethod->getParameters();
                
                $this->assertGreaterThanOrEqual(
                    2,
                    count($parameters),
                    "Legacy add method should have at least 2 parameters in {$repositoryClass}"
                );
                
                // Second parameter should be flush control
                if (count($parameters) >= 2) {
                    $this->assertEquals(
                        'flush',
                        $parameters[1]->getName(),
                        "Second parameter of add method should be 'flush' in {$repositoryClass}"
                    );
                }
            }
            
            // Repository should have standardized save method
            $this->assertTrue(
                $reflection->hasMethod('save'),
                "Repository {$repositoryClass} should have standardized save method"
            );
            
            // Repository should have standardized remove method
            $this->assertTrue(
                $reflection->hasMethod('remove'),
                "Repository {$repositoryClass} should have standardized remove method"
            );
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
}