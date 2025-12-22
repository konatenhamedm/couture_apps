<?php

namespace App\Tests\Property\Entity;

use App\Entity\Reservation;
use App\Entity\Client;
use App\Entity\Boutique;
use App\Entity\Entreprise;
use App\Entity\User;
use App\Enum\ReservationStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Tests de propriété pour l'entité Reservation
 * 
 * @tag Feature: reservation-workflow-management, Property 1: Reservation Status Initialization
 * @tag Feature: reservation-workflow-management, Property 2: Status Field Validation
 */
class ReservationPropertyTest extends KernelTestCase
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
     * Property 1: Reservation Status Initialization
     * For any new reservation created in the system, the initial status should always be set to "en_attente"
     * 
     * @test
     * @tag Feature: reservation-workflow-management, Property 1: Reservation Status Initialization
     */
    public function testReservationStatusInitialization(): void
    {
        // Test avec différentes données d'entrée
        $testCases = [
            ['montant' => '50000', 'avance' => '20000', 'reste' => '30000'],
            ['montant' => '100000', 'avance' => '50000', 'reste' => '50000'],
            ['montant' => '25000', 'avance' => '10000', 'reste' => '15000'],
        ];

        foreach ($testCases as $data) {
            $reservation = new Reservation();
            $reservation->setMontant($data['montant']);
            $reservation->setAvance($data['avance']);
            $reservation->setReste($data['reste']);
            $reservation->setDateRetrait(new \DateTime('+1 week'));

            // Vérifier que le statut initial est toujours "en_attente"
            $this->assertEquals(ReservationStatus::EN_ATTENTE->value, $reservation->getStatus());
            $this->assertEquals(ReservationStatus::EN_ATTENTE, $reservation->getStatusEnum());
            $this->assertTrue($reservation->isPending());
            $this->assertFalse($reservation->isConfirmed());
            $this->assertFalse($reservation->isCancelled());
        }
    }

    /**
     * Property 2: Status Field Validation
     * For any reservation stored in the system, the status field should contain only valid values
     * 
     * @test
     * @tag Feature: reservation-workflow-management, Property 2: Status Field Validation
     */
    public function testStatusFieldValidation(): void
    {
        $reservation = new Reservation();
        
        // Test des valeurs valides
        $validStatuses = [
            ReservationStatus::EN_ATTENTE->value,
            ReservationStatus::CONFIRMEE->value,
            ReservationStatus::ANNULEE->value,
        ];

        foreach ($validStatuses as $status) {
            $reservation->setStatus($status);
            $this->assertEquals($status, $reservation->getStatus());
            
            // Vérifier que l'enum fonctionne correctement
            $statusEnum = $reservation->getStatusEnum();
            $this->assertInstanceOf(ReservationStatus::class, $statusEnum);
            $this->assertEquals($status, $statusEnum->value);
        }

        // Test des méthodes d'état
        $reservation->setStatus(ReservationStatus::EN_ATTENTE->value);
        $this->assertTrue($reservation->isPending());
        $this->assertTrue($reservation->isConfirmable());
        $this->assertTrue($reservation->isCancellable());

        $reservation->setStatus(ReservationStatus::CONFIRMEE->value);
        $this->assertTrue($reservation->isConfirmed());
        $this->assertFalse($reservation->isConfirmable());
        $this->assertFalse($reservation->isCancellable());

        $reservation->setStatus(ReservationStatus::ANNULEE->value);
        $this->assertTrue($reservation->isCancelled());
        $this->assertFalse($reservation->isConfirmable());
        $this->assertFalse($reservation->isCancellable());
    }

    /**
     * Test que les valeurs invalides lèvent une exception
     * 
     * @test
     */
    public function testInvalidStatusThrowsException(): void
    {
        $this->expectException(\ValueError::class);
        
        $reservation = new Reservation();
        $reservation->setStatus('invalid_status');
        
        // Cette ligne devrait lever une exception
        $reservation->getStatusEnum();
    }

    /**
     * Test des méthodes utilitaires de l'énumération
     * 
     * @test
     */
    public function testReservationStatusEnumUtilities(): void
    {
        // Test getValues()
        $values = ReservationStatus::getValues();
        $this->assertCount(3, $values);
        $this->assertContains('en_attente', $values);
        $this->assertContains('confirmee', $values);
        $this->assertContains('annulee', $values);

        // Test getLabel()
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
    }

    /**
     * Test de la cohérence des nouveaux champs
     * 
     * @test
     */
    public function testNewFieldsConsistency(): void
    {
        $reservation = new Reservation();
        $user = new User();
        $now = new \DateTime();

        // Test des champs de confirmation
        $this->assertNull($reservation->getConfirmedAt());
        $this->assertNull($reservation->getConfirmedBy());

        $reservation->setConfirmedAt($now);
        $reservation->setConfirmedBy($user);

        $this->assertEquals($now, $reservation->getConfirmedAt());
        $this->assertEquals($user, $reservation->getConfirmedBy());

        // Test des champs d'annulation
        $this->assertNull($reservation->getCancelledAt());
        $this->assertNull($reservation->getCancelledBy());
        $this->assertNull($reservation->getCancellationReason());

        $cancelledAt = new \DateTime();
        $reason = 'Client a changé d\'avis';

        $reservation->setCancelledAt($cancelledAt);
        $reservation->setCancelledBy($user);
        $reservation->setCancellationReason($reason);

        $this->assertEquals($cancelledAt, $reservation->getCancelledAt());
        $this->assertEquals($user, $reservation->getCancelledBy());
        $this->assertEquals($reason, $reservation->getCancellationReason());
    }

    /**
     * Test de l'historique des statuts
     * 
     * @test
     */
    public function testStatusHistoryCollection(): void
    {
        $reservation = new Reservation();
        
        // Vérifier que la collection est initialisée
        $this->assertCount(0, $reservation->getStatusHistory());
        
        // La gestion de l'historique sera testée plus en détail dans les tests de service
        // Ici on teste juste que la collection fonctionne
        $this->assertInstanceOf(\Doctrine\Common\Collections\Collection::class, $reservation->getStatusHistory());
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