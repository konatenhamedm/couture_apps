<?php

namespace App\Tests\Property\Entity;

use App\Entity\Reservation;
use App\Entity\ReservationStatusHistory;
use App\Entity\User;
use App\Enum\ReservationStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Tests de propriété pour l'entité ReservationStatusHistory
 * 
 * @tag Feature: reservation-workflow-management, Property 11: Audit Trail Completeness
 */
class ReservationStatusHistoryPropertyTest extends KernelTestCase
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
     * Test de la création d'un enregistrement d'historique
     * 
     * @test
     * @tag Feature: reservation-workflow-management, Property 11: Audit Trail Completeness
     */
    public function testStatusHistoryCreation(): void
    {
        $reservation = new Reservation();
        $user = new User();
        
        $history = new ReservationStatusHistory();
        $history->setReservation($reservation);
        $history->setOldStatus(ReservationStatus::EN_ATTENTE->value);
        $history->setNewStatus(ReservationStatus::CONFIRMEE->value);
        $history->setChangedBy($user);
        $history->setReason('Confirmation par le client');

        // Vérifier que tous les champs sont correctement définis
        $this->assertEquals($reservation, $history->getReservation());
        $this->assertEquals(ReservationStatus::EN_ATTENTE->value, $history->getOldStatus());
        $this->assertEquals(ReservationStatus::CONFIRMEE->value, $history->getNewStatus());
        $this->assertEquals($user, $history->getChangedBy());
        $this->assertEquals('Confirmation par le client', $history->getReason());
        
        // Vérifier que la date de changement est automatiquement définie
        $this->assertInstanceOf(\DateTime::class, $history->getChangedAt());
        $this->assertLessThanOrEqual(new \DateTime(), $history->getChangedAt());
    }

    /**
     * Test des différentes transitions de statut
     * 
     * @test
     */
    public function testStatusTransitions(): void
    {
        $reservation = new Reservation();
        $user = new User();

        // Test de toutes les transitions possibles
        $transitions = [
            [ReservationStatus::EN_ATTENTE->value, ReservationStatus::CONFIRMEE->value, 'Confirmation'],
            [ReservationStatus::EN_ATTENTE->value, ReservationStatus::ANNULEE->value, 'Annulation'],
            [ReservationStatus::CONFIRMEE->value, ReservationStatus::ANNULEE->value, 'Annulation après confirmation'],
        ];

        foreach ($transitions as [$oldStatus, $newStatus, $reason]) {
            $history = new ReservationStatusHistory();
            $history->setReservation($reservation);
            $history->setOldStatus($oldStatus);
            $history->setNewStatus($newStatus);
            $history->setChangedBy($user);
            $history->setReason($reason);

            $this->assertEquals($oldStatus, $history->getOldStatus());
            $this->assertEquals($newStatus, $history->getNewStatus());
            $this->assertEquals($reason, $history->getReason());
        }
    }

    /**
     * Test de la relation avec Reservation
     * 
     * @test
     */
    public function testReservationRelation(): void
    {
        $reservation = new Reservation();
        $user = new User();
        
        $history1 = new ReservationStatusHistory();
        $history1->setReservation($reservation);
        $history1->setOldStatus(ReservationStatus::EN_ATTENTE->value);
        $history1->setNewStatus(ReservationStatus::CONFIRMEE->value);
        $history1->setChangedBy($user);

        $history2 = new ReservationStatusHistory();
        $history2->setReservation($reservation);
        $history2->setOldStatus(ReservationStatus::CONFIRMEE->value);
        $history2->setNewStatus(ReservationStatus::ANNULEE->value);
        $history2->setChangedBy($user);

        // Ajouter à la collection de la réservation
        $reservation->addStatusHistory($history1);
        $reservation->addStatusHistory($history2);

        // Vérifier la relation bidirectionnelle
        $this->assertCount(2, $reservation->getStatusHistory());
        $this->assertTrue($reservation->getStatusHistory()->contains($history1));
        $this->assertTrue($reservation->getStatusHistory()->contains($history2));
        
        $this->assertEquals($reservation, $history1->getReservation());
        $this->assertEquals($reservation, $history2->getReservation());
    }

    /**
     * Test de la suppression d'un historique
     * 
     * @test
     */
    public function testStatusHistoryRemoval(): void
    {
        $reservation = new Reservation();
        $user = new User();
        
        $history = new ReservationStatusHistory();
        $history->setReservation($reservation);
        $history->setOldStatus(ReservationStatus::EN_ATTENTE->value);
        $history->setNewStatus(ReservationStatus::CONFIRMEE->value);
        $history->setChangedBy($user);

        // Ajouter puis supprimer
        $reservation->addStatusHistory($history);
        $this->assertCount(1, $reservation->getStatusHistory());

        $reservation->removeStatusHistory($history);
        $this->assertCount(0, $reservation->getStatusHistory());
        $this->assertNull($history->getReservation());
    }

    /**
     * Test que la date de changement est automatiquement définie
     * 
     * @test
     */
    public function testAutomaticTimestamp(): void
    {
        $beforeCreation = new \DateTime();
        
        $history = new ReservationStatusHistory();
        
        $afterCreation = new \DateTime();
        
        // La date doit être entre avant et après la création
        $this->assertGreaterThanOrEqual($beforeCreation, $history->getChangedAt());
        $this->assertLessThanOrEqual($afterCreation, $history->getChangedAt());
    }

    /**
     * Test des champs optionnels
     * 
     * @test
     */
    public function testOptionalFields(): void
    {
        $reservation = new Reservation();
        $user = new User();
        
        // Test sans raison
        $history = new ReservationStatusHistory();
        $history->setReservation($reservation);
        $history->setOldStatus(ReservationStatus::EN_ATTENTE->value);
        $history->setNewStatus(ReservationStatus::CONFIRMEE->value);
        $history->setChangedBy($user);
        
        $this->assertNull($history->getReason());
        
        // Test avec raison
        $history->setReason('Raison du changement');
        $this->assertEquals('Raison du changement', $history->getReason());
        
        // Test de suppression de la raison
        $history->setReason(null);
        $this->assertNull($history->getReason());
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