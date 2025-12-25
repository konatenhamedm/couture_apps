<?php

namespace App\Tests\Property\Service;

use App\Service\StockDeficit;
use PHPUnit\Framework\TestCase;

/**
 * Tests de propriété pour la classe StockDeficit
 * 
 * @tag Feature: reservation-stock-notification, Property 3: Calcul correct des déficits
 */
class StockDeficitTest extends TestCase
{
    /**
     * Property 3: Calcul correct des déficits
     * For any valid StockDeficit, the recorded deficit should equal (quantity_requested - quantity_available) for each item
     * 
     * @test
     */
    public function testDeficitCalculationProperty(): void
    {
        // Générer 100 cas de test aléatoires
        for ($i = 0; $i < 100; $i++) {
            $modeleName = 'Model_' . rand(1, 1000);
            $quantityRequested = rand(1, 100);
            $quantityAvailable = rand(0, 150); // Peut être supérieur à la demande
            $boutiqueId = 'boutique_' . rand(1, 50);
            
            $stockDeficit = new StockDeficit(
                $modeleName,
                $quantityRequested,
                $quantityAvailable,
                $boutiqueId
            );
            
            // Property: Le déficit doit toujours être max(0, demandé - disponible)
            $expectedDeficit = max(0, $quantityRequested - $quantityAvailable);
            $this->assertEquals(
                $expectedDeficit,
                $stockDeficit->getDeficit(),
                "Déficit incorrect pour demandé={$quantityRequested}, disponible={$quantityAvailable}"
            );
            
            // Property: hasDeficit() doit être cohérent avec le calcul
            $this->assertEquals(
                $expectedDeficit > 0,
                $stockDeficit->hasDeficit(),
                "hasDeficit() incohérent avec le déficit calculé"
            );
            
            // Property: isOutOfStock() doit être vrai seulement si quantité disponible = 0
            $this->assertEquals(
                $quantityAvailable === 0,
                $stockDeficit->isOutOfStock(),
                "isOutOfStock() incorrect pour quantité disponible={$quantityAvailable}"
            );
        }
    }

    /**
     * Test de propriété pour les pourcentages de déficit
     * 
     * @test
     */
    public function testDeficitPercentageProperty(): void
    {
        // Générer 100 cas de test aléatoires
        for ($i = 0; $i < 100; $i++) {
            $modeleName = 'Model_' . rand(1, 1000);
            $quantityRequested = rand(1, 100); // Toujours > 0 pour éviter division par zéro
            $quantityAvailable = rand(0, 150);
            $boutiqueId = 'boutique_' . rand(1, 50);
            
            $stockDeficit = new StockDeficit(
                $modeleName,
                $quantityRequested,
                $quantityAvailable,
                $boutiqueId
            );
            
            $expectedPercentage = ($stockDeficit->getDeficit() / $quantityRequested) * 100;
            
            // Property: Le pourcentage doit être calculé correctement
            $this->assertEquals(
                $expectedPercentage,
                $stockDeficit->getDeficitPercentage(),
                "Pourcentage de déficit incorrect",
                0.01 // Tolérance pour les calculs flottants
            );
            
            // Property: Le pourcentage doit être entre 0 et 100
            $this->assertGreaterThanOrEqual(
                0,
                $stockDeficit->getDeficitPercentage(),
                "Le pourcentage ne peut pas être négatif"
            );
            
            $this->assertLessThanOrEqual(
                100,
                $stockDeficit->getDeficitPercentage(),
                "Le pourcentage ne peut pas dépasser 100%"
            );
        }
    }

    /**
     * Test de propriété pour la validation des entrées
     * 
     * @test
     */
    public function testInputValidationProperty(): void
    {
        // Property: Les entrées invalides doivent toujours lever une exception
        
        // Test avec nom de modèle vide
        $this->expectException(\InvalidArgumentException::class);
        new StockDeficit('', 10, 5, 'boutique_1');
    }

    /**
     * Test de propriété pour la validation des quantités négatives
     * 
     * @test
     */
    public function testNegativeQuantityValidationProperty(): void
    {
        // Property: Les quantités négatives doivent être rejetées
        
        // Test avec quantité demandée négative
        $this->expectException(\InvalidArgumentException::class);
        new StockDeficit('Model_1', -1, 5, 'boutique_1');
    }

    /**
     * Test de propriété pour la validation des quantités disponibles négatives
     * 
     * @test
     */
    public function testNegativeAvailableQuantityValidationProperty(): void
    {
        // Property: Les quantités disponibles négatives doivent être rejetées
        
        // Test avec quantité disponible négative
        $this->expectException(\InvalidArgumentException::class);
        new StockDeficit('Model_1', 10, -1, 'boutique_1');
    }

    /**
     * Test de propriété pour la validation de l'ID boutique vide
     * 
     * @test
     */
    public function testEmptyBoutiqueIdValidationProperty(): void
    {
        // Property: L'ID boutique vide doit être rejeté
        
        $this->expectException(\InvalidArgumentException::class);
        new StockDeficit('Model_1', 10, 5, '');
    }

    /**
     * Test de propriété pour la sérialisation en tableau
     * 
     * @test
     */
    public function testArraySerializationProperty(): void
    {
        // Générer 50 cas de test aléatoires
        for ($i = 0; $i < 50; $i++) {
            $modeleName = 'Model_' . rand(1, 1000);
            $quantityRequested = rand(1, 100);
            $quantityAvailable = rand(0, 150);
            $boutiqueId = 'boutique_' . rand(1, 50);
            
            $stockDeficit = new StockDeficit(
                $modeleName,
                $quantityRequested,
                $quantityAvailable,
                $boutiqueId
            );
            
            $array = $stockDeficit->toArray();
            
            // Property: Le tableau doit contenir toutes les clés requises
            $requiredKeys = [
                'modele_name', 'quantity_requested', 'quantity_available',
                'deficit', 'boutique_id', 'has_deficit', 'deficit_percentage',
                'is_out_of_stock', 'description'
            ];
            
            foreach ($requiredKeys as $key) {
                $this->assertArrayHasKey(
                    $key,
                    $array,
                    "Clé manquante dans la sérialisation: {$key}"
                );
            }
            
            // Property: Les valeurs dans le tableau doivent correspondre aux getters
            $this->assertEquals($modeleName, $array['modele_name']);
            $this->assertEquals($quantityRequested, $array['quantity_requested']);
            $this->assertEquals($quantityAvailable, $array['quantity_available']);
            $this->assertEquals($stockDeficit->getDeficit(), $array['deficit']);
            $this->assertEquals($boutiqueId, $array['boutique_id']);
            $this->assertEquals($stockDeficit->hasDeficit(), $array['has_deficit']);
            $this->assertEquals($stockDeficit->getDeficitPercentage(), $array['deficit_percentage']);
            $this->assertEquals($stockDeficit->isOutOfStock(), $array['is_out_of_stock']);
            $this->assertEquals($stockDeficit->getDeficitDescription(), $array['description']);
        }
    }

    /**
     * Test de propriété pour la cohérence des descriptions
     * 
     * @test
     */
    public function testDescriptionConsistencyProperty(): void
    {
        // Générer 50 cas de test aléatoires
        for ($i = 0; $i < 50; $i++) {
            $modeleName = 'Model_' . rand(1, 1000);
            $quantityRequested = rand(1, 100);
            $quantityAvailable = rand(0, 150);
            $boutiqueId = 'boutique_' . rand(1, 50);
            
            $stockDeficit = new StockDeficit(
                $modeleName,
                $quantityRequested,
                $quantityAvailable,
                $boutiqueId
            );
            
            $description = $stockDeficit->getDeficitDescription();
            
            // Property: La description doit contenir le nom du modèle
            $this->assertStringContainsString(
                $modeleName,
                $description,
                "La description doit contenir le nom du modèle"
            );
            
            // Property: Si pas de déficit, la description doit indiquer "Stock suffisant"
            if (!$stockDeficit->hasDeficit()) {
                $this->assertStringContainsString(
                    'Stock suffisant',
                    $description,
                    "La description doit indiquer 'Stock suffisant' quand il n'y a pas de déficit"
                );
            } else {
                // Property: Si déficit, la description doit contenir "Déficit"
                $this->assertStringContainsString(
                    'Déficit',
                    $description,
                    "La description doit contenir 'Déficit' quand il y a un déficit"
                );
                
                // Property: La description doit contenir les quantités
                $this->assertStringContainsString(
                    (string)$quantityRequested,
                    $description,
                    "La description doit contenir la quantité demandée"
                );
                
                $this->assertStringContainsString(
                    (string)$quantityAvailable,
                    $description,
                    "La description doit contenir la quantité disponible"
                );
            }
        }
    }
}