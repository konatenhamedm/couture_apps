<?php

namespace App\Tests\Unit;

use App\Repository\ReservationRepository;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Tests unitaires pour les filtres simplifiés du repository des réservations
 */
class ReservationSimpleFiltersTest extends TestCase
{
    /**
     * Test que la méthode findByBoutiqueWithSimpleFilters existe
     */
    public function testFindByBoutiqueWithSimpleFiltersMethodExists(): void
    {
        $reflection = new ReflectionClass(ReservationRepository::class);
        
        $this->assertTrue($reflection->hasMethod('findByBoutiqueWithSimpleFilters'));
        
        $method = $reflection->getMethod('findByBoutiqueWithSimpleFilters');
        $this->assertTrue($method->isPublic());
        
        // Vérifier les paramètres de la méthode
        $parameters = $method->getParameters();
        $this->assertCount(4, $parameters);
        
        $this->assertEquals('boutiqueId', $parameters[0]->getName());
        $this->assertEquals('dateDebut', $parameters[1]->getName());
        $this->assertEquals('dateFin', $parameters[2]->getName());
        $this->assertEquals('statusFilters', $parameters[3]->getName());
    }

    /**
     * Test que les paramètres par défaut sont corrects
     */
    public function testSimpleMethodDefaultParameters(): void
    {
        $reflection = new ReflectionClass(ReservationRepository::class);
        $method = $reflection->getMethod('findByBoutiqueWithSimpleFilters');
        $parameters = $method->getParameters();
        
        // Vérifier les valeurs par défaut
        $this->assertEquals([], $parameters[3]->getDefaultValue()); // statusFilters
    }

    /**
     * Test que la méthode retourne un array
     */
    public function testSimpleMethodReturnType(): void
    {
        $reflection = new ReflectionClass(ReservationRepository::class);
        $method = $reflection->getMethod('findByBoutiqueWithSimpleFilters');
        
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals('array', $returnType->getName());
    }

    /**
     * Test que l'ancienne méthode avancée existe toujours
     */
    public function testAdvancedMethodStillExists(): void
    {
        $reflection = new ReflectionClass(ReservationRepository::class);
        
        $this->assertTrue($reflection->hasMethod('findByBoutiqueWithAdvancedFilters'));
        
        $method = $reflection->getMethod('findByBoutiqueWithAdvancedFilters');
        $this->assertTrue($method->isPublic());
    }
}