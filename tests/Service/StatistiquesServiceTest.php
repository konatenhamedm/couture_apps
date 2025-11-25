<?php

namespace App\Tests\Service;

use App\Service\StatistiquesService;
use App\Repository\FactureRepository;
use App\Repository\ReservationRepository;
use App\Repository\ClientRepository;
use App\Repository\PaiementRepository;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use DateTime;

class StatistiquesServiceTest extends TestCase
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

    public function testGetEvolutionRevenus(): void
    {
        // Arrange
        $dateDebut = new DateTime('2025-01-01');
        $dateFin = new DateTime('2025-01-31');
        $groupBy = 'jour';

        $mockData = [
            ['periode' => '2025-01-01', 'montant' => '1200'],
            ['periode' => '2025-01-02', 'montant' => '1500'],
            ['periode' => '2025-01-03', 'montant' => '1800']
        ];

        $this->paiementRepo
            ->expects($this->once())
            ->method('getEvolutionRevenus')
            ->with($dateDebut, $dateFin, $groupBy)
            ->willReturn($mockData);

        // Act
        $result = $this->service->getEvolutionRevenus($dateDebut, $dateFin, $groupBy);

        // Assert
        $this->assertArrayHasKey('labels', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('moyenne', $result);

        $this->assertEquals(['01/01', '02/01', '03/01'], $result['labels']);
        $this->assertEquals([1200.0, 1500.0, 1800.0], $result['data']);
        $this->assertEquals(4500.0, $result['total']);
        $this->assertEquals(1500.0, $result['moyenne']);
    }

    public function testGetRevenusParType(): void
    {
        // Arrange
        $dateDebut = new DateTime('2025-01-01');
        $dateFin = new DateTime('2025-01-31');

        $mockData = [
            ['type' => 'PaiementFacture', 'montant' => '25000', 'nombre' => '10'],
            ['type' => 'PaiementReservation', 'montant' => '15000', 'nombre' => '8'],
            ['type' => 'PaiementBoutique', 'montant' => '8000', 'nombre' => '5']
        ];

        $this->paiementRepo
            ->expects($this->once())
            ->method('getRevenusParType')
            ->with($dateDebut, $dateFin)
            ->willReturn($mockData);

        // Act
        $result = $this->service->getRevenusParType($dateDebut, $dateFin);

        // Assert
        $this->assertArrayHasKey('labels', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('colors', $result);
        $this->assertArrayHasKey('total', $result);

        $this->assertEquals(['Factures', 'Réservations', 'Boutique'], $result['labels']);
        $this->assertEquals([25000.0, 15000.0, 8000.0], $result['data']);
        $this->assertEquals(48000.0, $result['total']);
    }

    public function testGetTopClients(): void
    {
        // Arrange
        $dateDebut = new DateTime('2025-01-01');
        $dateFin = new DateTime('2025-01-31');
        $limit = 5;

        $mockData = [
            [
                'id' => 1,
                'nom' => 'Dupont',
                'prenom' => 'Marie',
                'totalDepense' => 2500,
                'nombrePaiements' => 8
            ]
        ];

        $this->paiementRepo
            ->expects($this->once())
            ->method('getTopClients')
            ->with($dateDebut, $dateFin, $limit)
            ->willReturn($mockData);

        // Act
        $result = $this->service->getTopClients($dateDebut, $dateFin, $limit);

        // Assert
        $this->assertEquals($mockData, $result);
        $this->assertEquals('Dupont', $result[0]['nom']);
        $this->assertEquals(2500, $result[0]['totalDepense']);
    }
}