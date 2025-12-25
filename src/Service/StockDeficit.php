<?php

namespace App\Service;

/**
 * Classe représentant un déficit de stock pour un article spécifique
 * 
 * Cette classe encapsule les informations sur les ruptures de stock lors de la création
 * de réservations, permettant de calculer et de suivre les déficits par article.
 */
class StockDeficit
{
    private string $modeleName;
    private int $quantityRequested;
    private int $quantityAvailable;
    private int $deficit;
    private string $boutiqueId;

    /**
     * @param string $modeleName Nom du modèle en rupture
     * @param int $quantityRequested Quantité demandée dans la réservation
     * @param int $quantityAvailable Quantité actuellement disponible en stock
     * @param string $boutiqueId ID de la boutique concernée
     */
    public function __construct(
        string $modeleName,
        int $quantityRequested,
        int $quantityAvailable,
        string $boutiqueId
    ) {
        $this->validateInputs($modeleName, $quantityRequested, $quantityAvailable, $boutiqueId);
        
        $this->modeleName = $modeleName;
        $this->quantityRequested = $quantityRequested;
        $this->quantityAvailable = $quantityAvailable;
        $this->boutiqueId = $boutiqueId;
        $this->deficit = $this->calculateDeficit();
    }

    /**
     * Calcule le déficit de stock
     */
    private function calculateDeficit(): int
    {
        return max(0, $this->quantityRequested - $this->quantityAvailable);
    }

    /**
     * Valide les données d'entrée
     */
    private function validateInputs(string $modeleName, int $quantityRequested, int $quantityAvailable, string $boutiqueId): void
    {
        if (empty($modeleName)) {
            throw new \InvalidArgumentException('Le nom du modèle ne peut pas être vide');
        }

        if ($quantityRequested < 0) {
            throw new \InvalidArgumentException('La quantité demandée ne peut pas être négative');
        }

        if ($quantityAvailable < 0) {
            throw new \InvalidArgumentException('La quantité disponible ne peut pas être négative');
        }

        if (empty($boutiqueId)) {
            throw new \InvalidArgumentException('L\'ID de la boutique ne peut pas être vide');
        }
    }

    /**
     * Vérifie s'il y a effectivement un déficit
     */
    public function hasDeficit(): bool
    {
        return $this->deficit > 0;
    }

    /**
     * Retourne le pourcentage de déficit par rapport à la demande
     */
    public function getDeficitPercentage(): float
    {
        if ($this->quantityRequested === 0) {
            return 0.0;
        }

        return ($this->deficit / $this->quantityRequested) * 100;
    }

    /**
     * Vérifie si le stock est complètement épuisé
     */
    public function isOutOfStock(): bool
    {
        return $this->quantityAvailable === 0;
    }

    /**
     * Retourne une représentation textuelle du déficit
     */
    public function getDeficitDescription(): string
    {
        if (!$this->hasDeficit()) {
            return "Stock suffisant pour {$this->modeleName}";
        }

        return "Déficit de {$this->deficit} unité(s) pour {$this->modeleName} " .
               "(demandé: {$this->quantityRequested}, disponible: {$this->quantityAvailable})";
    }

    /**
     * Convertit l'objet en tableau pour la sérialisation
     */
    public function toArray(): array
    {
        return [
            'modele_name' => $this->modeleName,
            'quantity_requested' => $this->quantityRequested,
            'quantity_available' => $this->quantityAvailable,
            'deficit' => $this->deficit,
            'boutique_id' => $this->boutiqueId,
            'has_deficit' => $this->hasDeficit(),
            'deficit_percentage' => $this->getDeficitPercentage(),
            'is_out_of_stock' => $this->isOutOfStock(),
            'description' => $this->getDeficitDescription()
        ];
    }

    // Getters
    public function getModeleName(): string
    {
        return $this->modeleName;
    }

    public function getQuantityRequested(): int
    {
        return $this->quantityRequested;
    }

    public function getQuantityAvailable(): int
    {
        return $this->quantityAvailable;
    }

    public function getDeficit(): int
    {
        return $this->deficit;
    }

    public function getBoutiqueId(): string
    {
        return $this->boutiqueId;
    }
}