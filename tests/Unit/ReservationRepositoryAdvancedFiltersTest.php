<?php

namespace App\Tests\Unit;

use App\Repository\ReservationRepository;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Tests unitaires pour les filtres avancés du repository des réservations
 */
class ReservationRepositoryAdvancedFiltersTest extends TestCase
{
    /**
     * Test que la méthode findByBoutiqueWithAdvancedFilters existe
     */
    public function testFindByBoutiqueWithAdvancedFiltersMethodExists(): void
    {
        $reflection = new ReflectionClass(ReservationRepository::class);
        
        $this->assertTrue($reflection->hasMethod('findByBoutiqueWithAdvancedFilters'));
        
        $method = $reflection->getMethod('findByBoutiqueWithAdvancedFilters');
        $this->assertTrue($method->isPublic());
        
        // Vérifier les paramètres de la méthode
        $parameters = $method->getParameters();
        $this->assertCount(7, $parameters);
        
        $this->assertEquals('boutiqueId', $parameters[0]->getName());
        $this->assertEquals('dateDebut', $parameters[1]->getName());
        $this->assertEquals('dateFin', $parameters[2]->getName());
        $this->assertEquals('statusFilters', $parameters[3]->getName());
        $this->assertEquals('additionalFilters', $parameters[4]->getName());
        $this->assertEquals('orderBy', $parameters[5]->getName());
        $this->assertEquals('orderDirection', $parameters[6]->getName());
    }

    /**
     * Test que les paramètres par défaut sont corrects
     */
    public function testMethodDefaultParameters(): void
    {
        $reflection = new ReflectionClass(ReservationRepository::class);
        $method = $reflection->getMethod('findByBoutiqueWithAdvancedFilters');
        $parameters = $method->getParameters();
        
        // Vérifier les valeurs par défaut
        $this->assertEquals([], $parameters[3]->getDefaultValue()); // statusFilters
        $this->assertEquals([], $parameters[4]->getDefaultValue()); // additionalFilters
        $this->assertEquals('createdAt', $parameters[5]->getDefaultValue()); // orderBy
        $this->assertEquals('DESC', $parameters[6]->getDefaultValue()); // orderDirection
    }

    /**
     * Test que la méthode retourne un array
     */
    public function testMethodReturnType(): void
    {
        $reflection = new ReflectionClass(ReservationRepository::class);
        $method = $reflection->getMethod('findByBoutiqueWithAdvancedFilters');
        
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals('array', $returnType->getName());
    }
}