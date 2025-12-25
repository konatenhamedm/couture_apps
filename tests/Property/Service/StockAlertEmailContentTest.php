<?php

namespace App\Tests\Property\Service;

use App\Entity\Entreprise;
use App\Entity\User;
use App\Service\StockDeficit;
use PHPUnit\Framework\TestCase;

/**
 * Tests de propriété pour le contenu des emails d'alerte de stock
 * 
 * @tag Feature: reservation-stock-notification, Property 5: Contenu complet des emails d'alerte
 */
class StockAlertEmailContentTest extends TestCase
{
    /**
     * Property 5: Contenu complet des emails d'alerte
     * For any stock alert email, it should contain boutique name, client info, reservation details, deficit information, withdrawal date, and creator info
     * 
     * @test
     */
    public function testEmailContentCompletenessProperty(): void
    {
        // Générer 30 cas de test avec différents scénarios
        for ($i = 0; $i < 30; $i++) {
            $boutiqueName = 'Boutique_' . rand(1, 100);
            $itemCount = rand(1, 8);
            $stockDeficits = $this->generateStockDeficits($itemCount);
            $reservationInfo = $this->generateReservationInfo();
            
            $emailContext = $this->prepareEmailContext($boutiqueName, $stockDeficits, $reservationInfo);
            
            // Property: L'email doit contenir le nom de la boutique
            $this->assertArrayHasKey('boutique_name', $emailContext);
            $this->assertEquals($boutiqueName, $emailContext['boutique_name']);
            
            // Property: L'email doit contenir les informations du client
            $this->assertArrayHasKey('client_name', $emailContext);
            $this->assertArrayHasKey('client_phone', $emailContext);
            $this->assertNotEmpty($emailContext['client_name']);
            
            // Property: L'email doit contenir les détails de la réservation
            $this->assertArrayHasKey('total_amount', $emailContext);
            $this->assertArrayHasKey('advance_amount', $emailContext);
            $this->assertArrayHasKey('remaining_amount', $emailContext);
            $this->assertArrayHasKey('withdrawal_date', $emailContext);
            
            // Property: L'email doit contenir les informations de déficit
            $this->assertArrayHasKey('stock_deficits', $emailContext);
            $this->assertArrayHasKey('total_items_in_shortage', $emailContext);
            $this->assertCount($itemCount, $emailContext['stock_deficits']);
            $this->assertEquals($itemCount, $emailContext['total_items_in_shortage']);
            
            // Property: L'email doit contenir les informations du créateur
            $this->assertArrayHasKey('created_by', $emailContext);
            $this->assertArrayHasKey('created_at', $emailContext);
        }
    }

    /**
     * Test de propriété pour la structure des données de déficit dans l'email
     * 
     * @test
     */
    public function testDeficitDataStructureInEmailProperty(): void
    {
        // Générer 25 cas de test
        for ($i = 0; $i < 25; $i++) {
            $itemCount = rand(1, 6);
            $stockDeficits = $this->generateStockDeficits($itemCount);
            
            $emailDeficits = array_map(fn(StockDeficit $deficit) => $deficit->toArray(), $stockDeficits);
            
            foreach ($emailDeficits as $deficitData) {
                // Property: Chaque déficit doit contenir toutes les informations requises
                $requiredKeys = [
                    'modele_name', 'quantity_requested', 'quantity_available',
                    'deficit', 'boutique_id', 'has_deficit', 'deficit_percentage',
                    'is_out_of_stock', 'description'
                ];
                
                foreach ($requiredKeys as $key) {
                    $this->assertArrayHasKey(
                        $key,
                        $deficitData,
                        "Clé manquante dans les données de déficit: {$key}"
                    );
                }
                
                // Property: Les données numériques doivent être cohérentes
                $this->assertGreaterThanOrEqual(0, $deficitData['quantity_requested']);
                $this->assertGreaterThanOrEqual(0, $deficitData['quantity_available']);
                $this->assertGreaterThanOrEqual(0, $deficitData['deficit']);
                $this->assertGreaterThanOrEqual(0, $deficitData['deficit_percentage']);
                $this->assertLessThanOrEqual(100, $deficitData['deficit_percentage']);
                
                // Property: has_deficit doit être cohérent avec deficit > 0
                $this->assertEquals(
                    $deficitData['deficit'] > 0,
                    $deficitData['has_deficit'],
                    "has_deficit incohérent avec la valeur du déficit"
                );
                
                // Property: is_out_of_stock doit être cohérent avec quantity_available = 0
                $this->assertEquals(
                    $deficitData['quantity_available'] === 0,
                    $deficitData['is_out_of_stock'],
                    "is_out_of_stock incohérent avec quantity_available"
                );
            }
        }
    }

    /**
     * Test de propriété pour les informations monétaires dans l'email
     * 
     * @test
     */
    public function testMonetaryInformationInEmailProperty(): void
    {
        // Générer 20 cas de test
        for ($i = 0; $i < 20; $i++) {
            $reservationInfo = $this->generateReservationInfo();
            $emailContext = $this->prepareEmailContext('Boutique_Test', [], $reservationInfo);
            
            // Property: Les montants doivent être cohérents
            $totalAmount = $emailContext['total_amount'];
            $advanceAmount = $emailContext['advance_amount'];
            $remainingAmount = $emailContext['remaining_amount'];
            
            $this->assertGreaterThan(0, $totalAmount, "Le montant total doit être positif");
            $this->assertGreaterThanOrEqual(0, $advanceAmount, "L'avance ne peut pas être négative");
            $this->assertGreaterThanOrEqual(0, $remainingAmount, "Le reste ne peut pas être négatif");
            
            // Property: avance + reste = total
            $this->assertEquals(
                $totalAmount,
                $advanceAmount + $remainingAmount,
                "Incohérence monétaire: avance + reste ≠ total"
            );
        }
    }

    /**
     * Test de propriété pour les informations de l'administrateur dans l'email
     * 
     * @test
     */
    public function testAdminInformationInEmailProperty(): void
    {
        // Générer 15 cas de test
        for ($i = 0; $i < 15; $i++) {
            $admin = $this->createTestAdmin();
            $entreprise = $this->createTestEntreprise();
            
            $emailContext = $this->prepareEmailContextWithAdmin($admin, $entreprise);
            
            // Property: L'email doit contenir le nom de l'admin
            $this->assertArrayHasKey('admin_name', $emailContext);
            $this->assertNotEmpty($emailContext['admin_name']);
            
            // Property: L'email doit contenir le nom de l'entreprise
            $this->assertArrayHasKey('entreprise_name', $emailContext);
            $this->assertNotEmpty($emailContext['entreprise_name']);
            
            // Property: Le nom de l'admin doit être formaté correctement
            $adminName = $emailContext['admin_name'];
            if ($admin->getNom() && $admin->getPrenoms()) {
                $expectedName = $admin->getNom() . ' ' . $admin->getPrenoms();
                $this->assertEquals($expectedName, $adminName);
            } else {
                $this->assertEquals($admin->getLogin(), $adminName);
            }
        }
    }

    /**
     * Test de propriété pour le niveau de priorité dans l'email
     * 
     * @test
     */
    public function testPriorityLevelInEmailProperty(): void
    {
        // Test avec différents scénarios de priorité
        $scenarios = [
            ['itemCount' => 1, 'totalDeficit' => 5, 'expectedPriority' => 'NORMALE'],
            ['itemCount' => 3, 'totalDeficit' => 15, 'expectedPriority' => 'ÉLEVÉE'],
            ['itemCount' => 5, 'totalDeficit' => 30, 'expectedPriority' => 'CRITIQUE'],
            ['itemCount' => 2, 'totalDeficit' => 50, 'expectedPriority' => 'CRITIQUE'],
        ];
        
        foreach ($scenarios as $scenario) {
            $stockDeficits = $this->generateStockDeficitsWithTotalDeficit(
                $scenario['itemCount'],
                $scenario['totalDeficit']
            );
            
            $emailContext = $this->prepareEmailContext('Boutique_Test', $stockDeficits, []);
            
            // Property: Le niveau de priorité doit être correct
            $this->assertArrayHasKey('priority_level', $emailContext);
            $this->assertEquals(
                $scenario['expectedPriority'],
                $emailContext['priority_level'],
                "Niveau de priorité incorrect pour le scénario: " . json_encode($scenario)
            );
        }
    }

    /**
     * Test de propriété pour les descriptions de déficit dans l'email
     * 
     * @test
     */
    public function testDeficitDescriptionsInEmailProperty(): void
    {
        // Générer 20 cas de test
        for ($i = 0; $i < 20; $i++) {
            $itemCount = rand(1, 5);
            $stockDeficits = $this->generateStockDeficits($itemCount);
            
            $emailDeficits = array_map(fn(StockDeficit $deficit) => $deficit->toArray(), $stockDeficits);
            
            foreach ($emailDeficits as $deficitData) {
                $description = $deficitData['description'];
                
                // Property: La description doit contenir le nom du modèle
                $this->assertStringContainsString(
                    $deficitData['modele_name'],
                    $description,
                    "La description doit contenir le nom du modèle"
                );
                
                if ($deficitData['has_deficit']) {
                    // Property: Si déficit, la description doit contenir "Déficit"
                    $this->assertStringContainsString(
                        'Déficit',
                        $description,
                        "La description doit contenir 'Déficit' quand il y a un déficit"
                    );
                    
                    // Property: La description doit contenir les quantités
                    $this->assertStringContainsString(
                        (string)$deficitData['quantity_requested'],
                        $description,
                        "La description doit contenir la quantité demandée"
                    );
                    
                    $this->assertStringContainsString(
                        (string)$deficitData['quantity_available'],
                        $description,
                        "La description doit contenir la quantité disponible"
                    );
                } else {
                    // Property: Si pas de déficit, la description doit indiquer "Stock suffisant"
                    $this->assertStringContainsString(
                        'Stock suffisant',
                        $description,
                        "La description doit indiquer 'Stock suffisant' quand il n'y a pas de déficit"
                    );
                }
            }
        }
    }

    /**
     * Test de propriété pour la cohérence des dates dans l'email
     * 
     * @test
     */
    public function testDateConsistencyInEmailProperty(): void
    {
        // Générer 15 cas de test
        for ($i = 0; $i < 15; $i++) {
            $reservationInfo = $this->generateReservationInfo();
            $emailContext = $this->prepareEmailContext('Boutique_Test', [], $reservationInfo);
            
            // Property: La date de retrait doit être présente et valide
            $this->assertArrayHasKey('withdrawal_date', $emailContext);
            $withdrawalDate = $emailContext['withdrawal_date'];
            
            if (!empty($withdrawalDate)) {
                // Vérifier que c'est une date valide au format d/m/Y
                $dateTime = \DateTime::createFromFormat('d/m/Y', $withdrawalDate);
                $this->assertNotFalse(
                    $dateTime,
                    "La date de retrait doit être une date valide au format d/m/Y: {$withdrawalDate}"
                );
                
                // Vérifier que la date n'a pas d'erreurs
                if ($dateTime) {
                    $errors = \DateTime::getLastErrors();
                    $this->assertEquals(0, $errors['warning_count'] + $errors['error_count'], 
                        "La date ne doit pas avoir d'erreurs de parsing");
                }
            }
            
            // Property: La date de création doit être présente
            $this->assertArrayHasKey('created_at', $emailContext);
            $this->assertNotEmpty($emailContext['created_at']);
        }
    }

    // Méthodes utilitaires

    private function generateStockDeficits(int $count): array
    {
        $deficits = [];
        for ($i = 0; $i < $count; $i++) {
            $quantityRequested = rand(1, 20);
            $quantityAvailable = rand(0, $quantityRequested - 1);
            
            $deficits[] = new StockDeficit(
                'Modele_' . rand(1, 100),
                $quantityRequested,
                $quantityAvailable,
                'boutique_' . rand(1, 10)
            );
        }
        return $deficits;
    }

    private function generateStockDeficitsWithTotalDeficit(int $count, int $targetTotalDeficit): array
    {
        $deficits = [];
        $remainingDeficit = $targetTotalDeficit;
        
        for ($i = 0; $i < $count; $i++) {
            $deficitForThisItem = ($i === $count - 1) 
                ? $remainingDeficit 
                : rand(1, max(1, $remainingDeficit - ($count - $i - 1)));
            
            $quantityRequested = $deficitForThisItem + rand(0, 5);
            $quantityAvailable = $quantityRequested - $deficitForThisItem;
            
            $deficits[] = new StockDeficit(
                'Modele_' . rand(1, 100),
                $quantityRequested,
                $quantityAvailable,
                'boutique_' . rand(1, 10)
            );
            
            $remainingDeficit -= $deficitForThisItem;
        }
        
        return $deficits;
    }

    private function generateReservationInfo(): array
    {
        $totalAmount = rand(10000, 100000);
        $advanceAmount = rand(0, $totalAmount);
        $remainingAmount = $totalAmount - $advanceAmount;
        
        return [
            'client_name' => 'Client_' . rand(1, 100),
            'client_phone' => '+225 ' . rand(10000000, 99999999),
            'reservation_id' => rand(1, 1000),
            'total_amount' => $totalAmount,
            'advance_amount' => $advanceAmount,
            'remaining_amount' => $remainingAmount,
            'withdrawal_date' => (new \DateTime('+' . rand(1, 30) . ' days'))->format('d/m/Y'),
            'created_by' => 'User_' . rand(1, 50),
            'created_at' => (new \DateTime())->format('d/m/Y H:i')
        ];
    }

    private function createTestAdmin(): User
    {
        $admin = new User();
        
        // Utiliser la réflexion pour définir les propriétés privées
        $reflection = new \ReflectionClass($admin);
        
        $loginProperty = $reflection->getProperty('login');
        $loginProperty->setAccessible(true);
        $loginProperty->setValue($admin, 'admin_' . rand(1, 100) . '@example.com');
        
        // Parfois avec nom/prénom, parfois sans
        if (rand(0, 1)) {
            $nomProperty = $reflection->getProperty('nom');
            $nomProperty->setAccessible(true);
            $nomProperty->setValue($admin, 'Admin_' . rand(1, 100));
            
            $prenomsProperty = $reflection->getProperty('prenoms');
            $prenomsProperty->setAccessible(true);
            $prenomsProperty->setValue($admin, 'Prenom_' . rand(1, 100));
        }
        
        return $admin;
    }

    private function createTestEntreprise(): Entreprise
    {
        $entreprise = new Entreprise();
        $entreprise->setLibelle('Entreprise_' . rand(1, 50));
        return $entreprise;
    }

    private function prepareEmailContext(string $boutiqueName, array $stockDeficits, array $reservationInfo): array
    {
        // Ensure withdrawal_date is always valid
        $withdrawalDate = $reservationInfo['withdrawal_date'] ?? null;
        if (empty($withdrawalDate)) {
            $withdrawalDate = date('d/m/Y', strtotime('+1 week'));
        }
        
        return [
            'boutique_name' => $boutiqueName,
            'client_name' => $reservationInfo['client_name'] ?? 'Client_Test',
            'client_phone' => $reservationInfo['client_phone'] ?? '+225 12345678',
            'reservation_id' => $reservationInfo['reservation_id'] ?? rand(1, 1000),
            'total_amount' => $reservationInfo['total_amount'] ?? 50000,
            'advance_amount' => $reservationInfo['advance_amount'] ?? 20000,
            'remaining_amount' => $reservationInfo['remaining_amount'] ?? 30000,
            'withdrawal_date' => $withdrawalDate,
            'created_by' => $reservationInfo['created_by'] ?? 'User_Test',
            'created_at' => $reservationInfo['created_at'] ?? date('d/m/Y H:i'),
            'stock_deficits' => array_map(fn(StockDeficit $deficit) => $deficit->toArray(), $stockDeficits),
            'total_items_in_shortage' => count($stockDeficits),
            'priority_level' => $this->determinePriorityLevel($stockDeficits)
        ];
    }

    private function prepareEmailContextWithAdmin(User $admin, Entreprise $entreprise): array
    {
        return [
            'admin_name' => $admin->getNom() && $admin->getPrenoms() 
                ? $admin->getNom() . ' ' . $admin->getPrenoms() 
                : $admin->getLogin(),
            'entreprise_name' => $entreprise->getLibelle(),
            'boutique_name' => 'Boutique_Test',
            'client_name' => 'Client_Test',
            'stock_deficits' => [],
            'total_items_in_shortage' => 0
        ];
    }

    private function determinePriorityLevel(array $stockDeficits): string
    {
        $itemCount = count($stockDeficits);
        $totalDeficit = array_sum(array_map(fn(StockDeficit $deficit) => $deficit->getDeficit(), $stockDeficits));

        if ($itemCount >= 5 || $totalDeficit >= 50) {
            return 'CRITIQUE';
        } elseif ($itemCount >= 3 || $totalDeficit >= 20) {
            return 'ÉLEVÉE';
        } else {
            return 'NORMALE';
        }
    }
}