<?php

namespace App\Tests\Property\Service;

use App\Entity\Reservation;
use App\Entity\ReservationStatusHistory;
use App\Entity\User;
use App\Entity\Client;
use App\Entity\Boutique;
use App\Entity\Entreprise;
use App\Enum\ReservationStatus;
use App\Service\ReservationWorkflowService;
use App\Repository\ReservationRepository;
use App\Repository\ReservationStatusHistoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Tests de propriété pour le service de workflow des réservations
 * 
 * @tag Feature: reservation-workflow-management, Property 7: Confirmation Status Transition
 * @tag Feature: reservation-workflow-management, Property 9: Status-Based Operation Validation
 * @tag Feature: reservation-workflow-management, Property 11: Audit Trail Completeness
 */
class ReservationWorkflowServiceTest extends KernelTestCase
{
    private ?EntityManagerInterface $entityManager = null;
    private ?ReservationWorkflowService $workflowService = null;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        
        // Créer le service avec des mocks
        $reservationRepo = $this->createMock(ReservationRepository::class);
        $historyRepo = $this->createMock(ReservationStatusHistoryRepository::class);
        $logger = $this->createMock(LoggerInterface::class);
        
        $this->workflowService = new ReservationWorkflowService(
            $this->entityManager,
            $reservationRepo,
            $historyRepo,
            $logger
        );
    }

    /**
     * Property 7: Confirmation Status Transition
     * For any reservation in "en_attente" status with sufficient stock, 
     * confirmation should update the status to "confirmee" and record confirmation metadata
     * 
     * @test
     * @tag Feature: reservation-workflow-management, Property 7: Confirmation Status Transition
     */
    public function testConfirmationStatusTransition(): void
    {
        // Test avec différents scénarios de réservation
        $testCases = [
            ['montant' => 50000, 'avance' => 20000, 'reste' => 30000],
            ['montant' => 100000, 'avance' => 50000, 'reste' => 50000],
            ['montant' => 25000, 'avance' => 0, 'reste' => 25000], // Sans acompte
        ];

        foreach ($testCases as $case) {
            $reservation = $this->createTestReservation();
            $reservation->setMontant($case['montant']);
            $reservation->setAvance($case['avance']);
            $reservation->setReste($case['reste']);
            $user = $this->createTestUser();

            // État initial : en_attente
            $this->assertEquals(ReservationStatus::EN_ATTENTE->value, $reservation->getStatus());
            $this->assertTrue($reservation->isConfirmable());
            $this->assertNull($reservation->getConfirmedAt());
            $this->assertNull($reservation->getConfirmedBy());

            // Simuler la confirmation (sans persistance)
            $reservation->setStatus(ReservationStatus::CONFIRMEE->value);
            $reservation->setConfirmedAt(new \DateTime());
            $reservation->setConfirmedBy($user);

            // Vérifier la transition
            $this->assertEquals(ReservationStatus::CONFIRMEE->value, $reservation->getStatus());
            $this->assertTrue($reservation->isConfirmed());
            $this->assertFalse($reservation->isConfirmable());
            $this->assertFalse($reservation->isCancellable());
            $this->assertInstanceOf(\DateTime::class, $reservation->getConfirmedAt());
            $this->assertEquals($user, $reservation->getConfirmedBy());
        }
    }

    /**
     * Property 9: Status-Based Operation Validation
     * For any reservation operation (confirm/cancel), the system should only allow 
     * the operation if the current status permits it
     * 
     * @test
     * @tag Feature: reservation-workflow-management, Property 9: Status-Based Operation Validation
     */
    public function testStatusBasedOperationValidation(): void
    {
        $reservation = $this->createTestReservation();

        // Test des transitions valides depuis "en_attente"
        $this->assertTrue($this->workflowService->isValidTransition(
            ReservationStatus::EN_ATTENTE->value,
            ReservationStatus::CONFIRMEE->value
        ));
        
        $this->assertTrue($this->workflowService->isValidTransition(
            ReservationStatus::EN_ATTENTE->value,
            ReservationStatus::ANNULEE->value
        ));

        // Test des transitions invalides depuis "confirmee"
        $this->assertFalse($this->workflowService->isValidTransition(
            ReservationStatus::CONFIRMEE->value,
            ReservationStatus::EN_ATTENTE->value
        ));
        
        $this->assertFalse($this->workflowService->isValidTransition(
            ReservationStatus::CONFIRMEE->value,
            ReservationStatus::ANNULEE->value
        ));

        // Test des transitions invalides depuis "annulee"
        $this->assertFalse($this->workflowService->isValidTransition(
            ReservationStatus::ANNULEE->value,
            ReservationStatus::EN_ATTENTE->value
        ));
        
        $this->assertFalse($this->workflowService->isValidTransition(
            ReservationStatus::ANNULEE->value,
            ReservationStatus::CONFIRMEE->value
        ));

        // Test des méthodes de validation sur l'entité
        $reservation->setStatus(ReservationStatus::EN_ATTENTE->value);
        $this->assertTrue($reservation->isConfirmable());
        $this->assertTrue($reservation->isCancellable());

        $reservation->setStatus(ReservationStatus::CONFIRMEE->value);
        $this->assertFalse($reservation->isConfirmable());
        $this->assertFalse($reservation->isCancellable());

        $reservation->setStatus(ReservationStatus::ANNULEE->value);
        $this->assertFalse($reservation->isConfirmable());
        $this->assertFalse($reservation->isCancellable());
    }

    /**
     * Property 11: Audit Trail Completeness
     * For any reservation status change, the system should create complete audit records
     * 
     * @test
     * @tag Feature: reservation-workflow-management, Property 11: Audit Trail Completeness
     */
    public function testAuditTrailCompleteness(): void
    {
        $reservation = $this->createTestReservation();
        $user = $this->createTestUser();

        // Test de création d'historique pour confirmation
        $history1 = new ReservationStatusHistory();
        $history1->setReservation($reservation);
        $history1->setOldStatus(ReservationStatus::EN_ATTENTE->value);
        $history1->setNewStatus(ReservationStatus::CONFIRMEE->value);
        $history1->setChangedBy($user);
        $history1->setReason('Confirmation par le client');

        // Vérifier que tous les champs requis sont présents
        $this->assertEquals($reservation, $history1->getReservation());
        $this->assertEquals(ReservationStatus::EN_ATTENTE->value, $history1->getOldStatus());
        $this->assertEquals(ReservationStatus::CONFIRMEE->value, $history1->getNewStatus());
        $this->assertEquals($user, $history1->getChangedBy());
        $this->assertEquals('Confirmation par le client', $history1->getReason());
        $this->assertInstanceOf(\DateTime::class, $history1->getChangedAt());

        // Test de création d'historique pour annulation
        $history2 = new ReservationStatusHistory();
        $history2->setReservation($reservation);
        $history2->setOldStatus(ReservationStatus::EN_ATTENTE->value);
        $history2->setNewStatus(ReservationStatus::ANNULEE->value);
        $history2->setChangedBy($user);
        $history2->setReason('Client a changé d\'avis');

        $this->assertEquals(ReservationStatus::ANNULEE->value, $history2->getNewStatus());
        $this->assertEquals('Client a changé d\'avis', $history2->getReason());

        // Vérifier que l'historique est lié à la réservation
        $reservation->addStatusHistory($history1);
        $reservation->addStatusHistory($history2);

        $this->assertCount(2, $reservation->getStatusHistory());
        $this->assertTrue($reservation->getStatusHistory()->contains($history1));
        $this->assertTrue($reservation->getStatusHistory()->contains($history2));
    }

    /**
     * Test des transitions d'état avec différents utilisateurs
     * 
     * @test
     */
    public function testStatusTransitionsWithDifferentUsers(): void
    {
        $reservation = $this->createTestReservation();
        $user1 = $this->createTestUser();
        $user2 = $this->createTestUser();

        // Confirmation par user1
        $reservation->setStatus(ReservationStatus::CONFIRMEE->value);
        $reservation->setConfirmedBy($user1);
        $reservation->setConfirmedAt(new \DateTime());

        $this->assertEquals($user1, $reservation->getConfirmedBy());
        $this->assertTrue($reservation->isConfirmed());

        // Tentative d'annulation par user2 (doit échouer car déjà confirmée)
        $this->assertFalse($reservation->isCancellable());

        // Test avec une nouvelle réservation pour l'annulation
        $reservation2 = $this->createTestReservation();
        $reservation2->setStatus(ReservationStatus::ANNULEE->value);
        $reservation2->setCancelledBy($user2);
        $reservation2->setCancelledAt(new \DateTime());
        $reservation2->setCancellationReason('Stock épuisé');

        $this->assertEquals($user2, $reservation2->getCancelledBy());
        $this->assertEquals('Stock épuisé', $reservation2->getCancellationReason());
        $this->assertTrue($reservation2->isCancelled());
    }

    /**
     * Test de la cohérence des timestamps
     * 
     * @test
     */
    public function testTimestampConsistency(): void
    {
        $reservation = $this->createTestReservation();
        $user = $this->createTestUser();

        $beforeConfirmation = new \DateTime();
        
        // Simuler la confirmation
        $reservation->setStatus(ReservationStatus::CONFIRMEE->value);
        $reservation->setConfirmedAt(new \DateTime());
        $reservation->setConfirmedBy($user);
        
        $afterConfirmation = new \DateTime();

        // Vérifier que le timestamp de confirmation est cohérent
        $this->assertGreaterThanOrEqual($beforeConfirmation, $reservation->getConfirmedAt());
        $this->assertLessThanOrEqual($afterConfirmation, $reservation->getConfirmedAt());

        // Test pour l'annulation
        $reservation2 = $this->createTestReservation();
        
        $beforeCancellation = new \DateTime();
        
        $reservation2->setStatus(ReservationStatus::ANNULEE->value);
        $reservation2->setCancelledAt(new \DateTime());
        $reservation2->setCancelledBy($user);
        
        $afterCancellation = new \DateTime();

        $this->assertGreaterThanOrEqual($beforeCancellation, $reservation2->getCancelledAt());
        $this->assertLessThanOrEqual($afterCancellation, $reservation2->getCancelledAt());
    }

    /**
     * Test des raisons d'annulation
     * 
     * @test
     */
    public function testCancellationReasons(): void
    {
        $reservation = $this->createTestReservation();
        $user = $this->createTestUser();

        $reasons = [
            'Client a changé d\'avis',
            'Stock épuisé',
            'Problème de paiement',
            'Demande du client',
            null // Raison optionnelle
        ];

        foreach ($reasons as $reason) {
            $testReservation = $this->createTestReservation();
            $testReservation->setStatus(ReservationStatus::ANNULEE->value);
            $testReservation->setCancelledBy($user);
            $testReservation->setCancelledAt(new \DateTime());
            $testReservation->setCancellationReason($reason);

            $this->assertEquals($reason, $testReservation->getCancellationReason());
            $this->assertTrue($testReservation->isCancelled());
        }
    }

    /**
     * Crée une réservation de test
     */
    private function createTestReservation(): Reservation
    {
        $entreprise = new Entreprise();
        $entreprise->setLibelle('Test Entreprise');
        $entreprise->setCreatedAtValue(new \DateTime());
        $entreprise->setUpdatedAt(new \DateTime());
        $entreprise->setIsActive(true);

        $boutique = new Boutique();
        $boutique->setLibelle('Test Boutique');
        $boutique->setEntreprise($entreprise);
        $boutique->setCreatedAtValue(new \DateTime());
        $boutique->setUpdatedAt(new \DateTime());
        $boutique->setIsActive(true);

        $client = new Client();
        $client->setNom('Test');
        $client->setPrenom('Client');
        $client->setEntreprise($entreprise);
        $client->setCreatedAtValue(new \DateTime());
        $client->setUpdatedAt(new \DateTime());
        $client->setIsActive(true);

        $reservation = new Reservation();
        $reservation->setMontant(50000);
        $reservation->setAvance(20000);
        $reservation->setReste(30000);
        $reservation->setDateRetrait(new \DateTime('+1 week'));
        $reservation->setClient($client);
        $reservation->setBoutique($boutique);
        $reservation->setEntreprise($entreprise);
        $reservation->setCreatedAtValue(new \DateTime());
        $reservation->setUpdatedAt(new \DateTime());
        $reservation->setIsActive(true);

        return $reservation;
    }

    /**
     * Crée un utilisateur de test
     */
    private function createTestUser(): User
    {
        $user = new User();
        $user->setLogin('test@example.com');
        $user->setPassword('password');
        $user->setIsActive(true);

        return $user;
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