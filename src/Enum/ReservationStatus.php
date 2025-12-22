<?php

namespace App\Enum;

/**
 * Énumération des statuts possibles pour une réservation
 */
enum ReservationStatus: string
{
    case EN_ATTENTE = 'en_attente';
    case CONFIRMEE = 'confirmee';
    case ANNULEE = 'annulee';

    /**
     * Vérifie si une réservation peut être confirmée
     */
    public function isConfirmable(): bool
    {
        return $this === self::EN_ATTENTE;
    }

    /**
     * Vérifie si une réservation peut être annulée
     */
    public function isCancellable(): bool
    {
        return $this === self::EN_ATTENTE;
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
            self::CONFIRMEE => 'Confirmée',
            self::ANNULEE => 'Annulée',
        };
    }
}