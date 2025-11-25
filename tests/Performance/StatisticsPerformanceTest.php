<?php

namespace App\Tests\Performance;

use App\Service\StatistiquesService;
use App\Repository\FactureRepository;
use App\Repository\ReservationRepository;
use App\Repository\ClientRepository;
use App\Repository\PaiementRepository;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use DateTime;

class StatisticsPerformanceTest extends TestCase
{
    private StatistiquesService $service;
    private FactureRepository|MockObject $factureRepo;
    private ReservationRepository|MockObject $reservationRepo;
    private ClientRepository|MockObject $clientRepo;
    private PaiementRepository|MockObject $paiementRepo;

    protected function setUp(): void
    {
        $this->factureRepo = $this->createMock(FactureRepository::class);
        $this->reservationRepo = $this->createMock(ReservationRepository::class);
        $this->clientRepo = $this->createMock(ClientRepository::class);
        $this->paiementRepo = $this->createMock(PaiementRepository::class);

        $this->service = new StatistiquesService(
            $this->factureRepo,
            $this->reservationRepo,
            $this->clientRepo,
            $this->paiementRepo
        );
    }

    public function testDashboardStatsPerformance(): void
    {
        $this->factureRepo->method('countByDateRange')->willReturn(10000);
        $this->reservationRepo->method('countByDateRange')->willReturn(5000);
        $this->clientRepo->method('countNewClients')->willReturn(1000);
        $this->paiementRepo->method('sumMontantByDateRange')->willReturn(500000.0);

        $dateDebut = new DateTime('-30 days');
        $dateFin = new DateTime('now');

        $startTime = microtime(true);
        $result = $this->service->getDashboardStats($dateDebut, $dateFin);
        $endTime = microtime(true);

        $executionTime = $endTime - $startTime;

        $this->assertLessThan(1.0, $executionTime);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('commandesTotales', $result);
    }

    public function testEvolutionRevenusPerformance(): void
    {
        $mockData = [];
        for ($i = 0; $i < 30; $i++) {
            $mockData[] = [
                'periode' => (new DateTime("-$i days"))->format('Y-m-d'),
                'montant' => rand(1000, 5000)
            ];
        }

        $this->paiementRepo->method('getEvolutionRevenus')->willReturn($mockData);

        $dateDebut = new DateTime('-30 days');
        $dateFin = new DateTime('now');

        $startTime = microtime(true);
        $result = $this->service->getEvolutionRevenus($dateDebut, $dateFin, 'jour');
        $endTime = microtime(true);

        $executionTime = $endTime - $startTime;

        $this->assertLessThan(0.1, $executionTime);
        $this->assertCount(30, $result['labels']);
        $this->assertCount(30, $result['data']);
    }

    public function testMemoryUsage(): void
    {
        $initialMemory = memory_get_usage();

        $this->factureRepo->method('countByDateRange')->willReturn(50000);
        $this->reservationRepo->method('countByDateRange')->willReturn(25000);
        $this->clientRepo->method('countNewClients')->willReturn(5000);
        $this->paiementRepo->method('sumMontantByDateRange')->willReturn(2500000.0);

        $dateDebut = new DateTime('-90 days');
        $dateFin = new DateTime('now');

        $result = $this->service->getDashboardStats($dateDebut, $dateFin);

        $finalMemory = memory_get_usage();
        $memoryUsed = $finalMemory - $initialMemory;

        $this->assertLessThan(1024 * 1024, $memoryUsed);
        $this->assertIsArray($result);
    }
}