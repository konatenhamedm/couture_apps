<?php

namespace App\Tests\Property\Controller;

use App\Entity\Boutique;
use App\Entity\Client;
use App\Entity\Entreprise;
use App\Entity\Reservation;
use App\Entity\User;
use App\Enum\ReservationStatus;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Tests de propriété pour les réponses API des réservations
 * 
 * Ces tests valident que les réponses API incluent correctement tous les nouveaux champs :
 * - Property 12: API Response Consistency
 */
class ReservationApiResponseTest extends KernelTestCase
{
    private SerializerInterface $serializer;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->serializer = static::getContainer()->get(SerializerInterface::class);
    }

    /**
     * Property 12: API Response Consistency
     * 
     * Vérifie que les réponses API incluent tous les champs de statut
     * et maintiennent la cohérence des données.
     */
    public function testProperty12ApiResponseConsistency(): void
    {
        // Arrange: Créer une réservation avec tous les nouveaux champs
        $reservation = $this->createReservation();

        // Act: Sérialiser la réservation comme le ferait l'API
        $serialized = $this->serializer->normalize($reservation, null, ['groups' => ['group1']]);

        // Assert: Vérifier que tous les champs essentiels sont présents
        $this->assertArrayHasKey('status', $serialized, "Le statut doit être présent dans la réponse");
        $this->assertArrayHasKey('montant', $serialized, "Le montant doit être présent dans la réponse");
        $this->assertArrayHasKey('avance', $serialized, "L'avance doit être présente dans la réponse");
        $this->assertArrayHasKey('reste', $serialized, "Le reste doit être présent dans la réponse");
        $this->assertArrayHasKey('dateRetrait', $serialized, "La date de retrait doit être présente dans la réponse");
    }

    /**
     * Test que les champs de confirmation sont inclus dans les réponses
     */
    public function testConfirmationFieldsInResponse(): void
    {
        // Arrange: Créer une réservation confirmée
        $reservation = $this->createReservation();
        $user = $this->createUser();
        $reservation->setStatusEnum(ReservationStatus::CONFIRMEE);
        $reservation->setConfirmedAt(new \DateTime());
        $reservation->setConfirmedBy($user);

        // Act: Sérialiser avec le groupe group1
        $serialized = $this->serializer->normalize($reservation, null, ['groups' => ['group1']]);

        // Assert: Vérifier que les champs de confirmation sont présents
        $this->assertArrayHasKey('status', $serialized);
        $this->assertEquals(ReservationStatus::CONFIRMEE->value, $serialized['status']);
        $this->assertArrayHasKey('confirmedAt', $serialized, "La date de confirmation doit être présente");
    }

    /**
     * Test que les champs d'annulation sont inclus dans les réponses
     */
    public function testCancellationFieldsInResponse(): void
    {
        // Arrange: Créer une réservation annulée
        $reservation = $this->createReservation();
        $user = $this->createUser();
        $reservation->setStatusEnum(ReservationStatus::ANNULEE);
        $reservation->setCancelledAt(new \DateTime());
        $reservation->setCancelledBy($user);
        $reservation->setCancellationReason('Client ne souhaite plus les articles');

        // Act: Sérialiser avec le groupe group1
        $serialized = $this->serializer->normalize($reservation, null, ['groups' => ['group1']]);

        // Assert: Vérifier que les champs d'annulation sont présents
        $this->assertArrayHasKey('status', $serialized);
        $this->assertEquals(ReservationStatus::ANNULEE->value, $serialized['status']);
        $this->assertArrayHasKey('cancelledAt', $serialized, "La date d'annulation doit être présente");
        $this->assertArrayHasKey('cancellationReason', $serialized, "La raison d'annulation doit être présente");
    }

    /**
     * Test que le statut est toujours présent, même pour les réservations en attente
     */
    public function testStatusAlwaysPresent(): void
    {
        $statuses = [
            ReservationStatus::EN_ATTENTE,
            ReservationStatus::CONFIRMEE,
            ReservationStatus::ANNULEE
        ];

        foreach ($statuses as $status) {
            // Arrange: Créer une réservation avec chaque statut
            $reservation = $this->createReservation();
            $reservation->setStatusEnum($status);

            // Act: Sérialiser
            $serialized = $this->serializer->normalize($reservation, null, ['groups' => ['group1']]);

            // Assert: Vérifier que le statut est présent et correct
            $this->assertArrayHasKey('status', $serialized, "Le statut doit toujours être présent");
            $this->assertEquals($status->value, $serialized['status'], "Le statut doit correspondre à la valeur attendue");
        }
    }

    /**
     * Test de la cohérence des données dans les réponses
     */
    public function testDataConsistencyInResponse(): void
    {
        // Arrange: Créer une réservation avec des données cohérentes
        $reservation = $this->createReservation();
        $reservation->setMontant('50000');
        $reservation->setAvance('20000');
        $reservation->setReste('30000');

        // Act: Sérialiser
        $serialized = $this->serializer->normalize($reservation, null, ['groups' => ['group1']]);

        // Assert: Vérifier la cohérence des montants
        $this->assertEquals('50000', $serialized['montant']);
        $this->assertEquals('20000', $serialized['avance']);
        $this->assertEquals('30000', $serialized['reste']);
        
        // Vérifier que avance + reste = montant
        $montant = (int)$serialized['montant'];
        $avance = (int)$serialized['avance'];
        $reste = (int)$serialized['reste'];
        $this->assertEquals($montant, $avance + $reste, "La somme avance + reste doit égaler le montant");
    }

    /**
     * Test que les champs optionnels sont null quand non définis
     */
    public function testOptionalFieldsNullWhenNotSet(): void
    {
        // Arrange: Créer une réservation en attente (sans confirmation ni annulation)
        $reservation = $this->createReservation();
        $reservation->setStatusEnum(ReservationStatus::EN_ATTENTE);

        // Act: Sérialiser
        $serialized = $this->serializer->normalize($reservation, null, ['groups' => ['group1']]);

        // Assert: Vérifier que les champs optionnels sont null
        $this->assertNull($serialized['confirmedAt'] ?? null, "confirmedAt doit être null pour une réservation en attente");
        $this->assertNull($serialized['cancelledAt'] ?? null, "cancelledAt doit être null pour une réservation en attente");
        $this->assertNull($serialized['cancellationReason'] ?? null, "cancellationReason doit être null pour une réservation en attente");
    }

    /**
     * Test de compatibilité avec les différents groupes de sérialisation
     */
    public function testSerializationGroupsCompatibility(): void
    {
        $groups = ['group1', 'group_reservation', 'group_details'];
        $reservation = $this->createReservation();

        foreach ($groups as $group) {
            // Act: Sérialiser avec chaque groupe
            $serialized = $this->serializer->normalize($reservation, null, ['groups' => [$group]]);

            // Assert: Vérifier que les champs essentiels sont présents
            $this->assertArrayHasKey('status', $serialized, "Le statut doit être présent dans le groupe {$group}");
        }
    }

    /**
     * Test que les dates sont correctement formatées
     */
    public function testDateFormatting(): void
    {
        // Arrange: Créer une réservation avec des dates
        $reservation = $this->createReservation();
        $dateRetrait = new \DateTime('2025-02-15 10:00:00');
        $confirmedAt = new \DateTime('2025-01-30 14:30:00');
        
        $reservation->setDateRetrait($dateRetrait);
        $reservation->setConfirmedAt($confirmedAt);

        // Act: Sérialiser
        $serialized = $this->serializer->normalize($reservation, null, ['groups' => ['group1']]);

        // Assert: Vérifier que les dates sont présentes
        $this->assertArrayHasKey('dateRetrait', $serialized, "La date de retrait doit être présente");
        $this->assertArrayHasKey('confirmedAt', $serialized, "La date de confirmation doit être présente");
    }

    /**
     * Test de la structure JSON complète
     */
    public function testCompleteJsonStructure(): void
    {
        // Arrange: Créer une réservation complète
        $reservation = $this->createReservation();
        $user = $this->createUser();
        $reservation->setStatusEnum(ReservationStatus::CONFIRMEE);
        $reservation->setConfirmedAt(new \DateTime());
        $reservation->setConfirmedBy($user);

        // Act: Sérialiser en JSON
        $json = $this->serializer->serialize($reservation, 'json', ['groups' => ['group1']]);
        $data = json_decode($json, true);

        // Assert: Vérifier la structure JSON
        $this->assertIsArray($data, "La réponse doit être un tableau");
        $this->assertArrayHasKey('status', $data);
        $this->assertArrayHasKey('montant', $data);
        $this->assertArrayHasKey('avance', $data);
        $this->assertArrayHasKey('reste', $data);
        $this->assertArrayHasKey('dateRetrait', $data);
        $this->assertArrayHasKey('confirmedAt', $data);
    }

    /**
     * Crée une réservation pour les tests
     */
    private function createReservation(): Reservation
    {
        $reservation = new Reservation();
        $reservation->setMontant('50000');
        $reservation->setAvance('20000');
        $reservation->setReste('30000');
        $reservation->setDateRetrait(new \DateTime('2025-02-15'));
        $reservation->setStatusEnum(ReservationStatus::EN_ATTENTE);
        
        // Ajouter les relations nécessaires
        $entreprise = new Entreprise();
        $boutique = new Boutique();
        $client = new Client();
        
        $reservation->setEntreprise($entreprise);
        $reservation->setBoutique($boutique);
        $reservation->setClient($client);
        
        return $reservation;
    }

    /**
     * Crée un utilisateur pour les tests
     */
    private function createUser(): User
    {
        $user = new User();
        return $user;
    }
}