<?php

namespace App\Tests\Property\Controller;

use App\Entity\Reservation;
use App\Entity\Client;
use App\Entity\Boutique;
use App\Entity\Entreprise;
use App\Entity\User;
use App\Entity\ModeleBoutique;
use App\Entity\Modele;
use App\Entity\PaiementReservation;
use App\Enum\ReservationStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests de propriété pour la création de réservation sans déduction de stock
 * 
 * @tag Feature: reservation-workflow-management, Property 3: Stock Preservation During Creation
 * @tag Feature: reservation-workflow-management, Property 4: Stock Availability Validation
 * @tag Feature: reservation-workflow-management, Property 5: Payment Independence from Stock
 */
class ReservationCreationPropertyTest extends WebTestCase
{
    private EntityManagerInterface $entityManager;
    private $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = $this->client->getContainer()
            ->get('doctrine')
            ->getManager();
    }

    /**
     * Property 3: Stock Preservation During Creation
     * For any reservation creation operation, the stock quantities should remain unchanged
     * 
     * @test
     * @tag Feature: reservation-workflow-management, Property 3: Stock Preservation During Creation
     */
    public function testStockPreservationDuringCreation(): void
    {
        // Créer des données de test
        $testData = $this->createTestData();
        
        // Capturer les niveaux de stock initiaux
        $initialStocks = $this->captureStockLevels($testData['modeleBoutiques']);
        
        // Créer une réservation via l'API
        $reservationData = [
            'montant' => 50000,
            'avance' => 20000,
            'reste' => 30000,
            'dateRetrait' => (new \DateTime('+1 week'))->format('Y-m-d H:i:s'),
            'client' => $testData['client']->getId(),
            'boutique' => $testData['boutique']->getId(),
            'ligne' => [
                [
                    'modele' => $testData['modeleBoutiques'][0]->getId(),
                    'quantite' => 2,
                    'avanceModele' => $testData['modeleBoutiques'][0]->getId()
                ],
                [
                    'modele' => $testData['modeleBoutiques'][1]->getId(),
                    'quantite' => 1,
                    'avanceModele' => $testData['modeleBoutiques'][1]->getId()
                ]
            ]
        ];

        // Simuler l'authentification (vous devrez adapter selon votre système d'auth)
        $this->authenticateUser($testData['user']);

        // Faire la requête de création
        $this->client->request(
            'POST',
            '/api/reservation/create',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($reservationData)
        );

        // Vérifier que la réservation a été créée avec succès
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());

        // Capturer les niveaux de stock après création
        $this->entityManager->refresh($testData['modeleBoutiques'][0]);
        $this->entityManager->refresh($testData['modeleBoutiques'][1]);
        $finalStocks = $this->captureStockLevels($testData['modeleBoutiques']);

        // Vérifier que les stocks n'ont PAS changé
        $this->assertEquals($initialStocks, $finalStocks, 
            'Les stocks ne doivent pas être modifiés lors de la création d\'une réservation');

        // Nettoyer les données de test
        $this->cleanupTestData($testData);
    }

    /**
     * Property 4: Stock Availability Validation
     * For any reservation creation request, if quantities exceed available stock, 
     * the system should reject the request
     * 
     * @test
     * @tag Feature: reservation-workflow-management, Property 4: Stock Availability Validation
     */
    public function testStockAvailabilityValidation(): void
    {
        // Créer des données de test avec stock limité
        $testData = $this->createTestData();
        
        // Définir un stock très bas
        $testData['modeleBoutiques'][0]->setQuantite(1);
        $testData['modeleBoutiques'][0]->getModele()->setQuantiteGlobale(1);
        $this->entityManager->flush();

        // Tenter de réserver plus que disponible
        $reservationData = [
            'montant' => 50000,
            'avance' => 20000,
            'reste' => 30000,
            'dateRetrait' => (new \DateTime('+1 week'))->format('Y-m-d H:i:s'),
            'client' => $testData['client']->getId(),
            'boutique' => $testData['boutique']->getId(),
            'ligne' => [
                [
                    'modele' => $testData['modeleBoutiques'][0]->getId(),
                    'quantite' => 5, // Plus que le stock disponible (1)
                    'avanceModele' => $testData['modeleBoutiques'][0]->getId()
                ]
            ]
        ];

        $this->authenticateUser($testData['user']);

        $this->client->request(
            'POST',
            '/api/reservation/create',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($reservationData)
        );

        // Vérifier que la requête est rejetée
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('ERROR', $responseData['status']);
        $this->assertStringContainsString('Stock insuffisant', $responseData['message']);

        // Nettoyer les données de test
        $this->cleanupTestData($testData);
    }

    /**
     * Property 5: Payment Independence from Stock
     * For any reservation creation with acompte payment, the payment should be recorded
     * regardless of stock levels, as long as stock validation passes
     * 
     * @test
     * @tag Feature: reservation-workflow-management, Property 5: Payment Independence from Stock
     */
    public function testPaymentIndependenceFromStock(): void
    {
        // Créer des données de test
        $testData = $this->createTestData();

        $reservationData = [
            'montant' => 50000,
            'avance' => 20000,
            'reste' => 30000,
            'dateRetrait' => (new \DateTime('+1 week'))->format('Y-m-d H:i:s'),
            'client' => $testData['client']->getId(),
            'boutique' => $testData['boutique']->getId(),
            'ligne' => [
                [
                    'modele' => $testData['modeleBoutiques'][0]->getId(),
                    'quantite' => 1,
                    'avanceModele' => $testData['modeleBoutiques'][0]->getId()
                ]
            ]
        ];

        $this->authenticateUser($testData['user']);

        // Compter les paiements avant
        $initialPaymentCount = $this->entityManager
            ->getRepository(PaiementReservation::class)
            ->count([]);

        $this->client->request(
            'POST',
            '/api/reservation/create',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($reservationData)
        );

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());

        // Vérifier qu'un paiement a été créé
        $finalPaymentCount = $this->entityManager
            ->getRepository(PaiementReservation::class)
            ->count([]);

        $this->assertEquals($initialPaymentCount + 1, $finalPaymentCount,
            'Un paiement d\'acompte doit être créé lors de la réservation');

        // Vérifier que le paiement correspond au montant d'avance
        $responseData = json_decode($response->getContent(), true);
        $reservationId = $responseData['id'];
        
        $reservation = $this->entityManager
            ->getRepository(Reservation::class)
            ->find($reservationId);
        
        $this->assertNotNull($reservation);
        $this->assertEquals(20000, $reservation->getAvance());
        $this->assertEquals(ReservationStatus::EN_ATTENTE->value, $reservation->getStatus());

        // Nettoyer les données de test
        $this->cleanupTestData($testData);
    }

    /**
     * Test que le statut initial est bien "en_attente"
     * 
     * @test
     */
    public function testInitialStatusIsEnAttente(): void
    {
        $testData = $this->createTestData();

        $reservationData = [
            'montant' => 30000,
            'avance' => 10000,
            'reste' => 20000,
            'dateRetrait' => (new \DateTime('+1 week'))->format('Y-m-d H:i:s'),
            'client' => $testData['client']->getId(),
            'boutique' => $testData['boutique']->getId(),
            'ligne' => [
                [
                    'modele' => $testData['modeleBoutiques'][0]->getId(),
                    'quantite' => 1,
                    'avanceModele' => $testData['modeleBoutiques'][0]->getId()
                ]
            ]
        ];

        $this->authenticateUser($testData['user']);

        $this->client->request(
            'POST',
            '/api/reservation/create',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($reservationData)
        );

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals(ReservationStatus::EN_ATTENTE->value, $responseData['status']);

        $this->cleanupTestData($testData);
    }

    /**
     * Crée des données de test pour les réservations
     */
    private function createTestData(): array
    {
        // Créer une entreprise
        $entreprise = new Entreprise();
        $entreprise->setLibelle('Test Entreprise');
        $entreprise->setCreatedAtValue(new \DateTime());
        $entreprise->setUpdatedAt(new \DateTime());
        $entreprise->setIsActive(true);
        $this->entityManager->persist($entreprise);

        // Créer une boutique
        $boutique = new Boutique();
        $boutique->setLibelle('Test Boutique');
        $boutique->setEntreprise($entreprise);
        $boutique->setCreatedAtValue(new \DateTime());
        $boutique->setUpdatedAt(new \DateTime());
        $boutique->setIsActive(true);
        $this->entityManager->persist($boutique);

        // Créer un client
        $client = new Client();
        $client->setNom('Test');
        $client->setPrenom('Client');
        $client->setTelephone('0123456789');
        $client->setEntreprise($entreprise);
        $client->setCreatedAtValue(new \DateTime());
        $client->setUpdatedAt(new \DateTime());
        $client->setIsActive(true);
        $this->entityManager->persist($client);

        // Créer un utilisateur
        $user = new User();
        $user->setLogin('test@example.com');
        $user->setPassword('password');
        $user->setEntreprise($entreprise);
        $user->setBoutique($boutique);
        $user->setIsActive(true);
        $this->entityManager->persist($user);

        // Créer des modèles et modeleBoutiques
        $modeleBoutiques = [];
        for ($i = 0; $i < 2; $i++) {
            $modele = new Modele();
            $modele->setNom("Test Modele $i");
            $modele->setQuantiteGlobale(10);
            $modele->setEntreprise($entreprise);
            $modele->setCreatedAtValue(new \DateTime());
            $modele->setUpdatedAt(new \DateTime());
            $modele->setIsActive(true);
            $this->entityManager->persist($modele);

            $modeleBoutique = new ModeleBoutique();
            $modeleBoutique->setModele($modele);
            $modeleBoutique->setBoutique($boutique);
            $modeleBoutique->setQuantite(5);
            $modeleBoutique->setCreatedAtValue(new \DateTime());
            $modeleBoutique->setUpdatedAt(new \DateTime());
            $modeleBoutique->setIsActive(true);
            $this->entityManager->persist($modeleBoutique);

            $modeleBoutiques[] = $modeleBoutique;
        }

        $this->entityManager->flush();

        return [
            'entreprise' => $entreprise,
            'boutique' => $boutique,
            'client' => $client,
            'user' => $user,
            'modeleBoutiques' => $modeleBoutiques
        ];
    }

    /**
     * Capture les niveaux de stock actuels
     */
    private function captureStockLevels(array $modeleBoutiques): array
    {
        $stocks = [];
        foreach ($modeleBoutiques as $mb) {
            $stocks[$mb->getId()] = [
                'boutique_quantity' => $mb->getQuantite(),
                'global_quantity' => $mb->getModele()->getQuantiteGlobale()
            ];
        }
        return $stocks;
    }

    /**
     * Simule l'authentification d'un utilisateur
     */
    private function authenticateUser(User $user): void
    {
        // Cette méthode doit être adaptée selon votre système d'authentification
        // Pour les tests, vous pourriez utiliser un token JWT ou une session simulée
        
        // Exemple basique (à adapter selon votre implémentation) :
        $this->client->loginUser($user);
    }

    /**
     * Nettoie les données de test
     */
    private function cleanupTestData(array $testData): void
    {
        try {
            // Supprimer les réservations créées
            $reservations = $this->entityManager
                ->getRepository(Reservation::class)
                ->findBy(['client' => $testData['client']]);
            
            foreach ($reservations as $reservation) {
                $this->entityManager->remove($reservation);
            }

            // Supprimer les autres entités de test
            foreach ($testData['modeleBoutiques'] as $mb) {
                $this->entityManager->remove($mb->getModele());
                $this->entityManager->remove($mb);
            }
            
            $this->entityManager->remove($testData['client']);
            $this->entityManager->remove($testData['user']);
            $this->entityManager->remove($testData['boutique']);
            $this->entityManager->remove($testData['entreprise']);
            
            $this->entityManager->flush();
        } catch (\Exception $e) {
            // Ignorer les erreurs de nettoyage
        }
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