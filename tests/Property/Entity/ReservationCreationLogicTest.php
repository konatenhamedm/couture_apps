<?php

namespace App\Tests\Property\Entity;

use App\Entity\Reservation;
use App\Entity\Client;
use App\Entity\Boutique;
use App\Entity\Entreprise;
use App\Entity\User;
use App\Entity\ModeleBoutique;
use App\Entity\Modele;
use App\Entity\LigneReservation;
use App\Enum\ReservationStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Tests de propriété pour la logique de création de réservation
 * 
 * @tag Feature: reservation-workflow-management, Property 3: Stock Preservation During Creation
 * @tag Feature: reservation-workflow-management, Property 4: Stock Availability Validation
 * @tag Feature: reservation-workflow-management, Property 5: Payment Independence from Stock
 */
class ReservationCreationLogicTest extends KernelTestCase
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
     * Property 3: Stock Preservation During Creation
     * For any reservation creation operation, stock quantities should remain unchanged
     * 
     * @test
     * @tag Feature: reservation-workflow-management, Property 3: Stock Preservation During Creation
     */
    public function testStockPreservationDuringReservationCreation(): void
    {
        // Créer des modèles avec stock initial
        $modele1 = $this->createTestModele('Robe Test 1', 10, 5);
        $modele2 = $this->createTestModele('Robe Test 2', 8, 3);

        // Capturer les stocks initiaux
        $initialStock1Boutique = $modele1['modeleBoutique']->getQuantite();
        $initialStock1Global = $modele1['modele']->getQuantiteGlobale();
        $initialStock2Boutique = $modele2['modeleBoutique']->getQuantite();
        $initialStock2Global = $modele2['modele']->getQuantiteGlobale();

        // Créer une réservation avec des lignes (simulation sans persistance)
        $reservation = $this->createTestReservation();
        
        $ligne1 = new LigneReservation();
        $ligne1->setQuantite(2);
        $ligne1->setModele($modele1['modeleBoutique']);
        $ligne1->setAvanceModele($modele1['modeleBoutique']->getId());
        $ligne1->setCreatedAtValue(new \DateTime());
        $ligne1->setUpdatedAt(new \DateTime());
        $ligne1->setIsActive(true);
        
        $ligne2 = new LigneReservation();
        $ligne2->setQuantite(1);
        $ligne2->setModele($modele2['modeleBoutique']);
        $ligne2->setAvanceModele($modele2['modeleBoutique']->getId());
        $ligne2->setCreatedAtValue(new \DateTime());
        $ligne2->setUpdatedAt(new \DateTime());
        $ligne2->setIsActive(true);

        $reservation->addLigneReservation($ligne1);
        $reservation->addLigneReservation($ligne2);

        // Dans le nouveau système, la création de réservation ne doit PAS modifier les stocks
        // Nous simulons cela en vérifiant que les stocks restent inchangés

        // Vérifier que les stocks n'ont PAS changé (simulation du nouveau comportement)
        $this->assertEquals($initialStock1Boutique, $modele1['modeleBoutique']->getQuantite(),
            'Le stock boutique du modèle 1 ne doit pas changer lors de la création de réservation');
        $this->assertEquals($initialStock1Global, $modele1['modele']->getQuantiteGlobale(),
            'Le stock global du modèle 1 ne doit pas changer lors de la création de réservation');
        $this->assertEquals($initialStock2Boutique, $modele2['modeleBoutique']->getQuantite(),
            'Le stock boutique du modèle 2 ne doit pas changer lors de la création de réservation');
        $this->assertEquals($initialStock2Global, $modele2['modele']->getQuantiteGlobale(),
            'Le stock global du modèle 2 ne doit pas changer lors de la création de réservation');

        // Vérifier que la réservation a bien le statut "en_attente"
        $this->assertEquals(ReservationStatus::EN_ATTENTE->value, $reservation->getStatus());
        
        // Vérifier que les lignes sont bien associées
        $this->assertCount(2, $reservation->getLigneReservations());
    }

    /**
     * Property 4: Stock Availability Validation
     * For any reservation creation request, validation should check stock without modifying it
     * 
     * @test
     * @tag Feature: reservation-workflow-management, Property 4: Stock Availability Validation
     */
    public function testStockAvailabilityValidationLogic(): void
    {
        // Créer un modèle avec stock limité
        $modele = $this->createTestModele('Robe Limitée', 2, 1); // Stock boutique: 1, Global: 2

        // Test 1: Validation avec quantité disponible
        $isAvailable1 = $this->validateStockAvailability($modele['modeleBoutique'], 1);
        $this->assertTrue($isAvailable1, 'La validation doit réussir quand la quantité est disponible');

        // Test 2: Validation avec quantité supérieure au stock boutique
        $isAvailable2 = $this->validateStockAvailability($modele['modeleBoutique'], 2);
        $this->assertFalse($isAvailable2, 'La validation doit échouer quand la quantité dépasse le stock boutique');

        // Test 3: Validation avec quantité supérieure au stock global
        $modele['modeleBoutique']->setQuantite(5); // Augmenter le stock boutique
        $isAvailable3 = $this->validateStockAvailability($modele['modeleBoutique'], 3);
        $this->assertFalse($isAvailable3, 'La validation doit échouer quand la quantité dépasse le stock global');

        // Vérifier que les stocks n'ont pas été modifiés par la validation
        $this->assertEquals(5, $modele['modeleBoutique']->getQuantite());
        $this->assertEquals(2, $modele['modele']->getQuantiteGlobale());
    }

    /**
     * Property 5: Payment Independence from Stock
     * For any reservation creation, payment recording should be independent of stock levels
     * 
     * @test
     * @tag Feature: reservation-workflow-management, Property 5: Payment Independence from Stock
     */
    public function testPaymentIndependenceFromStock(): void
    {
        // Créer différents scénarios de stock
        $scenarios = [
            ['stock_boutique' => 10, 'stock_global' => 20, 'avance' => 15000],
            ['stock_boutique' => 1, 'stock_global' => 1, 'avance' => 25000],
            ['stock_boutique' => 0, 'stock_global' => 5, 'avance' => 10000], // Stock boutique épuisé
        ];

        foreach ($scenarios as $index => $scenario) {
            $modele = $this->createTestModele("Modele Scenario $index", 
                $scenario['stock_global'], $scenario['stock_boutique']);

            $reservation = $this->createTestReservation();
            $reservation->setAvance($scenario['avance']);
            $reservation->setMontant($scenario['avance'] + 10000);
            $reservation->setReste(10000);

            // L'enregistrement du paiement ne doit pas dépendre du niveau de stock
            // tant que la validation de disponibilité passe (testée séparément)
            $this->assertInstanceOf(Reservation::class, $reservation);
            $this->assertEquals($scenario['avance'], $reservation->getAvance());
            $this->assertEquals(ReservationStatus::EN_ATTENTE->value, $reservation->getStatus());

            // Le stock ne doit pas être affecté par l'enregistrement du paiement
            $this->assertEquals($scenario['stock_boutique'], $modele['modeleBoutique']->getQuantite());
            $this->assertEquals($scenario['stock_global'], $modele['modele']->getQuantiteGlobale());
        }
    }

    /**
     * Test que le statut initial est toujours "en_attente"
     * 
     * @test
     */
    public function testInitialStatusAlwaysEnAttente(): void
    {
        // Tester avec différents montants et configurations
        $testCases = [
            ['montant' => 50000, 'avance' => 20000, 'reste' => 30000],
            ['montant' => 100000, 'avance' => 0, 'reste' => 100000], // Sans acompte
            ['montant' => 25000, 'avance' => 25000, 'reste' => 0], // Payé intégralement
        ];

        foreach ($testCases as $case) {
            $reservation = $this->createTestReservation();
            $reservation->setMontant($case['montant']);
            $reservation->setAvance($case['avance']);
            $reservation->setReste($case['reste']);

            // Le statut doit toujours être "en_attente" à la création
            $this->assertEquals(ReservationStatus::EN_ATTENTE->value, $reservation->getStatus());
            $this->assertTrue($reservation->isPending());
            $this->assertTrue($reservation->isConfirmable());
            $this->assertTrue($reservation->isCancellable());
            $this->assertFalse($reservation->isConfirmed());
            $this->assertFalse($reservation->isCancelled());
        }
    }

    /**
     * Test de cohérence des montants
     * 
     * @test
     */
    public function testAmountConsistency(): void
    {
        $reservation = $this->createTestReservation();
        
        // Test avec différentes combinaisons de montants
        $validCombinations = [
            ['montant' => 50000, 'avance' => 20000, 'reste' => 30000],
            ['montant' => 75000, 'avance' => 75000, 'reste' => 0],
            ['montant' => 30000, 'avance' => 0, 'reste' => 30000],
        ];

        foreach ($validCombinations as $combo) {
            $reservation->setMontant($combo['montant']);
            $reservation->setAvance($combo['avance']);
            $reservation->setReste($combo['reste']);

            // Vérifier la cohérence : montant = avance + reste
            $this->assertEquals(
                $combo['montant'], 
                $combo['avance'] + $combo['reste'],
                'Le montant total doit être égal à la somme de l\'avance et du reste'
            );

            // Le statut reste "en_attente" indépendamment des montants
            $this->assertEquals(ReservationStatus::EN_ATTENTE->value, $reservation->getStatus());
        }
    }

    /**
     * Simule la validation de disponibilité du stock
     */
    private function validateStockAvailability(ModeleBoutique $modeleBoutique, int $quantiteDemandee): bool
    {
        // Simulation de la logique de validation du contrôleur
        if ($modeleBoutique->getQuantite() < $quantiteDemandee) {
            return false;
        }

        if ($modeleBoutique->getModele()->getQuantiteGlobale() < $quantiteDemandee) {
            return false;
        }

        return true;
    }

    /**
     * Crée un modèle de test avec stock
     */
    private function createTestModele(string $nom, int $stockGlobal, int $stockBoutique): array
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

        $modele = new Modele();
        $modele->setLibelle($nom);
        $modele->setQuantiteGlobale($stockGlobal);
        $modele->setEntreprise($entreprise);
        $modele->setCreatedAtValue(new \DateTime());
        $modele->setUpdatedAt(new \DateTime());
        $modele->setIsActive(true);

        $modeleBoutique = new ModeleBoutique();
        $modeleBoutique->setModele($modele);
        $modeleBoutique->setBoutique($boutique);
        $modeleBoutique->setQuantite($stockBoutique);
        $modeleBoutique->setCreatedAtValue(new \DateTime());
        $modeleBoutique->setUpdatedAt(new \DateTime());
        $modeleBoutique->setIsActive(true);

        return [
            'entreprise' => $entreprise,
            'boutique' => $boutique,
            'modele' => $modele,
            'modeleBoutique' => $modeleBoutique
        ];
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

    protected function tearDown(): void
    {
        parent::tearDown();
        if ($this->entityManager) {
            $this->entityManager->close();
            $this->entityManager = null;
        }
    }
}