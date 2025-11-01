<?php

namespace App\Service;

use App\Repository\FactureRepository;
use App\Repository\ReservationRepository;
use App\Repository\ClientRepository;
use App\Repository\PaiementRepository;
use App\Repository\PaiementFactureRepository;
use App\Repository\PaiementReservationRepository;
use DateTime;

class StatistiquesService
{
    public function __construct(
        private FactureRepository $factureRepo,
        private ReservationRepository $reservationRepo,
        private ClientRepository $clientRepo,
        private PaiementRepository $paiementRepo
    ) {}

    /**
     * Retourne les statistiques principales du dashboard
     */
    public function getDashboardStats(\DateTime $dateDebut, \DateTime $dateFin): array
    {
        $datesPrecedentes = $this->getPeriodePrecedente($dateDebut, $dateFin);
        
        return [
            'commandesTotales' => $this->getCommandesTotales($dateDebut, $dateFin, $datesPrecedentes),
            'revenus' => $this->getRevenus($dateDebut, $dateFin, $datesPrecedentes),
            'nouveauxClients' => $this->getNouveauxClients($dateDebut, $dateFin, $datesPrecedentes),
            'tauxReservation' => $this->getTauxReservation($dateDebut, $dateFin, $datesPrecedentes),
            'periode' => [
                'debut' => $dateDebut->format('Y-m-d'),
                'fin' => $dateFin->format('Y-m-d')
            ]
        ];
    }

    /**
     * Évolution du chiffre d'affaires pour graphique
     */
    public function getEvolutionRevenus(\DateTime $dateDebut, \DateTime $dateFin, string $groupBy = 'jour'): array
    {
        $data = $this->paiementRepo->getEvolutionRevenus($dateDebut, $dateFin, $groupBy);
        
        // Formatage pour graphique
        $labels = [];
        $values = [];
        
        foreach ($data as $item) {
            $labels[] = $this->formatDateLabel($item['periode'], $groupBy);
            $values[] = (float) $item['montant'];
        }
        
        return [
            'labels' => $labels,
            'data' => $values,
            'total' => array_sum($values),
            'moyenne' => count($values) > 0 ? array_sum($values) / count($values) : 0
        ];
    }

    /**
     * Évolution des commandes pour graphique
     */
    public function getEvolutionCommandes(\DateTime $dateDebut, \DateTime $dateFin, string $groupBy = 'jour'): array
    {
        $factures = $this->factureRepo->getEvolutionCommandes($dateDebut, $dateFin, $groupBy);
        $reservations = $this->reservationRepo->getEvolutionCommandes($dateDebut, $dateFin, $groupBy);
        
        // Fusion des données
        $merged = [];
        foreach ($factures as $item) {
            $merged[$item['periode']] = (int) $item['nombre'];
        }
        
        foreach ($reservations as $item) {
            if (isset($merged[$item['periode']])) {
                $merged[$item['periode']] += (int) $item['nombre'];
            } else {
                $merged[$item['periode']] = (int) $item['nombre'];
            }
        }
        
        ksort($merged);
        
        $labels = [];
        $values = [];
        
        foreach ($merged as $periode => $nombre) {
            $labels[] = $this->formatDateLabel($periode, $groupBy);
            $values[] = $nombre;
        }
        
        return [
            'labels' => $labels,
            'data' => $values,
            'total' => array_sum($values),
            'moyenne' => count($values) > 0 ? array_sum($values) / count($values) : 0
        ];
    }

    /**
     * Répartition des types de paiements
     */
    public function getRevenusParType(\DateTime $dateDebut, \DateTime $dateFin): array
    {
        $data = $this->paiementRepo->getRevenusParType($dateDebut, $dateFin);
        
        $labels = [];
        $values = [];
        $colors = [
            'paiementFacture' => '#3B82F6',
            'paiementReservation' => '#10B981',
            'paiementBoutique' => '#F59E0B',
            'paiementAbonnement' => '#8B5CF6',
            'paiementSuccursale' => '#EC4899'
        ];
        
        foreach ($data as $item) {
            $labels[] = $this->formatTypeLabel($item['type']);
            $values[] = (float) $item['montant'];
        }
        
        return [
            'labels' => $labels,
            'data' => $values,
            'colors' => array_values(array_slice($colors, 0, count($labels))),
            'total' => array_sum($values)
        ];
    }

    /**
     * Top clients
     */
    public function getTopClients(\DateTime $dateDebut, \DateTime $dateFin, int $limit = 10): array
    {
        return $this->paiementRepo->getTopClients($dateDebut, $dateFin, $limit);
    }

    /**
     * Statistiques comparatives (vs période précédente)
     */
    public function getComparatif(\DateTime $dateDebut, \DateTime $dateFin): array
    {
        $datesPrecedentes = $this->getPeriodePrecedente($dateDebut, $dateFin);
        
        $revenusActuels = $this->getRevenusTotal($dateDebut, $dateFin);
        $revenusPrecedents = $this->getRevenusTotal($datesPrecedentes['debut'], $datesPrecedentes['fin']);
        
        $commandesActuelles = $this->getCommandesTotal($dateDebut, $dateFin);
        $commandesPrecedentes = $this->getCommandesTotal($datesPrecedentes['debut'], $datesPrecedentes['fin']);
        
        return [
            'revenus' => [
                'actuel' => $revenusActuels,
                'precedent' => $revenusPrecedents,
                'variation' => $this->calculateVariation($revenusActuels, $revenusPrecedents),
                'variationPourcent' => $this->calculateVariationPercent($revenusActuels, $revenusPrecedents)
            ],
            'commandes' => [
                'actuel' => $commandesActuelles,
                'precedent' => $commandesPrecedentes,
                'variation' => $commandesActuelles - $commandesPrecedentes,
                'variationPourcent' => $this->calculateVariationPercent($commandesActuelles, $commandesPrecedentes)
            ]
        ];
    }

    // Méthodes privées helpers

    private function getCommandesTotales(\DateTime $debut, \DateTime $fin, array $precedent): array
    {
        $actuel = $this->getCommandesTotal($debut, $fin);
        $precedentTotal = $this->getCommandesTotal($precedent['debut'], $precedent['fin']);
        
        return [
            'valeur' => $actuel,
            'variation' => $this->calculateVariationPercent($actuel, $precedentTotal)
        ];
    }

    private function getCommandesTotal(\DateTime $debut, \DateTime $fin): int
    {
        $factures = $this->factureRepo->countByDateRange($debut, $fin);
        $reservations = $this->reservationRepo->countByDateRange($debut, $fin);
        return $factures + $reservations;
    }

    private function getRevenus(\DateTime $debut, \DateTime $fin, array $precedent): array
    {
        $actuel = $this->getRevenusTotal($debut, $fin);
        $precedentTotal = $this->getRevenusTotal($precedent['debut'], $precedent['fin']);
        
        return [
            'valeur' => $actuel,
            'valeurFormatee' => $this->formatMontant($actuel),
            'variation' => $this->calculateVariationPercent($actuel, $precedentTotal)
        ];
    }

    private function getRevenusTotal(\DateTime $debut, \DateTime $fin): float
    {
        return $this->paiementRepo->sumMontantByDateRange($debut, $fin);
    }

    private function getNouveauxClients(\DateTime $debut, \DateTime $fin, array $precedent): array
    {
        $actuel = $this->clientRepo->countNewClients($debut, $fin);
        $precedentTotal = $this->clientRepo->countNewClients($precedent['debut'], $precedent['fin']);
        
        return [
            'valeur' => $actuel,
            'variation' => $this->calculateVariationPercent($actuel, $precedentTotal)
        ];
    }

    private function getTauxReservation(\DateTime $debut, \DateTime $fin, array $precedent): array
    {
        $totalCommandes = $this->getCommandesTotal($debut, $fin);
        $totalReservations = $this->reservationRepo->countByDateRange($debut, $fin);
        
        $tauxActuel = $totalCommandes > 0 ? ($totalReservations / $totalCommandes) * 100 : 0;
        
        $totalCommandesPrecedent = $this->getCommandesTotal($precedent['debut'], $precedent['fin']);
        $totalReservationsPrecedent = $this->reservationRepo->countByDateRange($precedent['debut'], $precedent['fin']);
        
        $tauxPrecedent = $totalCommandesPrecedent > 0 ? ($totalReservationsPrecedent / $totalCommandesPrecedent) * 100 : 0;
        
        return [
            'valeur' => round($tauxActuel, 1),
            'variation' => round($tauxActuel - $tauxPrecedent, 1)
        ];
    }

    private function getPeriodePrecedente(\DateTime $debut, \DateTime $fin): array
    {
        $diff = $debut->diff($fin)->days;
        
        $debutPrecedent = clone $debut;
        $debutPrecedent->modify("-{$diff} days");
        
        $finPrecedent = clone $fin;
        $finPrecedent->modify("-{$diff} days");
        
        return [
            'debut' => $debutPrecedent,
            'fin' => $finPrecedent
        ];
    }

    private function calculateVariation(float $actuel, float $precedent): float
    {
        return $actuel - $precedent;
    }

    private function calculateVariationPercent(float $actuel, float $precedent): float
    {
        if ($precedent == 0) {
            return $actuel > 0 ? 100 : 0;
        }
        
        return round((($actuel - $precedent) / $precedent) * 100, 1);
    }

    private function formatMontant(float $montant): string
    {
        if ($montant >= 1000000) {
            return round($montant / 1000000, 1) . 'M';
        } elseif ($montant >= 1000) {
            return round($montant / 1000, 1) . 'K';
        }
        return (string) $montant;
    }

    private function formatDateLabel(string $date, string $groupBy): string
    {
        $dateTime = new \DateTime($date);
        
        switch ($groupBy) {
            case 'jour':
                return $dateTime->format('d/m');
            case 'semaine':
                return 'S' . $dateTime->format('W');
            case 'mois':
                return $dateTime->format('M Y');
            default:
                return $date;
        }
    }

    private function formatTypeLabel(string $type): string
    {
        $labels = [
            'paiementFacture' => 'Factures',
            'paiementReservation' => 'Réservations',
            'paiementBoutique' => 'Boutique',
            'paiementAbonnement' => 'Abonnements',
            'paiementSuccursale' => 'Succursale'
        ];
        
        return $labels[$type] ?? $type;
    }
}