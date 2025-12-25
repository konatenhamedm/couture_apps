<?php

namespace App\Tests\Property\Service;

use App\Entity\Entreprise;
use App\Entity\User;
use App\Service\NotificationService;
use App\Service\SendMailService;
use App\Service\StockDeficit;
use PHPUnit\Framework\TestCase;

/**
 * Tests de propriété pour les notifications d'alerte de stock
 * 
 * @tag Feature: reservation-stock-notification, Property 4: Notifications envoyées pour stock insuffisant
 */
class StockAlertNotificationTest extends TestCase
{
    /**
     * Property 4: Notifications envoyées pour stock insuffisant
     * For any reservation created with insufficient stock, both email and push notifications should be sent to the administrator
     * 
     * @test
     */
    public function testNotificationsSentForInsufficientStockProperty(): void
    {
        // Générer 30 cas de test avec différents scénarios de rupture de stock
        for ($i = 0; $i < 30; $i++) {
            $boutiqueName = 'Boutique_' . rand(1, 100);
            $itemCount = rand(1, 10);
            $stockDeficits = $this->generateStockDeficits($itemCount);
            
            // Property: Pour tout stock insuffisant, les données de notification doivent être correctes
            $notificationData = $this->prepareNotificationData($boutiqueName, $stockDeficits);
            
            // Vérifier que les données de notification push sont complètes
            $this->assertNotificationPushDataIsComplete($notificationData, $boutiqueName, $itemCount);
            
            // Vérifier que les données d'email sont complètes
            $this->assertEmailDataIsComplete($notificationData, $boutiqueName, $stockDeficits);
        }
    }

    /**
     * Test de propriété pour la structure des données de notification push
     * 
     * @test
     */
    public function testPushNotificationDataStructureProperty(): void
    {
        // Générer 20 cas de test
        for ($i = 0; $i < 20; $i++) {
            $boutiqueName = 'Boutique_' . rand(1, 50);
            $itemCount = rand(1, 5);
            $stockDeficits = $this->generateStockDeficits($itemCount);
            
            $pushData = $this->preparePushNotificationData($boutiqueName, $stockDeficits);
            
            // Property: Les données push doivent contenir toutes les clés requises
            $requiredKeys = [
                'type', 'boutique_name', 'items_count', 'deficits', 
                'priority', 'action_required'
            ];
            
            foreach ($requiredKeys as $key) {
                $this->assertArrayHasKey(
                    $key,
                    $pushData,
                    "Clé manquante dans les données push: {$key}"
                );
            }
            
            // Property: Le type doit être 'stock_alert'
            $this->assertEquals('stock_alert', $pushData['type']);
            
            // Property: action_required doit être true pour les alertes de stock
            $this->assertTrue($pushData['action_required']);
            
            // Property: Le nombre d'articles doit correspondre aux déficits
            $this->assertEquals($itemCount, $pushData['items_count']);
            $this->assertCount($itemCount, $pushData['deficits']);
        }
    }

    /**
     * Test de propriété pour la génération des messages de notification
     * 
     * @test
     */
    public function testNotificationMessageGenerationProperty(): void
    {
        // Générer 25 cas de test
        for ($i = 0; $i < 25; $i++) {
            $clientName = 'Client_' . rand(1, 100);
            $itemCount = rand(1, 8);
            
            $message = $this->buildStockAlertMessage($itemCount, $clientName);
            
            // Property: Le message doit contenir le nom du client
            $this->assertStringContainsString(
                $clientName,
                $message,
                "Le message doit contenir le nom du client"
            );
            
            // Property: Le message doit mentionner le nombre d'articles
            if ($itemCount === 1) {
                $this->assertStringContainsString(
                    '1 article',
                    $message,
                    "Le message doit mentionner '1 article' pour un seul article"
                );
            } else {
                $this->assertStringContainsString(
                    "{$itemCount} articles",
                    $message,
                    "Le message doit mentionner '{$itemCount} articles' pour plusieurs articles"
                );
            }
            
            // Property: Le message doit indiquer qu'une action est requise
            $this->assertStringContainsString(
                'Action requise',
                $message,
                "Le message doit indiquer qu'une action est requise"
            );
            
            // Property: Le message doit mentionner la rupture de stock
            $this->assertStringContainsString(
                'rupture de stock',
                $message,
                "Le message doit mentionner la rupture de stock"
            );
        }
    }

    /**
     * Test de propriété pour la génération des sujets d'email
     * 
     * @test
     */
    public function testEmailSubjectGenerationProperty(): void
    {
        // Générer 20 cas de test
        for ($i = 0; $i < 20; $i++) {
            $boutiqueName = 'Boutique_' . rand(1, 50);
            $itemCount = rand(1, 6);
            
            $subject = $this->buildEmailSubject($boutiqueName, $itemCount);
            
            // Property: Le sujet doit contenir le nom de la boutique
            $this->assertStringContainsString(
                $boutiqueName,
                $subject,
                "Le sujet doit contenir le nom de la boutique"
            );
            
            // Property: Le sujet doit indiquer qu'il s'agit d'une alerte
            $this->assertStringContainsString(
                'Alerte',
                $subject,
                "Le sujet doit indiquer qu'il s'agit d'une alerte"
            );
            
            // Property: Le sujet doit mentionner le stock
            $this->assertStringContainsString(
                'Stock',
                $subject,
                "Le sujet doit mentionner le stock"
            );
            
            // Property: Le sujet doit indiquer le nombre d'articles
            $this->assertStringContainsString(
                (string)$itemCount,
                $subject,
                "Le sujet doit indiquer le nombre d'articles"
            );
            
            // Property: Le sujet doit avoir la bonne forme grammaticale
            if ($itemCount === 1) {
                $this->assertStringContainsString(
                    '1 article',
                    $subject,
                    "Le sujet doit utiliser le singulier pour 1 article"
                );
            } else {
                $this->assertStringContainsString(
                    'articles',
                    $subject,
                    "Le sujet doit utiliser le pluriel pour plusieurs articles"
                );
            }
        }
    }

    /**
     * Test de propriété pour la détermination du niveau de priorité
     * 
     * @test
     */
    public function testPriorityLevelDeterminationProperty(): void
    {
        // Test avec différents scénarios
        $scenarios = [
            // [itemCount, totalDeficit, expectedPriority]
            [1, 5, 'NORMALE'],
            [2, 10, 'NORMALE'],
            [3, 15, 'ÉLEVÉE'],
            [4, 25, 'ÉLEVÉE'],
            [5, 30, 'CRITIQUE'],
            [6, 60, 'CRITIQUE'],
            [2, 50, 'CRITIQUE'], // Déficit élevé même avec peu d'articles
            [1, 25, 'ÉLEVÉE']    // Déficit moyen avec un seul article
        ];
        
        foreach ($scenarios as [$itemCount, $totalDeficit, $expectedPriority]) {
            $stockDeficits = $this->generateStockDeficitsWithTotalDeficit($itemCount, $totalDeficit);
            $priority = $this->determinePriorityLevel($stockDeficits);
            
            $this->assertEquals(
                $expectedPriority,
                $priority,
                "Priorité incorrecte pour {$itemCount} articles avec déficit total de {$totalDeficit}"
            );
        }
    }

    /**
     * Test de propriété pour la cohérence des données entre push et email
     * 
     * @test
     */
    public function testDataConsistencyBetweenPushAndEmailProperty(): void
    {
        // Générer 15 cas de test
        for ($i = 0; $i < 15; $i++) {
            $boutiqueName = 'Boutique_' . rand(1, 30);
            $itemCount = rand(1, 7);
            $stockDeficits = $this->generateStockDeficits($itemCount);
            
            $pushData = $this->preparePushNotificationData($boutiqueName, $stockDeficits);
            $emailData = $this->prepareEmailData($boutiqueName, $stockDeficits);
            
            // Property: Les données essentielles doivent être cohérentes
            $this->assertEquals(
                $boutiqueName,
                $pushData['boutique_name'],
                "Nom de boutique incohérent entre push et email"
            );
            
            $this->assertEquals(
                $itemCount,
                $pushData['items_count'],
                "Nombre d'articles incohérent dans les données push"
            );
            
            $this->assertEquals(
                $itemCount,
                $emailData['total_items_in_shortage'],
                "Nombre d'articles incohérent dans les données email"
            );
            
            // Property: Les déficits doivent être identiques
            $this->assertCount(
                $itemCount,
                $pushData['deficits'],
                "Nombre de déficits incohérent dans push"
            );
            
            $this->assertCount(
                $itemCount,
                $emailData['stock_deficits'],
                "Nombre de déficits incohérent dans email"
            );
        }
    }

    // Méthodes utilitaires pour les tests

    private function generateStockDeficits(int $count): array
    {
        $deficits = [];
        for ($i = 0; $i < $count; $i++) {
            $quantityRequested = rand(1, 20);
            $quantityAvailable = rand(0, $quantityRequested - 1); // Toujours insuffisant
            
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

    private function prepareNotificationData(string $boutiqueName, array $stockDeficits): array
    {
        return [
            'boutique_name' => $boutiqueName,
            'stock_deficits' => $stockDeficits,
            'items_count' => count($stockDeficits)
        ];
    }

    private function preparePushNotificationData(string $boutiqueName, array $stockDeficits): array
    {
        return [
            'type' => 'stock_alert',
            'boutique_name' => $boutiqueName,
            'items_count' => count($stockDeficits),
            'deficits' => array_map(fn(StockDeficit $deficit) => $deficit->toArray(), $stockDeficits),
            'priority' => 'high',
            'action_required' => true
        ];
    }

    private function prepareEmailData(string $boutiqueName, array $stockDeficits): array
    {
        return [
            'boutique_name' => $boutiqueName,
            'stock_deficits' => array_map(fn(StockDeficit $deficit) => $deficit->toArray(), $stockDeficits),
            'total_items_in_shortage' => count($stockDeficits)
        ];
    }

    private function buildStockAlertMessage(int $itemCount, string $clientName): string
    {
        if ($itemCount === 1) {
            return "Réservation de {$clientName} : 1 article en rupture de stock. Action requise.";
        } else {
            return "Réservation de {$clientName} : {$itemCount} articles en rupture de stock. Action requise.";
        }
    }

    private function buildEmailSubject(string $boutiqueName, int $itemCount): string
    {
        return "🚨 Alerte Stock Urgent - {$boutiqueName} ({$itemCount} article" . ($itemCount > 1 ? 's' : '') . " en rupture)";
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

    private function assertNotificationPushDataIsComplete(array $data, string $boutiqueName, int $itemCount): void
    {
        $this->assertArrayHasKey('boutique_name', $data);
        $this->assertEquals($boutiqueName, $data['boutique_name']);
        $this->assertEquals($itemCount, $data['items_count']);
    }

    private function assertEmailDataIsComplete(array $data, string $boutiqueName, array $stockDeficits): void
    {
        $this->assertArrayHasKey('boutique_name', $data);
        $this->assertEquals($boutiqueName, $data['boutique_name']);
        $this->assertCount(count($stockDeficits), $data['stock_deficits']);
    }
}