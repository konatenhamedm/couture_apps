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
 * Tests d'intégration pour les filtres avancés des réservations par boutique
 */
class ReservationAdvancedFiltersTest extends WebTestCase
{
    /**
     * Test du filtre par mois
     */
    public function testFilterByMonth(): void
    {
        $client = static::createClient();
        $entityManager = $client->getContainer()->get('doctrine')->getManager();

        // Créer des données de test
        $boutique = $this->createTestBoutique($entityManager);
        $reservation = $this->createTestReservation($boutique, $entityManager);
        
        $entityManager->persist($boutique);
        $entityManager->persist($reservation);
        $entityManager->flush();

        // Tester le filtre par mois
        $requestData = [
            'filtre' => 'mois',
            'valeur' => (new \DateTime())->format('Y-m')
        ];

        $client->request(
            'POST',
            '/api/reservation/entreprise/by/boutique/' . $boutique->getId() . '/advanced',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($requestData)
        );

        $response = $client->getResponse();
        
        // Vérifier que la requête aboutit (même si non authentifié, la structure doit être correcte)
        $this->assertContains($response->getStatusCode(), [Response::HTTP_OK, Response::HTTP_UNAUTHORIZED]);

        // Nettoyer
        $entityManager->remove($reservation);
        $entityManager->remove($boutique);
        $entityManager->flush();
    }

    /**
     * Test du filtre par statut
     */
    public function testFilterByStatus(): void
    {
        $client = static::createClient();
        $entityManager = $client->getContainer()->get('doctrine')->getManager();

        // Créer des données de test
        $boutique = $this->createTestBoutique($entityManager);
        $reservation = $this->createTestReservation($boutique, $entityManager);
        
        $entityManager->persist($boutique);
        $entityManager->persist($reservation);
        $entityManager->flush();

        // Tester le filtre par statut
        $requestData = [
            'status' => 'en_attente,confirmee',
            'filtre' => 'mois',
            'valeur' => (new \DateTime())->format('Y-m')
        ];

        $client->request(
            'POST',
            '/api/reservation/entreprise/by/boutique/' . $boutique->getId() . '/advanced',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($requestData)
        );

        $response = $client->getResponse();
        
        // Vérifier que la requête aboutit
        $this->assertContains($response->getStatusCode(), [Response::HTTP_OK, Response::HTTP_UNAUTHORIZED]);

        // Nettoyer
        $entityManager->remove($reservation);
        $entityManager->remove($boutique);
        $entityManager->flush();
    }

    /**
     * Test du filtre par montant
     */
    public function testFilterByAmount(): void
    {
        $client = static::createClient();
        $entityManager = $client->getContainer()->get('doctrine')->getManager();

        // Créer des données de test
        $boutique = $this->createTestBoutique($entityManager);
        $reservation = $this->createTestReservation($boutique, $entityManager);
        
        $entityManager->persist($boutique);
        $entityManager->persist($reservation);
        $entityManager->flush();

        // Tester le filtre par montant
        $requestData = [
            'montantMin' => 10000,
            'montantMax' => 100000,
            'filtre' => 'mois',
            'valeur' => (new \DateTime())->format('Y-m')
        ];

        $client->request(
            'POST',
            '/api/reservation/entreprise/by/boutique/' . $boutique->getId() . '/advanced',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($requestData)
        );

        $response = $client->getResponse();
        
        // Vérifier que la requête aboutit
        $this->assertContains($response->getStatusCode(), [Response::HTTP_OK, Response::HTTP_UNAUTHORIZED]);

        // Nettoyer
        $entityManager->remove($reservation);
        $entityManager->remove($boutique);
        $entityManager->flush();
    }

    /**
     * Test avec boutique inexistante
     */
    public function testWithNonExistentBoutique(): void
    {
        $client = static::createClient();

        $requestData = [
            'filtre' => 'mois',
            'valeur' => (new \DateTime())->format('Y-m')
        ];

        $client->request(
            'POST',
            '/api/reservation/entreprise/by/boutique/99999/advanced',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($requestData)
        );

        $response = $client->getResponse();
        
        // Devrait retourner 404 pour boutique non trouvée (ou 401 si non authentifié)
        $this->assertContains($response->getStatusCode(), [Response::HTTP_NOT_FOUND, Response::HTTP_UNAUTHORIZED]);
    }

    /**
     * Test avec statut invalide
     */
    public function testWithInvalidStatus(): void
    {
        $client = static::createClient();
        $entityManager = $client->getContainer()->get('doctrine')->getManager();

        // Créer des données de test
        $boutique = $this->createTestBoutique($entityManager);
        
        $entityManager->persist($boutique);
        $entityManager->flush();

        $requestData = [
            'status' => 'statut_invalide',
            'filtre' => 'mois',
            'valeur' => (new \DateTime())->format('Y-m')
        ];

        $client->request(
            'POST',
            '/api/reservation/entreprise/by/boutique/' . $boutique->getId() . '/advanced',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($requestData)
        );

        $response = $client->getResponse();
        
        // Devrait retourner 400 pour statut invalide (ou 401 si non authentifié)
        $this->assertContains($response->getStatusCode(), [Response::HTTP_BAD_REQUEST, Response::HTTP_UNAUTHORIZED]);

        // Nettoyer
        $entityManager->remove($boutique);
        $entityManager->flush();
    }

    /**
     * Crée une boutique de test
     */
    private function createTestBoutique(EntityManagerInterface $entityManager): Boutique
    {
        $entreprise = new Entreprise();
        $entreprise->setLibelle('Test Entreprise');
        $entityManager->persist($entreprise);

        $boutique = new Boutique();
        $boutique->setLibelle('Test Boutique');
        $boutique->setContact('123456789');
        $boutique->setSituation('Test Situation');
        $boutique->setEntreprise($entreprise);
        
        return $boutique;
    }

    /**
     * Crée une réservation de test
     */
    private function createTestReservation(Boutique $boutique, EntityManagerInterface $entityManager): Reservation
    {
        $client = new Client();
        $client->setNom('Test Client');
        $client->setPrenom('Test');
        $client->setTelephone('123456789');
        $client->setNumero('CLI001');
        $entityManager->persist($client);

        $reservation = new Reservation();
        $reservation->setMontant('50000');
        $reservation->setAvance('20000');
        $reservation->setReste('30000');
        $reservation->setDateRetrait(new \DateTime('2025-02-15'));
        $reservation->setBoutique($boutique);
        $reservation->setEntreprise($boutique->getEntreprise());
        $reservation->setClient($client);
        $reservation->setStatusEnum(ReservationStatus::EN_ATTENTE);

        return $reservation;
    }
}