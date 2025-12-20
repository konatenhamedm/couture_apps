<?php

namespace App\Tests\Property\Repository;

use App\Repository\Interface\StandardRepositoryInterface;
use App\Repository\Trait\StandardRepositoryTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Eris\Generators;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class RepositoryInheritanceTest extends TestCase
{
    use TestTrait;

    /**
     * Feature: repository-standardization, Property 3: Repository Inheritance Consistency
     * For any repository extending ServiceEntityRepository and using StandardRepositoryTrait,
     * it should inherit all standard CRUD methods and maintain their signatures
     */
    public function testRepositoryInheritanceConsistency(): void
    {
        $repositoryClasses = $this->getRepositoryClasses();
        
        if (empty($repositoryClasses)) {
            $this->markTestSkipped('No repository classes found');
        }

        $this->forAll(
            Generators::oneOf(...array_map(
                fn($class) => Generators::constant($class),
                $repositoryClasses
            ))
        )->then(function (string $repositoryClass) {
            $reflection = new ReflectionClass($repositoryClass);
            
            // Verify it extends ServiceEntityRepository
            $this->assertTrue(
                $reflection->isSubclassOf(ServiceEntityRepository::class),
                "Repository {$repositoryClass} must extend ServiceEntityRepository"
            );
            
            // Verify it uses StandardRepositoryTrait (if it implements StandardRepositoryInterface)
            if ($reflection->implementsInterface(StandardRepositoryInterface::class)) {
                $traitNames = $reflection->getTraitNames();
                $this->assertContains(
                    StandardRepositoryTrait::class,
                    $traitNames,
                    "Repository {$repositoryClass} implementing StandardRepositoryInterface must use StandardRepositoryTrait"
                );
                
                // Verify all required methods exist with correct signatures
                $this->verifyStandardMethods($reflection);
            }
        });
    }

    /**
     * Feature: repository-standardization, Property 3: Repository Inheritance Consistency
     * For any repository using StandardRepositoryTrait, all trait methods should be available
     */
    public function testTraitMethodsAvailability(): void
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
            
            $traitMethods = [
                'save',
                'remove',
                'paginate',
                'getEntityClass',
                'findByWithOptions',
                'findWithFilters',
                'countWithFilters'
            ];
            
            foreach ($traitMethods as $methodName) {
                $this->assertTrue(
                    $reflection->hasMethod($methodName),
                    "Repository {$repositoryClass} using StandardRepositoryTrait must have method {$methodName}"
                );
            }
        });
    }

    /**
     * Feature: repository-standardization, Property 3: Repository Inheritance Consistency
     * For any repository method signature, it should match the expected interface signature
     */
    public function testMethodSignatureConsistency(): void
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
            
            // Test save method signature
            if ($reflection->hasMethod('save')) {
                $saveMethod = $reflection->getMethod('save');
                $parameters = $saveMethod->getParameters();
                
                $this->assertCount(2, $parameters, "save() method should have 2 parameters");
                $this->assertEquals('entity', $parameters[0]->getName());
                $this->assertEquals('flush', $parameters[1]->getName());
                $this->assertTrue($parameters[1]->isOptional(), "flush parameter should be optional");
                $this->assertTrue($parameters[1]->getDefaultValue(), "flush parameter should default to true");
            }
            
            // Test paginate method signature
            if ($reflection->hasMethod('paginate')) {
                $paginateMethod = $reflection->getMethod('paginate');
                $parameters = $paginateMethod->getParameters();
                
                $this->assertCount(3, $parameters, "paginate() method should have 3 parameters");
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

    private function getRepositoriesUsingTrait(): array
    {
        return array_filter($this->getRepositoryClasses(), function ($className) {
            $reflection = new ReflectionClass($className);
            return in_array(StandardRepositoryTrait::class, $reflection->getTraitNames());
        });
    }

    private function getStandardizedRepositories(): array
    {
        return array_filter($this->getRepositoryClasses(), function ($className) {
            $reflection = new ReflectionClass($className);
            return $reflection->implementsInterface(StandardRepositoryInterface::class);
        });
    }

    private function verifyStandardMethods(ReflectionClass $reflection): void
    {
        $requiredMethods = [
            'find',
            'findAll',
            'findBy',
            'findOneBy',
            'save',
            'remove',
            'count',
            'paginate',
            'getEntityClass'
        ];

        foreach ($requiredMethods as $methodName) {
            $this->assertTrue(
                $reflection->hasMethod($methodName),
                "Repository {$reflection->getName()} must have method {$methodName}"
            );
        }
    }
}