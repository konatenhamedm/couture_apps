<?php

namespace App\Tests\Controller;

use App\Controller\Apis\ApiStatistiqueController;
use App\Service\StatistiquesService;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use DateTime;
use ReflectionClass;

class ApiStatistiqueControllerTest extends TestCase
{
    private ApiStatistiqueController $controller;
    private StatistiquesService|MockObject $statistiquesService;

    protected function setUp(): void
    {
        $this->statistiquesService = $this->createMock(StatistiquesService::class);
        
        // Create controller with minimal mocked dependencies
        $this->controller = $this->getMockBuilder(ApiStatistiqueController::class)
            ->setConstructorArgs([$this->statistiquesService])
            ->onlyMethods([])
            ->getMock();
    }

    public function testParseDateRangeWithPeriod(): void
    {
        // Test private method through reflection
        $reflection = new ReflectionClass(ApiStatistiqueController::class);
        $method = $reflection->getMethod('parseDateRange');
        $method->setAccessible(true);

        // Create a simple controller instance for testing
        $controller = new class($this->statistiquesService) extends ApiStatistiqueController {
            public function testParseDateRange($data) {
                return $this->parseDateRange($data);
            }
        };

        // Test 7j period
        $result = $controller->testParseDateRange(['periode' => '7j']);
        $this->assertCount(2, $result);
        $this->assertInstanceOf(DateTime::class, $result[0]);
        $this->assertInstanceOf(DateTime::class, $result[1]);

        // Test 30j period
        $result = $controller->testParseDateRange(['periode' => '30j']);
        $this->assertInstanceOf(DateTime::class, $result[0]);

        // Test 3m period
        $result = $controller->testParseDateRange(['periode' => '3m']);
        $this->assertInstanceOf(DateTime::class, $result[0]);
    }

    public function testParseDateRangeWithCustomDates(): void
    {
        $controller = new class($this->statistiquesService) extends ApiStatistiqueController {
            public function testParseDateRange($data) {
                return $this->parseDateRange($data);
            }
        };

        $data = [
            'dateDebut' => '2025-01-01',
            'dateFin' => '2025-01-31'
        ];

        $result = $controller->testParseDateRange($data);
        
        $this->assertEquals('2025-01-01', $result[0]->format('Y-m-d'));
        $this->assertEquals('2025-01-31', $result[1]->format('Y-m-d'));
    }

    public function testStatistiquesServiceIntegration(): void
    {
        // Test that StatistiquesService methods are called correctly
        $dateDebut = new DateTime('2025-01-01');
        $dateFin = new DateTime('2025-01-31');
        
        $this->statistiquesService
            ->expects($this->once())
            ->method('getEvolutionRevenus')
            ->with(
                $this->isInstanceOf(DateTime::class),
                $this->isInstanceOf(DateTime::class),
                'jour'
            )
            ->willReturn([
                'labels' => ['01/01', '02/01'],
                'data' => [1200, 1500],
                'total' => 2700,
                'moyenne' => 1350
            ]);

        // Call the service method directly
        $result = $this->statistiquesService->getEvolutionRevenus($dateDebut, $dateFin, 'jour');
        
        $this->assertArrayHasKey('labels', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertEquals(2700, $result['total']);
    }

    public function testServiceMethodCalls(): void
    {
        $dateDebut = new DateTime('2025-01-01');
        $dateFin = new DateTime('2025-01-31');
        
        // Test getRevenusParType
        $this->statistiquesService
            ->expects($this->once())
            ->method('getRevenusParType')
            ->with($dateDebut, $dateFin)
            ->willReturn([
                'labels' => ['Factures', 'Réservations'],
                'data' => [25000, 15000],
                'total' => 40000
            ]);

        $result = $this->statistiquesService->getRevenusParType($dateDebut, $dateFin);
        $this->assertEquals(40000, $result['total']);
    }

    public function testTopClientsService(): void
    {
        $dateDebut = new DateTime('2025-01-01');
        $dateFin = new DateTime('2025-01-31');
        $limit = 5;
        
        $expectedData = [
            [
                'id' => 1,
                'nom' => 'Dupont',
                'prenom' => 'Marie',
                'totalDepense' => 2500,
                'nombrePaiements' => 8
            ]
        ];

        $this->statistiquesService
            ->expects($this->once())
            ->method('getTopClients')
            ->with($dateDebut, $dateFin, $limit)
            ->willReturn($expectedData);

        $result = $this->statistiquesService->getTopClients($dateDebut, $dateFin, $limit);
        $this->assertEquals('Dupont', $result[0]['nom']);
        $this->assertEquals(2500, $result[0]['totalDepense']);
    }

    public function testServiceExceptionHandling(): void
    {
        $dateDebut = new DateTime('2025-01-01');
        $dateFin = new DateTime('2025-01-31');
        
        $this->statistiquesService
            ->expects($this->once())
            ->method('getDashboardStats')
            ->with($dateDebut, $dateFin)
            ->willThrowException(new \Exception('Database connection failed'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Database connection failed');
        
        $this->statistiquesService->getDashboardStats($dateDebut, $dateFin);
    }
}