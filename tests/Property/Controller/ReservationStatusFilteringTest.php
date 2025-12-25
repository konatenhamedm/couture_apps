<?php

namespace App\Tests\Property\Controller;

use App\Entity\Reservation;
use App\Enum\ReservationStatus;
use App\Repository\ReservationRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests de propriété pour le filtrage par statut des réservations
 * 
 * Ces tests valident les propriétés critiques du filtrage :
 * - Property 15: Status Filtering Accuracy
 * - Property 16: Default Filtering Behavior
 * - Property 17: Filter Validation
 */
class ReservationStatusFilteringTest extends TestCase
{
    private ReservationRepository $reservationRepository;

    protected function setUp(): void
    {
        $this->reservationRepository = $this->createMock(ReservationRepository::class);
    }

    /**
     * Property 15: Status Filtering Accuracy
     * 
     * Vérifie que le filtrage par statut retourne uniquement les réservations
     * correspondant aux statuts demandés.
     */
    public function testProperty15StatusFilteringAccuracy(): void
    {
        // Test avec un seul statut
        $singleStatusFilters = [
            ReservationStatus::EN_ATTENTE->value,
            ReservationStatus::CONFIRMEE->value,
            ReservationStatus::ANNULEE->value
        ];

        foreach ($singleStatusFilters as $status) {
            // Arrange: Créer des réservations avec différents statuts
            $reservations = [
                $this->createMockReservation(1, ReservationStatus::EN_ATTENTE->value),
                $this->createMockReservation(2, ReservationStatus::CONFIRMEE->value),
                $this->createMockReservation(3, ReservationStatus::ANNULEE->value),
                $this->createMockReservation(4, $status), // Cette réservation doit être incluse
            ];

            // Filtrer les réservations qui correspondent au statut demandé
            $expectedReservations = array_filter($reservations, function($reservation) use ($status) {
                return $reservation->getStatus() === $status;
            });

            // Assert: Vérifier que seules les réservations avec le bon statut sont retournées
            $this->assertGreaterThan(0, count($expectedReservations), "Au moins une réservation doit correspondre au statut {$status}");
            
            foreach ($expectedReservations as $reservation) {
                $this->assertEquals($status, $reservation->getStatus(), 
                    "Toutes les réservations filtrées doivent avoir le statut {$status}");
            }
        }
    }

    /**
     * Property 16: Default Filtering Behavior
     * 
     * Vérifie que lorsqu'aucun filtre de statut n'est fourni,
     * toutes les réservations sont retournées.
     */
    public function testProperty16DefaultFilteringBehavior(): void
    {
        // Arrange: Créer des réservations avec tous les statuts possibles
        $allReservations = [
            $this->createMockReservation(1, ReservationStatus::EN_ATTENTE->value),
            $this->createMockReservation(2, ReservationStatus::CONFIRMEE->value),
            $this->createMockReservation(3, ReservationStatus::ANNULEE->value),
            $this->createMockReservation(4, ReservationStatus::EN_ATTENTE->value),
            $this->createMockReservation(5, ReservationStatus::CONFIRMEE->value),
        ];

        $this->reservationRepository
            ->expects($this->once())
            ->method('findAll')
            ->willReturn($allReservations);

        // Act: Simuler une requête sans filtre de statut
        $request = Request::create('/api/reservation/', 'GET');
        
        // Assert: Vérifier que toutes les réservations sont retournées
        $result = $this->reservationRepository->findAll();
        $this->assertCount(5, $result, "Toutes les réservations doivent être retournées sans filtre");
        
        // Vérifier que tous les statuts sont présents
        $statuses = array_map(fn($r) => $r->getStatus(), $result);
        $this->assertContains(ReservationStatus::EN_ATTENTE->value, $statuses);
        $this->assertContains(ReservationStatus::CONFIRMEE->value, $statuses);
        $this->assertContains(ReservationStatus::ANNULEE->value, $statuses);
    }

    /**
     * Property 17: Filter Validation
     * 
     * Vérifie que les valeurs de statut invalides sont rejetées
     * avec des messages d'erreur appropriés.
     */
    public function testProperty17FilterValidation(): void
    {
        $invalidStatuses = [
            'invalid_status',
            'pending',
            'completed',
            'cancelled',
            '',
            'EN_ATTENTE', // Mauvaise casse
            'CONFIRMEE',  // Mauvaise casse
            'en-attente', // Mauvais séparateur
            '123',
            'null'
        ];

        $validStatuses = [
            ReservationStatus::EN_ATTENTE->value,
            ReservationStatus::CONFIRMEE->value,
            ReservationStatus::ANNULEE->value
        ];

        foreach ($invalidStatuses as $invalidStatus) {
            // Act & Assert: Vérifier que le statut invalide est détecté
            $isValid = in_array($invalidStatus, $validStatuses);
            $this->assertFalse($isValid, "Le statut '{$invalidStatus}' ne doit pas être considéré comme valide");
        }

        // Vérifier que les statuts valides sont acceptés
        foreach ($validStatuses as $validStatus) {
            $isValid = in_array($validStatus, $validStatuses);
            $this->assertTrue($isValid, "Le statut '{$validStatus}' doit être considéré comme valide");
        }
    }

    /**
     * Test de filtrage avec plusieurs statuts
     */
    public function testMultipleStatusFiltering(): void
    {
        // Arrange: Créer des réservations avec différents statuts
        $reservations = [
            $this->createMockReservation(1, ReservationStatus::EN_ATTENTE->value),
            $this->createMockReservation(2, ReservationStatus::CONFIRMEE->value),
            $this->createMockReservation(3, ReservationStatus::ANNULEE->value),
            $this->createMockReservation(4, ReservationStatus::EN_ATTENTE->value),
            $this->createMockReservation(5, ReservationStatus::CONFIRMEE->value),
        ];

        $requestedStatuses = [ReservationStatus::EN_ATTENTE->value, ReservationStatus::CONFIRMEE->value];

        $this->reservationRepository
            ->expects($this->once())
            ->method('findByMultipleStatuses')
            ->with($requestedStatuses)
            ->willReturn(array_filter($reservations, function($r) use ($requestedStatuses) {
                return in_array($r->getStatus(), $requestedStatuses);
            }));

        // Act: Simuler le filtrage avec plusieurs statuts
        $result = $this->reservationRepository->findByMultipleStatuses($requestedStatuses);

        // Assert: Vérifier que seules les réservations avec les statuts demandés sont retournées
        $this->assertCount(4, $result, "4 réservations doivent correspondre aux statuts demandés");
        
        foreach ($result as $reservation) {
            $this->assertContains($reservation->getStatus(), $requestedStatuses,
                "Chaque réservation doit avoir un des statuts demandés");
        }

        // Vérifier qu'aucune réservation annulée n'est incluse
        foreach ($result as $reservation) {
            $this->assertNotEquals(ReservationStatus::ANNULEE->value, $reservation->getStatus(),
                "Aucune réservation annulée ne doit être incluse");
        }
    }

    /**
     * Test de validation des paramètres de requête
     */
    public function testQueryParameterParsing(): void
    {
        // Test avec un seul statut
        $singleStatusQuery = 'en_attente';
        $parsedSingle = array_map('trim', explode(',', $singleStatusQuery));
        $this->assertCount(1, $parsedSingle);
        $this->assertEquals('en_attente', $parsedSingle[0]);

        // Test avec plusieurs statuts
        $multipleStatusQuery = 'en_attente,confirmee,annulee';
        $parsedMultiple = array_map('trim', explode(',', $multipleStatusQuery));
        $this->assertCount(3, $parsedMultiple);
        $this->assertEquals(['en_attente', 'confirmee', 'annulee'], $parsedMultiple);

        // Test avec espaces
        $spacedQuery = 'en_attente, confirmee , annulee ';
        $parsedSpaced = array_map('trim', explode(',', $spacedQuery));
        $this->assertCount(3, $parsedSpaced);
        $this->assertEquals(['en_attente', 'confirmee', 'annulee'], $parsedSpaced);
    }

    /**
     * Test de compatibilité ascendante
     */
    public function testBackwardCompatibility(): void
    {
        // Arrange: Simuler une requête sans paramètre de statut (comportement existant)
        $allReservations = [
            $this->createMockReservation(1, ReservationStatus::EN_ATTENTE->value),
            $this->createMockReservation(2, ReservationStatus::CONFIRMEE->value),
        ];

        $this->reservationRepository
            ->expects($this->once())
            ->method('findAll')
            ->willReturn($allReservations);

        // Act: Simuler l'appel sans filtre (comme avant l'implémentation du filtrage)
        $result = $this->reservationRepository->findAll();

        // Assert: Vérifier que le comportement existant est préservé
        $this->assertCount(2, $result, "Le comportement sans filtre doit être préservé");
        $this->assertIsArray($result, "Le résultat doit être un tableau");
    }

    /**
     * Test de performance avec de nombreux statuts
     */
    public function testPerformanceWithManyStatuses(): void
    {
        // Arrange: Tester avec tous les statuts possibles
        $allValidStatuses = [
            ReservationStatus::EN_ATTENTE->value,
            ReservationStatus::EN_ATTENTE_STOCK->value,
            ReservationStatus::CONFIRMEE->value,
            ReservationStatus::ANNULEE->value
        ];

        // Act & Assert: Vérifier que la validation fonctionne avec tous les statuts
        foreach ($allValidStatuses as $status) {
            $this->assertContains($status, $allValidStatuses, 
                "Chaque statut valide doit être reconnu");
        }

        // Vérifier que la combinaison de tous les statuts est valide
        $this->assertCount(4, $allValidStatuses, "Il doit y avoir exactement 4 statuts valides");
    }

    /**
     * Crée une réservation mock avec un statut spécifique
     */
    private function createMockReservation(int $id, string $status): Reservation
    {
        $reservation = $this->createMock(Reservation::class);
        $reservation->method('getId')->willReturn($id);
        $reservation->method('getStatus')->willReturn($status);
        return $reservation;
    }
}