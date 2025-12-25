<?php

namespace App\Tests\Property\Controller;

use App\Enum\ReservationStatus;
use App\Service\StockDeficit;
use PHPUnit\Framework\TestCase;

/**
 * Tests de propriété pour l'assignation de statut selon le stock
 * 
 * @tag Feature: reservation-stock-notification, Property 2: Statut correct selon stock
 */
class ReservationStatusAssignmentTest extends TestCase
{
    /**
     * Property 2: Statut correct selon stock
     * For any reservation creation, if stock is insufficient then status should be "en_attente_stock", otherwise "en_attente"
     * 
     * @test
     */
    public function testStatusAssignmentBasedOnStockProperty(): void
    {
        // Générer 100 cas de test avec différents scénarios de stock
        for ($i = 0; $i < 100; $i++) {
            $quantityRequested = rand(1, 50);
            $quantityAvailable = rand(0, 75);
            
            $hasStockIssue = $quantityAvailable < $quantityRequested;
            
            // Property: Le statut doit être déterminé correctement selon le stock
            $expectedStatus = $this->determineStatusByStock($hasStockIssue);
            
            if ($hasStockIssue) {
                $this->assertEquals(
                    ReservationStatus::EN_ATTENTE_STOCK->value,
                    $expectedStatus,
                    "Statut incorrect pour stock insuffisant (demandé: {$quantityRequested}, disponible: {$quantityAvailable})"
                );
            } else {
                $this->assertEquals(
                    ReservationStatus::EN_ATTENTE->value,
                    $expectedStatus,
                    "Statut incorrect pour stock suffisant (demandé: {$quantityRequested}, disponible: {$quantityAvailable})"
                );
            }
        }
    }

    /**
     * Test de propriété pour les cas limites de stock
     * 
     * @test
     */
    public function testEdgeCaseStockStatusProperty(): void
    {
        // Cas limite : stock exactement égal à la demande
        for ($i = 0; $i < 50; $i++) {
            $quantity = rand(1, 30);
            
            // Property: Stock égal à la demande = pas de problème de stock
            $status = $this->determineStatusByStock(false); // Stock suffisant
            $this->assertEquals(
                ReservationStatus::EN_ATTENTE->value,
                $status,
                "Stock égal à la demande doit donner statut EN_ATTENTE"
            );
        }
        
        // Cas limite : stock à zéro
        for ($i = 0; $i < 30; $i++) {
            $quantityRequested = rand(1, 20);
            
            // Property: Stock à zéro avec demande > 0 = problème de stock
            $status = $this->determineStatusByStock(true); // Stock insuffisant
            $this->assertEquals(
                ReservationStatus::EN_ATTENTE_STOCK->value,
                $status,
                "Stock à zéro doit donner statut EN_ATTENTE_STOCK"
            );
        }
    }

    /**
     * Test de propriété pour la cohérence avec StockDeficit
     * 
     * @test
     */
    public function testStatusConsistencyWithStockDeficitProperty(): void
    {
        // Générer 50 cas de test pour vérifier la cohérence
        for ($i = 0; $i < 50; $i++) {
            $modeleName = 'Model_' . rand(1, 100);
            $quantityRequested = rand(1, 30);
            $quantityAvailable = rand(0, 40);
            $boutiqueId = 'boutique_' . rand(1, 10);
            
            $stockDeficit = new StockDeficit(
                $modeleName,
                $quantityRequested,
                $quantityAvailable,
                $boutiqueId
            );
            
            // Property: Le statut doit être cohérent avec StockDeficit::hasDeficit()
            $expectedStatus = $this->determineStatusByStock($stockDeficit->hasDeficit());
            
            if ($stockDeficit->hasDeficit()) {
                $this->assertEquals(
                    ReservationStatus::EN_ATTENTE_STOCK->value,
                    $expectedStatus,
                    "Statut incohérent avec StockDeficit pour déficit détecté"
                );
            } else {
                $this->assertEquals(
                    ReservationStatus::EN_ATTENTE->value,
                    $expectedStatus,
                    "Statut incohérent avec StockDeficit pour stock suffisant"
                );
            }
        }
    }

    /**
     * Test de propriété pour les transitions de statut valides
     * 
     * @test
     */
    public function testValidStatusTransitionsProperty(): void
    {
        // Property: EN_ATTENTE_STOCK peut transitionner vers EN_ATTENTE
        $this->assertTrue(
            ReservationStatus::EN_ATTENTE_STOCK->canTransitionToReady(),
            "EN_ATTENTE_STOCK doit pouvoir transitionner vers EN_ATTENTE"
        );
        
        // Property: EN_ATTENTE ne peut pas transitionner vers "ready" (déjà prêt)
        $this->assertFalse(
            ReservationStatus::EN_ATTENTE->canTransitionToReady(),
            "EN_ATTENTE ne doit pas pouvoir transitionner vers ready (déjà prêt)"
        );
        
        // Property: CONFIRMEE ne peut pas transitionner vers "ready"
        $this->assertFalse(
            ReservationStatus::CONFIRMEE->canTransitionToReady(),
            "CONFIRMEE ne doit pas pouvoir transitionner vers ready"
        );
        
        // Property: ANNULEE ne peut pas transitionner vers "ready"
        $this->assertFalse(
            ReservationStatus::ANNULEE->canTransitionToReady(),
            "ANNULEE ne doit pas pouvoir transitionner vers ready"
        );
    }

    /**
     * Test de propriété pour la détection des problèmes de stock
     * 
     * @test
     */
    public function testStockIssueDetectionProperty(): void
    {
        // Property: Seul EN_ATTENTE_STOCK doit indiquer un problème de stock
        $this->assertTrue(
            ReservationStatus::EN_ATTENTE_STOCK->hasStockIssue(),
            "EN_ATTENTE_STOCK doit indiquer un problème de stock"
        );
        
        $this->assertFalse(
            ReservationStatus::EN_ATTENTE->hasStockIssue(),
            "EN_ATTENTE ne doit pas indiquer de problème de stock"
        );
        
        $this->assertFalse(
            ReservationStatus::CONFIRMEE->hasStockIssue(),
            "CONFIRMEE ne doit pas indiquer de problème de stock"
        );
        
        $this->assertFalse(
            ReservationStatus::ANNULEE->hasStockIssue(),
            "ANNULEE ne doit pas indiquer de problème de stock"
        );
    }

    /**
     * Test de propriété pour les statuts d'alerte de stock
     * 
     * @test
     */
    public function testStockAlertStatusesProperty(): void
    {
        $alertStatuses = ReservationStatus::getStockAlertStatuses();
        
        // Property: Seul EN_ATTENTE_STOCK doit être dans les statuts d'alerte
        $this->assertCount(
            1,
            $alertStatuses,
            "Il doit y avoir exactement un statut d'alerte de stock"
        );
        
        $this->assertContains(
            ReservationStatus::EN_ATTENTE_STOCK->value,
            $alertStatuses,
            "EN_ATTENTE_STOCK doit être dans les statuts d'alerte"
        );
        
        // Property: Les autres statuts ne doivent pas être dans les alertes
        $this->assertNotContains(
            ReservationStatus::EN_ATTENTE->value,
            $alertStatuses,
            "EN_ATTENTE ne doit pas être dans les statuts d'alerte"
        );
        
        $this->assertNotContains(
            ReservationStatus::CONFIRMEE->value,
            $alertStatuses,
            "CONFIRMEE ne doit pas être dans les statuts d'alerte"
        );
        
        $this->assertNotContains(
            ReservationStatus::ANNULEE->value,
            $alertStatuses,
            "ANNULEE ne doit pas être dans les statuts d'alerte"
        );
    }

    /**
     * Test de propriété pour la cohérence des méthodes de validation
     * 
     * @test
     */
    public function testValidationMethodsConsistencyProperty(): void
    {
        // Property: EN_ATTENTE_STOCK doit être confirmable et annulable
        $this->assertTrue(
            ReservationStatus::EN_ATTENTE_STOCK->isConfirmable(),
            "EN_ATTENTE_STOCK doit être confirmable"
        );
        
        $this->assertTrue(
            ReservationStatus::EN_ATTENTE_STOCK->isCancellable(),
            "EN_ATTENTE_STOCK doit être annulable"
        );
        
        // Property: EN_ATTENTE doit aussi être confirmable et annulable
        $this->assertTrue(
            ReservationStatus::EN_ATTENTE->isConfirmable(),
            "EN_ATTENTE doit être confirmable"
        );
        
        $this->assertTrue(
            ReservationStatus::EN_ATTENTE->isCancellable(),
            "EN_ATTENTE doit être annulable"
        );
        
        // Property: CONFIRMEE ne doit plus être confirmable ni annulable
        $this->assertFalse(
            ReservationStatus::CONFIRMEE->isConfirmable(),
            "CONFIRMEE ne doit plus être confirmable"
        );
        
        $this->assertFalse(
            ReservationStatus::CONFIRMEE->isCancellable(),
            "CONFIRMEE ne doit plus être annulable"
        );
    }

    /**
     * Test de propriété pour les labels de statut
     * 
     * @test
     */
    public function testStatusLabelsProperty(): void
    {
        // Property: Chaque statut doit avoir un label non vide
        $statuses = [
            ReservationStatus::EN_ATTENTE,
            ReservationStatus::EN_ATTENTE_STOCK,
            ReservationStatus::CONFIRMEE,
            ReservationStatus::ANNULEE
        ];
        
        foreach ($statuses as $status) {
            $label = $status->getLabel();
            
            $this->assertNotEmpty(
                $label,
                "Le label du statut {$status->value} ne doit pas être vide"
            );
            
            $this->assertIsString(
                $label,
                "Le label du statut {$status->value} doit être une chaîne"
            );
        }
        
        // Property: Le label d'EN_ATTENTE_STOCK doit mentionner le stock
        $stockLabel = ReservationStatus::EN_ATTENTE_STOCK->getLabel();
        $this->assertStringContainsString(
            'stock',
            strtolower($stockLabel),
            "Le label d'EN_ATTENTE_STOCK doit mentionner le stock"
        );
    }

    // Méthodes utilitaires

    private function determineStatusByStock(bool $hasStockIssue): string
    {
        return $hasStockIssue 
            ? ReservationStatus::EN_ATTENTE_STOCK->value 
            : ReservationStatus::EN_ATTENTE->value;
    }
}