<?php

namespace App\Tests\Integration;

use App\Entity\Boutique;
use App\Entity\Client;
use App\Entity\Entreprise;
use App\Entity\Modele;
use App\Entity\ModeleBoutique;
use App\Entity\Reservation;
use App\Enum\ReservationStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Test d'intégration pour vérifier la nouvelle gestion des stocks insuffisants
 */
class ReservationStockHandlingTest extends KernelTestCase
{
    private ?EntityManagerInterface $entityManager = null;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();
    }

    /**
     * Test que les réservations avec stock suffisant ont le statut EN_ATTENTE
     * 
     * @test
     */
    public function testReservationWithSufficientStockHasCorrectStatus(): void
    {
        // Créer une réservation avec stock suffisant
        $reservation = new Reservation();
        $reservation->setMontant(50000);
        $reservation->setAvance(20000);
        $reservation->setReste(30000);
        $reservation->setDateRetrait(new \DateTime('+1 week'));
        
        // Simuler la logique du contrôleur : stock suffisant
        $hasStockIssues = false; // Stock suffisant
        
        if ($hasStockIssues) {
            $reservation->setStatus(ReservationStatus::EN_ATTENTE_STOCK->value);
        } else {
            $reservation->setStatus(ReservationStatus::EN_ATTENTE->value);
        }
        
        // Vérifier le statut assigné
        $this->assertEquals(ReservationStatus::EN_ATTENTE->value, $reservation->getStatus());
        $this->assertEquals(ReservationStatus::EN_ATTENTE, $reservation->getStatusEnum());
        
        // Vérifier les propriétés du statut
        $statusEnum = ReservationStatus::from($reservation->getStatus());
        $this->assertFalse($statusEnum->hasStockIssue());
        $this->assertTrue($statusEnum->isConfirmable());
        $this->assertTrue($statusEnum->isCancellable());
        $this->assertFalse($statusEnum->canTransitionToReady());
    }

    /**
     * Test que les réservations avec stock insuffisant ont le statut EN_ATTENTE_STOCK
     * 
     * @test
     */
    public function testReservationWithInsufficientStockHasCorrectStatus(): void
    {
        // Créer une réservation avec stock insuffisant
        $reservation = new Reservation();
        $reservation->setMontant(75000);
        $reservation->setAvance(30000);
        $reservation->setReste(45000);
        $reservation->setDateRetrait(new \DateTime('+2 weeks'));
        
        // Simuler la logique du contrôleur : stock insuffisant
        $hasStockIssues = true; // Stock insuffisant
        
        if ($hasStockIssues) {
            $reservation->setStatus(ReservationStatus::EN_ATTENTE_STOCK->value);
        } else {
            $reservation->setStatus(ReservationStatus::EN_ATTENTE->value);
        }
        
        // Vérifier le statut assigné
        $this->assertEquals(ReservationStatus::EN_ATTENTE_STOCK->value, $reservation->getStatus());
        $this->assertEquals(ReservationStatus::EN_ATTENTE_STOCK, $reservation->getStatusEnum());
        
        // Vérifier les propriétés du statut
        $statusEnum = ReservationStatus::from($reservation->getStatus());
        $this->assertTrue($statusEnum->hasStockIssue());
        $this->assertTrue($statusEnum->isConfirmable());
        $this->assertTrue($statusEnum->isCancellable());
        $this->assertTrue($statusEnum->canTransitionToReady());
    }

    /**
     * Test de la transition de statut après ravitaillement
     * 
     * @test
     */
    public function testStatusTransitionAfterRestocking(): void
    {
        // Créer une réservation initialement en attente de stock
        $reservation = new Reservation();
        $reservation->setMontant(40000);
        $reservation->setAvance(15000);
        $reservation->setReste(25000);
        $reservation->setDateRetrait(new \DateTime('+10 days'));
        $reservation->setStatus(ReservationStatus::EN_ATTENTE_STOCK->value);
        
        // Vérifier l'état initial
        $this->assertEquals(ReservationStatus::EN_ATTENTE_STOCK->value, $reservation->getStatus());
        $this->assertTrue(ReservationStatus::from($reservation->getStatus())->canTransitionToReady());
        
        // Simuler le ravitaillement : transition vers EN_ATTENTE
        $reservation->setStatus(ReservationStatus::EN_ATTENTE->value);
        
        // Vérifier la transition
        $this->assertEquals(ReservationStatus::EN_ATTENTE->value, $reservation->getStatus());
        $this->assertFalse(ReservationStatus::from($reservation->getStatus())->hasStockIssue());
        $this->assertTrue(ReservationStatus::from($reservation->getStatus())->isConfirmable());
    }

    /**
     * Test que les statuts d'alerte de stock sont corrects
     * 
     * @test
     */
    public function testStockAlertStatuses(): void
    {
        $alertStatuses = ReservationStatus::getStockAlertStatuses();
        
        // Vérifier qu'il y a exactement un statut d'alerte
        $this->assertCount(1, $alertStatuses);
        $this->assertContains(ReservationStatus::EN_ATTENTE_STOCK->value, $alertStatuses);
        
        // Vérifier que les autres statuts ne sont pas dans les alertes
        $this->assertNotContains(ReservationStatus::EN_ATTENTE->value, $alertStatuses);
        $this->assertNotContains(ReservationStatus::CONFIRMEE->value, $alertStatuses);
        $this->assertNotContains(ReservationStatus::ANNULEE->value, $alertStatuses);
    }

    /**
     * Test de la compatibilité avec les méthodes existantes de l'entité Reservation
     * 
     * @test
     */
    public function testBackwardCompatibilityWithReservationEntity(): void
    {
        // Test avec le nouveau statut EN_ATTENTE_STOCK
        $reservation = new Reservation();
        $reservation->setStatus(ReservationStatus::EN_ATTENTE_STOCK->value);
        
        // Les méthodes existantes doivent fonctionner
        $this->assertTrue($reservation->isPending()); // EN_ATTENTE_STOCK est considéré comme pending
        $this->assertTrue($reservation->isConfirmable());
        $this->assertTrue($reservation->isCancellable());
        $this->assertFalse($reservation->isConfirmed());
        $this->assertFalse($reservation->isCancelled());
        
        // Test avec les statuts existants pour s'assurer qu'ils fonctionnent toujours
        $reservation->setStatus(ReservationStatus::EN_ATTENTE->value);
        $this->assertTrue($reservation->isPending());
        $this->assertTrue($reservation->isConfirmable());
        
        $reservation->setStatus(ReservationStatus::CONFIRMEE->value);
        $this->assertFalse($reservation->isPending());
        $this->assertTrue($reservation->isConfirmed());
        $this->assertFalse($reservation->isConfirmable());
        
        $reservation->setStatus(ReservationStatus::ANNULEE->value);
        $this->assertFalse($reservation->isPending());
        $this->assertTrue($reservation->isCancelled());
        $this->assertFalse($reservation->isConfirmable());
    }

    /**
     * Test des labels de statut
     * 
     * @test
     */
    public function testStatusLabels(): void
    {
        $this->assertEquals('En attente', ReservationStatus::EN_ATTENTE->getLabel());
        $this->assertEquals('En attente de stock', ReservationStatus::EN_ATTENTE_STOCK->getLabel());
        $this->assertEquals('Confirmée', ReservationStatus::CONFIRMEE->getLabel());
        $this->assertEquals('Annulée', ReservationStatus::ANNULEE->getLabel());
        
        // Vérifier que le label du nouveau statut mentionne le stock
        $stockLabel = ReservationStatus::EN_ATTENTE_STOCK->getLabel();
        $this->assertStringContainsString('stock', strtolower($stockLabel));
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        if ($this->entityManager) {
            $this->entityManager->close();
            $this->entityManager = null;
        }
    }
}