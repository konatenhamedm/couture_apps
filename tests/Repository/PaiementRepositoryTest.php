<?php

namespace App\Tests\Repository;

use App\Entity\Paiement;
use App\Repository\PaiementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use DateTime;

class PaiementRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private PaiementRepository $repository;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        $this->repository = $this->entityManager->getRepository(Paiement::class);
    }

    public function testSumMontantByDateRange(): void
    {
        // Arrange
        $dateDebut = new DateTime('2025-01-01');
        $dateFin = new DateTime('2025-01-31');

        // Act
        $result = $this->repository->sumMontantByDateRange($dateDebut, $dateFin);

        // Assert
        $this->assertIsFloat($result);
        $this->assertGreaterThanOrEqual(0, $result);
    }

    public function testGetEvolutionRevenus(): void
    {
        // Arrange
        $dateDebut = new DateTime('2025-01-01');
        $dateFin = new DateTime('2025-01-31');
        $groupBy = 'jour';

        // Act
        $result = $this->repository->getEvolutionRevenus($dateDebut, $dateFin, $groupBy);

        // Assert
        $this->assertIsArray($result);
        
        if (!empty($result)) {
            $this->assertArrayHasKey('periode', $result[0]);
            $this->assertArrayHasKey('montant', $result[0]);
        }
    }

    public function testGetRevenusParType(): void
    {
        // Arrange
        $dateDebut = new DateTime('2025-01-01');
        $dateFin = new DateTime('2025-01-31');

        // Act
        $result = $this->repository->getRevenusParType($dateDebut, $dateFin);

        // Assert
        $this->assertIsArray($result);
        
        if (!empty($result)) {
            $this->assertArrayHasKey('type', $result[0]);
            $this->assertArrayHasKey('montant', $result[0]);
            $this->assertArrayHasKey('nombre', $result[0]);
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }
}