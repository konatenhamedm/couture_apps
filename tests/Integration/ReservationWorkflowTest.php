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
     * Test des transitions de statut
     * 
     * @test
     */
    public function testStatusTransitions(): void
    {
        $reservation = new Reservation();
        
        // État initial
        $this->assertEquals(ReservationStatus::EN_ATTENTE->value, $reservation->getStatus());
        
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
     * Test que l'énumération fonctionne correctement
     * 
     * @test
     */
    public function testReservationStatusEnum(): void
    {
        // Test des valeurs
        $this->assertEquals('en_attente', ReservationStatus::EN_ATTENTE->value);
        $this->assertEquals('confirmee', ReservationStatus::CONFIRMEE->value);
        $this->assertEquals('annulee', ReservationStatus::ANNULEE->value);
        
        // Test des labels
        $this->assertEquals('En attente', ReservationStatus::EN_ATTENTE->getLabel());
        $this->assertEquals('Confirmée', ReservationStatus::CONFIRMEE->getLabel());
        $this->assertEquals('Annulée', ReservationStatus::ANNULEE->getLabel());
        
        // Test des méthodes de validation
        $this->assertTrue(ReservationStatus::EN_ATTENTE->isConfirmable());
        $this->assertTrue(ReservationStatus::EN_ATTENTE->isCancellable());
        
        $this->assertFalse(ReservationStatus::CONFIRMEE->isConfirmable());
        $this->assertFalse(ReservationStatus::CONFIRMEE->isCancellable());
        
        $this->assertFalse(ReservationStatus::ANNULEE->isConfirmable());
        $this->assertFalse(ReservationStatus::ANNULEE->isCancellable());
        
        // Test getValues()
        $values = ReservationStatus::getValues();
        $this->assertCount(3, $values);
        $this->assertContains('en_attente', $values);
        $this->assertContains('confirmee', $values);
        $this->assertContains('annulee', $values);
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