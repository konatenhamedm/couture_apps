<?php

namespace App\Tests\Integration;

use App\Entity\Reservation;
use App\Enum\ReservationStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Test d'intégration pour vérifier le nouveau workflow de réservation
 */
class ReservationWorkflowTest extends KernelTestCase
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
     * Test que les nouvelles réservations ont bien le statut "en_attente"
     * 
     * @test
     */
    public function testNewReservationHasCorrectInitialStatus(): void
    {
        // Créer une nouvelle réservation
        $reservation = new Reservation();
        $reservation->setMontant(50000);
        $reservation->setAvance(20000);
        $reservation->setReste(30000);
        $reservation->setDateRetrait(new \DateTime('+1 week'));

        // Vérifier le statut initial
        $this->assertEquals(ReservationStatus::EN_ATTENTE->value, $reservation->getStatus());
        $this->assertEquals(ReservationStatus::EN_ATTENTE, $reservation->getStatusEnum());
        
        // Vérifier les méthodes utilitaires
        $this->assertTrue($reservation->isPending());
        $this->assertTrue($reservation->isConfirmable());
        $this->assertTrue($reservation->isCancellable());
        $this->assertFalse($reservation->isConfirmed());
        $this->assertFalse($reservation->isCancelled());
    }

    /**
     * Test des transitions de statut incluant EN_ATTENTE_STOCK
     * 
     * @test
     */
    public function testStatusTransitions(): void
    {
        $reservation = new Reservation();
        
        // État initial
        $this->assertEquals(ReservationStatus::EN_ATTENTE->value, $reservation->getStatus());
        
        // Transition vers en_attente_stock
        $reservation->setStatus(ReservationStatus::EN_ATTENTE_STOCK->value);
        $this->assertEquals(ReservationStatus::EN_ATTENTE_STOCK->value, $reservation->getStatus());
        $this->assertTrue($reservation->isConfirmable());
        $this->assertTrue($reservation->isCancellable());
        
        // Transition de en_attente_stock vers en_attente (après ravitaillement)
        $reservation->setStatus(ReservationStatus::EN_ATTENTE->value);
        $this->assertEquals(ReservationStatus::EN_ATTENTE->value, $reservation->getStatus());
        $this->assertTrue($reservation->isConfirmable());
        $this->assertTrue($reservation->isCancellable());
        
        // Transition vers confirmée
        $reservation->setStatus(ReservationStatus::CONFIRMEE->value);
        $this->assertEquals(ReservationStatus::CONFIRMEE->value, $reservation->getStatus());
        $this->assertTrue($reservation->isConfirmed());
        $this->assertFalse($reservation->isConfirmable());
        $this->assertFalse($reservation->isCancellable());
        
        // Transition vers annulée
        $reservation->setStatus(ReservationStatus::ANNULEE->value);
        $this->assertEquals(ReservationStatus::ANNULEE->value, $reservation->getStatus());
        $this->assertTrue($reservation->isCancelled());
        $this->assertFalse($reservation->isConfirmable());
        $this->assertFalse($reservation->isCancellable());
    }

    /**
     * Test spécifique des transitions valides pour EN_ATTENTE_STOCK
     * 
     * @test
     */
    public function testStockStatusTransitions(): void
    {
        $reservation = new Reservation();
        
        // Mettre en statut EN_ATTENTE_STOCK
        $reservation->setStatus(ReservationStatus::EN_ATTENTE_STOCK->value);
        
        // Vérifier que les transitions sont possibles
        $this->assertTrue(ReservationStatus::EN_ATTENTE_STOCK->canTransitionToReady());
        $this->assertTrue(ReservationStatus::EN_ATTENTE_STOCK->isConfirmable());
        $this->assertTrue(ReservationStatus::EN_ATTENTE_STOCK->isCancellable());
        $this->assertTrue(ReservationStatus::EN_ATTENTE_STOCK->hasStockIssue());
        
        // Vérifier que les autres statuts ne peuvent pas transitionner vers "ready"
        $this->assertFalse(ReservationStatus::EN_ATTENTE->canTransitionToReady());
        $this->assertFalse(ReservationStatus::CONFIRMEE->canTransitionToReady());
        $this->assertFalse(ReservationStatus::ANNULEE->canTransitionToReady());
    }

    /**
     * Test que l'énumération fonctionne correctement avec le nouveau statut EN_ATTENTE_STOCK
     * 
     * @test
     */
    public function testReservationStatusEnum(): void
    {
        // Test des valeurs
        $this->assertEquals('en_attente', ReservationStatus::EN_ATTENTE->value);
        $this->assertEquals('en_attente_stock', ReservationStatus::EN_ATTENTE_STOCK->value);
        $this->assertEquals('confirmee', ReservationStatus::CONFIRMEE->value);
        $this->assertEquals('annulee', ReservationStatus::ANNULEE->value);
        
        // Test des labels
        $this->assertEquals('En attente', ReservationStatus::EN_ATTENTE->getLabel());
        $this->assertEquals('En attente de stock', ReservationStatus::EN_ATTENTE_STOCK->getLabel());
        $this->assertEquals('Confirmée', ReservationStatus::CONFIRMEE->getLabel());
        $this->assertEquals('Annulée', ReservationStatus::ANNULEE->getLabel());
        
        // Test des méthodes de validation pour EN_ATTENTE
        $this->assertTrue(ReservationStatus::EN_ATTENTE->isConfirmable());
        $this->assertTrue(ReservationStatus::EN_ATTENTE->isCancellable());
        $this->assertFalse(ReservationStatus::EN_ATTENTE->hasStockIssue());
        $this->assertFalse(ReservationStatus::EN_ATTENTE->canTransitionToReady());
        
        // Test des méthodes de validation pour EN_ATTENTE_STOCK
        $this->assertTrue(ReservationStatus::EN_ATTENTE_STOCK->isConfirmable());
        $this->assertTrue(ReservationStatus::EN_ATTENTE_STOCK->isCancellable());
        $this->assertTrue(ReservationStatus::EN_ATTENTE_STOCK->hasStockIssue());
        $this->assertTrue(ReservationStatus::EN_ATTENTE_STOCK->canTransitionToReady());
        
        // Test des méthodes de validation pour CONFIRMEE
        $this->assertFalse(ReservationStatus::CONFIRMEE->isConfirmable());
        $this->assertFalse(ReservationStatus::CONFIRMEE->isCancellable());
        $this->assertFalse(ReservationStatus::CONFIRMEE->hasStockIssue());
        $this->assertFalse(ReservationStatus::CONFIRMEE->canTransitionToReady());
        
        // Test des méthodes de validation pour ANNULEE
        $this->assertFalse(ReservationStatus::ANNULEE->isConfirmable());
        $this->assertFalse(ReservationStatus::ANNULEE->isCancellable());
        $this->assertFalse(ReservationStatus::ANNULEE->hasStockIssue());
        $this->assertFalse(ReservationStatus::ANNULEE->canTransitionToReady());
        
        // Test getValues()
        $values = ReservationStatus::getValues();
        $this->assertCount(4, $values);
        $this->assertContains('en_attente', $values);
        $this->assertContains('en_attente_stock', $values);
        $this->assertContains('confirmee', $values);
        $this->assertContains('annulee', $values);
        
        // Test getStockAlertStatuses()
        $stockAlertStatuses = ReservationStatus::getStockAlertStatuses();
        $this->assertCount(1, $stockAlertStatuses);
        $this->assertContains('en_attente_stock', $stockAlertStatuses);
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