<?php

namespace App\Enum;

/**
 * Énumération des statuts possibles pour une réservation
 * 
 * - EN_ATTENTE: Réservation créée avec stock suffisant, en attente de confirmation
 * - EN_ATTENTE_STOCK: Réservation créée avec stock insuffisant, nécessite ravitaillement
 * - CONFIRMEE: Réservation confirmée, stock déduit
 * - ANNULEE: Réservation annulée
 */
enum ReservationStatus: string
{
    case EN_ATTENTE = 'en_attente';
    case EN_ATTENTE_STOCK = 'en_attente_stock';
    case CONFIRMEE = 'confirmee';
    case ANNULEE = 'annulee';

    /**
     * Vérifie si une réservation peut être confirmée
     */
    public function isConfirmable(): bool
    {
        return $this === self::EN_ATTENTE || $this === self::EN_ATTENTE_STOCK;
    }

    /**
     * Vérifie si une réservation peut être annulée
     */
    public function isCancellable(): bool
    {
        return $this === self::EN_ATTENTE || $this === self::EN_ATTENTE_STOCK;
    }

    /**
     * Retourne tous les statuts possibles sous forme de tableau
     */
    public static function getValues(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }

    /**
     * Retourne le libellé français du statut
     */
    public function getLabel(): string
    {
        return match($this) {
            self::EN_ATTENTE => 'En attente',
            self::EN_ATTENTE_STOCK => 'En attente de stock',
            self::CONFIRMEE => 'Confirmée',
            self::ANNULEE => 'Annulée',
        };
    }

    /**
     * Vérifie si le statut indique un problème de stock
     */
    public function hasStockIssue(): bool
    {
        return $this === self::EN_ATTENTE_STOCK;
    }

    /**
     * Vérifie si une réservation peut être mise à jour vers "en_attente" après ravitaillement
     */
    public function canTransitionToReady(): bool
    {
        return $this === self::EN_ATTENTE_STOCK;
    }

    /**
     * Retourne les statuts qui nécessitent une notification d'alerte de stock
     */
    public static function getStockAlertStatuses(): array
    {
        return [self::EN_ATTENTE_STOCK->value];
    }
}