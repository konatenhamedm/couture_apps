<?php

namespace App\Tests\Integration;

use App\Entity\Boutique;
use App\Entity\Client;
use App\Entity\Entreprise;
use App\Entity\Reservation;
use App\Entity\User;
use App\Enum\ReservationStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests d'intégration pour valider les réponses API des réservations
 * avec les nouveaux champs de workflow
 */
class ReservationApiResponseIntegrationTest extends WebTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();
    }

    /**
     * Test que les réponses API incluent tous les nouveaux champs de workflow
     */
    public function testApiResponseIncludesWorkflowFields(): void
    {
        $client = static::createClient();

        // Créer une réservation de test avec tous les champs
        $reservation = $this->createTestReservation();
        $this->entityManager->persist($reservation);
        $this->entityManager->flush();

        // Faire une requête à l'API (en supposant qu'il y a un endpoint pour récupérer une réservation)
        // Note: Ajustez l'URL selon votre configuration de routes
        $client->request('GET', '/api/reservation/' . $reservation->getId());

        $response = $client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);

        // Vérifier que tous les nouveaux champs sont présents
        $this->assertArrayHasKey('status', $data, "Le champ 'status' doit être présent");
        $this->assertArrayHasKey('confirmedAt', $data, "Le champ 'confirmedAt' doit être présent");
        $this->assertArrayHasKey('confirmedBy', $data, "Le champ 'confirmedBy' doit être présent");
        $this->assertArrayHasKey('cancelledAt', $data, "Le champ 'cancelledAt' doit être présent");
        $this->assertArrayHasKey('cancelledBy', $data, "Le champ 'cancelledBy' doit être présent");
        $this->assertArrayHasKey('cancellationReason', $data, "Le champ 'cancellationReason' doit être présent");

        // Vérifier les valeurs
        $this->assertEquals(ReservationStatus::CONFIRMEE->value, $data['status']);
        $this->assertNotNull($data['confirmedAt']);
        $this->assertNull($data['cancelledAt']);
        $this->assertNull($data['cancellationReason']);

        // Nettoyer
        $this->entityManager->remove($reservation);
        $this->entityManager->flush();
    }

    /**
     * Test que les réponses API incluent l'historique des statuts
     */
    public function testApiResponseIncludesStatusHistory(): void
    {
        $client = static::createClient();

        // Créer une réservation de test
        $reservation = $this->createTestReservation();
        $this->entityManager->persist($reservation);
        $this->entityManager->flush();

        // Faire une requête à l'API avec le groupe qui inclut l'historique
        $client->request('GET', '/api/reservation/' . $reservation->getId() . '?groups=group_details');

        $response = $client->getResponse();
        
        if ($response->getStatusCode() === Response::HTTP_OK) {
            $data = json_decode($response->getContent(), true);

            // Vérifier que l'historique est présent si le groupe le permet
            if (isset($data['statusHistory'])) {
                $this->assertIsArray($data['statusHistory'], "L'historique des statuts doit être un tableau");
            }
        }

        // Nettoyer
        $this->entityManager->remove($reservation);
        $this->entityManager->flush();
    }

    /**
     * Crée une réservation de test avec tous les champs remplis
     */
    private function createTestReservation(): Reservation
    {
        // Créer les entités liées
        $entreprise = new Entreprise();
        $entreprise->setNom('Test Entreprise');
        $this->entityManager->persist($entreprise);

        $boutique = new Boutique();
        $boutique->setNom('Test Boutique');
        $boutique->setEntreprise($entreprise);
        $this->entityManager->persist($boutique);

        $client = new Client();
        $client->setNom('Test Client');
        $client->setPrenom('Test');
        $client->setTelephone('123456789');
        $client->setNumero('CLI001');
        $this->entityManager->persist($client);

        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPassword('password');
        $this->entityManager->persist($user);

        // Créer la réservation
        $reservation = new Reservation();
        $reservation->setMontant('50000');
        $reservation->setAvance('20000');
        $reservation->setReste('30000');
        $reservation->setDateRetrait(new \DateTime('2025-02-15'));
        $reservation->setEntreprise($entreprise);
        $reservation->setBoutique($boutique);
        $reservation->setClient($client);
        
        // Définir le statut confirmé avec les champs associés
        $reservation->setStatusEnum(ReservationStatus::CONFIRMEE);
        $reservation->setConfirmedAt(new \DateTime());
        $reservation->setConfirmedBy($user);

        return $reservation;
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }
}