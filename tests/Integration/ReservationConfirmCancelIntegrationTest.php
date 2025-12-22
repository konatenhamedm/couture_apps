<?php

namespace App\Tests\Integration;

use App\Entity\Reservation;
use App\Entity\User;
use App\Entity\Client;
use App\Entity\Boutique;
use App\Entity\Entreprise;
use App\Entity\Modele;
use App\Entity\ModeleBoutique;
use App\Entity\LigneReservation;
use App\Enum\ReservationStatus;
use App\Service\ReservationWorkflowService;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests d'intégration pour les fonctionnalités de confirmation et annulation des réservations
 * 
 * Ces tests valident le workflow complet avec de vraies entités et la base de données.
 */
class ReservationConfirmCancelIntegrationTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private ReservationWorkflowService $workflowService;
    private ReservationRepository $reservationRepository;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        
        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();
            
        // Créer le service manuellement avec ses dépendances
        $reservationRepository = $this->entityManager->getRepository(Reservation::class);
        $statusHistoryRepository = $this->entityManager->getRepository(\App\Entity\ReservationStatusHistory::class);
        $logger = $this->createMock(LoggerInterface::class);
        
        $this->workflowService = new ReservationWorkflowService(
            $this->entityManager,
            $reservationRepository,
            $statusHistoryRepository,
            $logger
        );
            
        $this->reservationRepository = $reservationRepository;
    }

    /**
     * Test du workflow complet : création → confirmation → vérification du stock
     */
    public function testCompleteConfirmationWorkflow(): void
    {
        // Arrange: Créer les entités nécessaires
        $entreprise = $this->createTestEntreprise();
        $boutique = $this->createTestBoutique($entreprise);
        $client = $this->createTestClient($entreprise);
        $user = $this->createTestUser($entreprise, $boutique);
        $modele = $this->createTestModele($entreprise);
        $modeleBoutique = $this->createTestModeleBoutique($boutique, $modele, 10); // Stock initial de 10

        // Créer une réservation en attente
        $reservation = $this->createTestReservation($client, $boutique, $entreprise, $user);
        $ligneReservation = $this->createTestLigneReservation($reservation, $modeleBoutique, 3); // Réserver 3 articles

        $this->entityManager->flush();

        // Vérifier l'état initial
        $this->assertEquals(ReservationStatus::EN_ATTENTE->value, $reservation->getStatus());
        $this->assertEquals(10, $modeleBoutique->getQuantite()); // Stock non déduit
        $this->assertEquals(100, $modele->getQuantiteGlobale()); // Stock global non déduit

        // Act: Confirmer la réservation
        $result = $this->workflowService->confirmReservation(
            $reservation->getId(),
            $user,
            'Test de confirmation d\'intégration'
        );

        // Assert: Vérifier le résultat
        $this->assertTrue($result['success']);
        $this->assertEquals('Réservation confirmée avec succès', $result['message']);
        
        // Recharger les entités depuis la base de données
        $this->entityManager->refresh($reservation);
        $this->entityManager->refresh($modeleBoutique);
        $this->entityManager->refresh($modele);

        // Vérifier que le statut a changé
        $this->assertEquals(ReservationStatus::CONFIRMEE->value, $reservation->getStatus());
        $this->assertNotNull($reservation->getConfirmedAt());
        $this->assertEquals($user->getId(), $reservation->getConfirmedBy()->getId());

        // Vérifier que le stock a été déduit
        $this->assertEquals(7, $modeleBoutique->getQuantite()); // 10 - 3 = 7
        $this->assertEquals(97, $modele->getQuantiteGlobale()); // 100 - 3 = 97

        // Vérifier que l'audit trail a été créé
        $statusHistory = $reservation->getStatusHistory();
        $this->assertCount(1, $statusHistory);
        $this->assertEquals(ReservationStatus::EN_ATTENTE->value, $statusHistory[0]->getOldStatus());
        $this->assertEquals(ReservationStatus::CONFIRMEE->value, $statusHistory[0]->getNewStatus());
    }

    /**
     * Test du workflow d'annulation : création → annulation → vérification du stock
     */
    public function testCompleteCancellationWorkflow(): void
    {
        // Arrange: Créer les entités nécessaires
        $entreprise = $this->createTestEntreprise();
        $boutique = $this->createTestBoutique($entreprise);
        $client = $this->createTestClient($entreprise);
        $user = $this->createTestUser($entreprise, $boutique);
        $modele = $this->createTestModele($entreprise);
        $modeleBoutique = $this->createTestModeleBoutique($boutique, $modele, 10);

        // Créer une réservation en attente
        $reservation = $this->createTestReservation($client, $boutique, $entreprise, $user);
        $ligneReservation = $this->createTestLigneReservation($reservation, $modeleBoutique, 3);

        $this->entityManager->flush();

        // Vérifier l'état initial
        $this->assertEquals(ReservationStatus::EN_ATTENTE->value, $reservation->getStatus());
        $this->assertEquals(10, $modeleBoutique->getQuantite());
        $this->assertEquals(100, $modele->getQuantiteGlobale());

        // Act: Annuler la réservation
        $result = $this->workflowService->cancelReservation(
            $reservation->getId(),
            $user,
            'Client ne souhaite plus les articles'
        );

        // Assert: Vérifier le résultat
        $this->assertTrue($result['success']);
        $this->assertEquals('Réservation annulée avec succès', $result['message']);
        
        // Recharger les entités
        $this->entityManager->refresh($reservation);
        $this->entityManager->refresh($modeleBoutique);
        $this->entityManager->refresh($modele);

        // Vérifier que le statut a changé
        $this->assertEquals(ReservationStatus::ANNULEE->value, $reservation->getStatus());
        $this->assertNotNull($reservation->getCancelledAt());
        $this->assertEquals($user->getId(), $reservation->getCancelledBy()->getId());
        $this->assertEquals('Client ne souhaite plus les articles', $reservation->getCancellationReason());

        // Vérifier que le stock n'a PAS été affecté (car pas encore déduit)
        $this->assertEquals(10, $modeleBoutique->getQuantite());
        $this->assertEquals(100, $modele->getQuantiteGlobale());

        // Vérifier l'audit trail
        $statusHistory = $reservation->getStatusHistory();
        $this->assertCount(1, $statusHistory);
        $this->assertEquals(ReservationStatus::EN_ATTENTE->value, $statusHistory[0]->getOldStatus());
        $this->assertEquals(ReservationStatus::ANNULEE->value, $statusHistory[0]->getNewStatus());
    }

    /**
     * Test de confirmation avec stock insuffisant
     */
    public function testConfirmationWithInsufficientStock(): void
    {
        // Arrange: Créer une réservation avec plus d'articles que disponible
        $entreprise = $this->createTestEntreprise();
        $boutique = $this->createTestBoutique($entreprise);
        $client = $this->createTestClient($entreprise);
        $user = $this->createTestUser($entreprise, $boutique);
        $modele = $this->createTestModele($entreprise);
        $modeleBoutique = $this->createTestModeleBoutique($boutique, $modele, 2); // Seulement 2 en stock

        $reservation = $this->createTestReservation($client, $boutique, $entreprise, $user);
        $ligneReservation = $this->createTestLigneReservation($reservation, $modeleBoutique, 5); // Demander 5

        $this->entityManager->flush();

        // Réduire le stock après création pour simuler une vente entre temps
        $modeleBoutique->setQuantite(1); // Plus que 1 en stock
        $this->entityManager->flush();

        // Act & Assert: Tenter de confirmer doit échouer
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Stock insuffisant pour certains articles');

        $this->workflowService->confirmReservation(
            $reservation->getId(),
            $user,
            'Tentative de confirmation avec stock insuffisant'
        );
    }

    /**
     * Test de tentative de confirmation d'une réservation déjà confirmée
     */
    public function testDoubleConfirmationPrevention(): void
    {
        // Arrange: Créer et confirmer une réservation
        $entreprise = $this->createTestEntreprise();
        $boutique = $this->createTestBoutique($entreprise);
        $client = $this->createTestClient($entreprise);
        $user = $this->createTestUser($entreprise, $boutique);
        $modele = $this->createTestModele($entreprise);
        $modeleBoutique = $this->createTestModeleBoutique($boutique, $modele, 10);

        $reservation = $this->createTestReservation($client, $boutique, $entreprise, $user);
        $ligneReservation = $this->createTestLigneReservation($reservation, $modeleBoutique, 3);

        $this->entityManager->flush();

        // Première confirmation (doit réussir)
        $this->workflowService->confirmReservation($reservation->getId(), $user, 'Première confirmation');

        // Act & Assert: Deuxième confirmation doit échouer
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La réservation ne peut pas être confirmée. Statut actuel: confirmee');

        $this->workflowService->confirmReservation($reservation->getId(), $user, 'Tentative de double confirmation');
    }

    /**
     * Test de tentative d'annulation d'une réservation confirmée
     */
    public function testCancellationOfConfirmedReservation(): void
    {
        // Arrange: Créer et confirmer une réservation
        $entreprise = $this->createTestEntreprise();
        $boutique = $this->createTestBoutique($entreprise);
        $client = $this->createTestClient($entreprise);
        $user = $this->createTestUser($entreprise, $boutique);
        $modele = $this->createTestModele($entreprise);
        $modeleBoutique = $this->createTestModeleBoutique($boutique, $modele, 10);

        $reservation = $this->createTestReservation($client, $boutique, $entreprise, $user);
        $ligneReservation = $this->createTestLigneReservation($reservation, $modeleBoutique, 3);

        $this->entityManager->flush();

        // Confirmer la réservation
        $this->workflowService->confirmReservation($reservation->getId(), $user, 'Confirmation');

        // Act & Assert: Tentative d'annulation doit échouer
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La réservation ne peut pas être annulée. Statut actuel: confirmee');

        $this->workflowService->cancelReservation($reservation->getId(), $user, 'Tentative d\'annulation après confirmation');
    }

    // Méthodes utilitaires pour créer les entités de test

    private function createTestEntreprise(): Entreprise
    {
        $entreprise = new Entreprise();
        $entreprise->setLibelle('Entreprise Test');
        $entreprise->setCreatedAtValue(new \DateTime());
        $entreprise->setUpdatedAt(new \DateTime());
        $entreprise->setIsActive(true);

        $this->entityManager->persist($entreprise);
        return $entreprise;
    }

    private function createTestBoutique(Entreprise $entreprise): Boutique
    {
        $boutique = new Boutique();
        $boutique->setLibelle('Boutique Test');
        $boutique->setEntreprise($entreprise);
        $boutique->setCreatedAtValue(new \DateTime());
        $boutique->setUpdatedAt(new \DateTime());
        $boutique->setIsActive(true);

        $this->entityManager->persist($boutique);
        return $boutique;
    }

    private function createTestClient(Entreprise $entreprise): Client
    {
        $client = new Client();
        $client->setNom('Doe');
        $client->setPrenom('John');
        $client->setNumero('CLI001');
        $client->setEntreprise($entreprise);
        $client->setCreatedAtValue(new \DateTime());
        $client->setUpdatedAt(new \DateTime());
        $client->setIsActive(true);

        $this->entityManager->persist($client);
        return $client;
    }

    private function createTestUser(Entreprise $entreprise, Boutique $boutique): User
    {
        $user = new User();
        $user->setLogin('testuser');
        $user->setNom('Test');
        $user->setPrenoms('User');
        $user->setEntreprise($entreprise);
        $user->setBoutique($boutique);
        $user->setCreatedAtValue(new \DateTime());
        $user->setUpdatedAt(new \DateTime());
        $user->setIsActive(true);

        $this->entityManager->persist($user);
        return $user;
    }

    private function createTestModele(Entreprise $entreprise): Modele
    {
        $modele = new Modele();
        $modele->setLibelle('Modèle Test');
        $modele->setNom('Robe Test');
        $modele->setQuantiteGlobale(100);
        $modele->setEntreprise($entreprise);
        $modele->setCreatedAtValue(new \DateTime());
        $modele->setUpdatedAt(new \DateTime());
        $modele->setIsActive(true);

        $this->entityManager->persist($modele);
        return $modele;
    }

    private function createTestModeleBoutique(Boutique $boutique, Modele $modele, int $quantite): ModeleBoutique
    {
        $modeleBoutique = new ModeleBoutique();
        $modeleBoutique->setBoutique($boutique);
        $modeleBoutique->setModele($modele);
        $modeleBoutique->setQuantite($quantite);
        $modeleBoutique->setCreatedAtValue(new \DateTime());
        $modeleBoutique->setUpdatedAt(new \DateTime());
        $modeleBoutique->setIsActive(true);

        $this->entityManager->persist($modeleBoutique);
        return $modeleBoutique;
    }

    private function createTestReservation(Client $client, Boutique $boutique, Entreprise $entreprise, User $user): Reservation
    {
        $reservation = new Reservation();
        $reservation->setClient($client);
        $reservation->setBoutique($boutique);
        $reservation->setEntreprise($entreprise);
        $reservation->setMontant(50000);
        $reservation->setAvance(20000);
        $reservation->setReste(30000);
        $reservation->setDateRetrait(new \DateTime('+7 days'));
        $reservation->setStatus(ReservationStatus::EN_ATTENTE->value);
        $reservation->setCreatedAtValue(new \DateTime());
        $reservation->setUpdatedAt(new \DateTime());
        $reservation->setCreatedBy($user);
        $reservation->setUpdatedBy($user);
        $reservation->setIsActive(true);

        $this->entityManager->persist($reservation);
        return $reservation;
    }

    private function createTestLigneReservation(Reservation $reservation, ModeleBoutique $modeleBoutique, int $quantite): LigneReservation
    {
        $ligne = new LigneReservation();
        $ligne->setReservation($reservation);
        $ligne->setModele($modeleBoutique);
        $ligne->setQuantite($quantite);
        $ligne->setAvanceModele(5000);
        $ligne->setCreatedAtValue(new \DateTime());
        $ligne->setUpdatedAt(new \DateTime());
        $ligne->setIsActive(true);

        $reservation->addLigneReservation($ligne);
        $this->entityManager->persist($ligne);
        return $ligne;
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Nettoyer la base de données après chaque test
        $this->entityManager->close();
        $this->entityManager = null;
    }
}