<?php

namespace App\Tests\Unit\Repository;

use App\Entity\User;
use App\Repository\Interface\StandardRepositoryInterface;
use App\Repository\UserRepository;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class UserRepositoryTest extends TestCase
{
    public function testImplementsStandardRepositoryInterface(): void
    {
        $reflection = new ReflectionClass(UserRepository::class);
        
        $this->assertTrue(
            $reflection->implementsInterface(StandardRepositoryInterface::class),
            'UserRepository should implement StandardRepositoryInterface'
        );
    }

    public function testHasAllRequiredMethods(): void
    {
        $reflection = new ReflectionClass(UserRepository::class);
        
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

        foreach ($requiredMethods as $method) {
            $this->assertTrue(
                $reflection->hasMethod($method),
                "UserRepository should have method: {$method}"
            );
        }
    }

    public function testUsesStandardRepositoryTrait(): void
    {
        $reflection = new ReflectionClass(UserRepository::class);
        
        $traitNames = $reflection->getTraitNames();
        $this->assertContains(
            'App\Repository\Trait\StandardRepositoryTrait',
            $traitNames,
            'UserRepository should use StandardRepositoryTrait'
        );
    }

    public function testCustomMethodsStillExist(): void
    {
        $reflection = new ReflectionClass(UserRepository::class);
        
        $customMethods = [
            'findOneByLogin',
            'getUserByCodeType',
            'findByActiveStatus',
            'updateActiveStatus',
            'countActiveByEntreprise'
        ];

        foreach ($customMethods as $method) {
            $this->assertTrue(
                $reflection->hasMethod($method),
                "UserRepository should still have custom method: {$method}"
            );
        }
    }

    public function testAddMethodExists(): void
    {
        $reflection = new ReflectionClass(UserRepository::class);
        
        $this->assertTrue(
            $reflection->hasMethod('add'),
            'UserRepository should still have add() method for backward compatibility'
        );
    }

    public function testAddMethodIsDeprecated(): void
    {
        $reflection = new ReflectionClass(UserRepository::class);
        $method = $reflection->getMethod('add');
        
        $docComment = $method->getDocComment();
        $this->assertStringContainsString(
            '@deprecated',
            $docComment,
            'add() method should be marked as deprecated'
        );
    }
}