<?php

namespace App\Tests\Unit;

use App\Controller\Apis\ApiReservationController;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Tests unitaires pour les filtres avancés des réservations
 */
class ReservationAdvancedFiltersUnitTest extends TestCase
{
    /**
     * Test que la méthode parseAdvancedFilters existe et est accessible
     */
    public function testParseAdvancedFiltersMethodExists(): void
    {
        $reflection = new ReflectionClass(ApiReservationController::class);
        
        $this->assertTrue($reflection->hasMethod('parseAdvancedFilters'));
        
        $method = $reflection->getMethod('parseAdvancedFilters');
        $this->assertTrue($method->isPrivate());
    }

    /**
     * Test que la méthode calculateReservationStats existe et est accessible
     */
    public function testCalculateReservationStatsMethodExists(): void
    {
        $reflection = new ReflectionClass(ApiReservationController::class);
        
        $this->assertTrue($reflection->hasMethod('calculateReservationStats'));
        
        $method = $reflection->getMethod('calculateReservationStats');
        $this->assertTrue($method->isPrivate());
    }

    /**
     * Test que la méthode indexAllByBoutiqueAdvanced existe
     */
    public function testIndexAllByBoutiqueAdvancedMethodExists(): void
    {
        $reflection = new ReflectionClass(ApiReservationController::class);
        
        $this->assertTrue($reflection->hasMethod('indexAllByBoutiqueAdvanced'));
        
        $method = $reflection->getMethod('indexAllByBoutiqueAdvanced');
        $this->assertTrue($method->isPublic());
    }

    /**
     * Test de la logique de parsing des filtres de date
     */
    public function testParseAdvancedFiltersLogic(): void
    {
        $reflection = new ReflectionClass(ApiReservationController::class);
        $method = $reflection->getMethod('parseAdvancedFilters');
        $method->setAccessible(true);

        // Mock du contrôleur (on ne peut pas l'instancier facilement à cause des dépendances)
        $controller = $this->getMockBuilder(ApiReservationController::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Test filtre par jour
        $data = [
            'filtre' => 'jour',
            'valeur' => '2025-01-30'
        ];
        
        [$dateDebut, $dateFin] = $method->invoke($controller, $data);
        
        $this->assertInstanceOf(\DateTime::class, $dateDebut);
        $this->assertInstanceOf(\DateTime::class, $dateFin);
        $this->assertEquals('2025-01-30', $dateDebut->format('Y-m-d'));
        $this->assertEquals('2025-01-30', $dateFin->format('Y-m-d'));

        // Test filtre par mois
        $data = [
            'filtre' => 'mois',
            'valeur' => '2025-01'
        ];
        
        [$dateDebut, $dateFin] = $method->invoke($controller, $data);
        
        $this->assertEquals('2025-01-01', $dateDebut->format('Y-m-d'));
        $this->assertEquals('2025-01-31', $dateFin->format('Y-m-d'));

        // Test filtre par année
        $data = [
            'filtre' => 'annee',
            'valeur' => '2025'
        ];
        
        [$dateDebut, $dateFin] = $method->invoke($controller, $data);
        
        $this->assertEquals('2025-01-01', $dateDebut->format('Y-m-d'));
        $this->assertEquals('2025-12-31', $dateFin->format('Y-m-d'));
    }

    /**
     * Test de la logique de calcul des statistiques
     */
    public function testCalculateReservationStatsLogic(): void
    {
        $reflection = new ReflectionClass(ApiReservationController::class);
        $method = $reflection->getMethod('calculateReservationStats');
        $method->setAccessible(true);

        // Mock du contrôleur
        $controller = $this->getMockBuilder(ApiReservationController::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Mock des réservations
        $reservation1 = $this->createMockReservation('50000', '20000', '30000');
        $reservation2 = $this->createMockReservation('75000', '30000', '45000');
        
        $reservations = [$reservation1, $reservation2];
        
        $stats = $method->invoke($controller, $reservations);
        
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_reservations', $stats);
        $this->assertArrayHasKey('montant_total', $stats);
        $this->assertArrayHasKey('montant_avances', $stats);
        $this->assertArrayHasKey('montant_reste', $stats);
        
        $this->assertEquals(2, $stats['total_reservations']);
        $this->assertEquals(125000, $stats['montant_total']);
        $this->assertEquals(50000, $stats['montant_avances']);
        $this->assertEquals(75000, $stats['montant_reste']);
    }

    /**
     * Crée un mock de réservation pour les tests
     */
    private function createMockReservation(string $montant, string $avance, string $reste)
    {
        $reservation = $this->createMock(\App\Entity\Reservation::class);
        $reservation->method('getMontant')->willReturn($montant);
        $reservation->method('getAvance')->willReturn($avance);
        $reservation->method('getReste')->willReturn($reste);
        
        return $reservation;
    }
}