<?php

namespace App\Service;

use App\Entity\Reservation;
use App\Entity\ReservationStatusHistory;
use App\Entity\User;
use App\Enum\ReservationStatus;
use App\Repository\ReservationRepository;
use App\Repository\ReservationStatusHistoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service de gestion du workflow des réservations
 * Gère les transitions d'état (confirmation, annulation) avec audit trail
 */
class ReservationWorkflowService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ReservationRepository $reservationRepository,
        private ReservationStatusHistoryRepository $statusHistoryRepository,
        private LoggerInterface $logger
    ) {}

    /**
     * Confirme une réservation et déduit le stock
     * 
     * @param int $reservationId ID de la réservation à confirmer
     * @param User $user Utilisateur effectuant la confirmation
     * @param string|null $notes Notes optionnelles
     * @return array Résultat de l'opération
     * @throws \Exception En cas d'erreur
     */
    public function confirmReservation(int $reservationId, User $user, ?string $notes = null): array
    {
        $this->logger->info('Début de confirmation de réservation', [
            'reservation_id' => $reservationId,
            'user_id' => $user->getId(),
            'notes' => $notes
        ]);

        // Récupérer la réservation
        $reservation = $this->reservationRepository->find($reservationId);
        if (!$reservation) {
            throw new \InvalidArgumentException("Réservation avec ID {$reservationId} non trouvée");
        }

        // Vérifier que la réservation peut être confirmée
        if (!$reservation->isConfirmable()) {
            throw new \InvalidArgumentException(
                "La réservation ne peut pas être confirmée. Statut actuel: {$reservation->getStatus()}"
            );
        }

        // Commencer une transaction
        $this->entityManager->beginTransaction();

        try {
            // Vérifier et déduire le stock
            $stockValidation = $this->validateAndDeductStock($reservation);
            if (!$stockValidation['success']) {
                throw new \RuntimeException($stockValidation['message']);
            }

            // Mettre à jour le statut de la réservation
            $oldStatus = $reservation->getStatus();
            $reservation->setStatus(ReservationStatus::CONFIRMEE->value);
            $reservation->setConfirmedAt(new \DateTime());
            $reservation->setConfirmedBy($user);
            $reservation->setUpdatedAt(new \DateTime());
            $reservation->setUpdatedBy($user);

            // Créer l'entrée d'audit trail
            $this->createStatusHistory(
                $reservation,
                $oldStatus,
                ReservationStatus::CONFIRMEE->value,
                $user,
                $notes ?? 'Confirmation de la réservation avec déduction du stock'
            );

            // Persister les changements
            $this->entityManager->persist($reservation);
            $this->entityManager->flush();
            $this->entityManager->commit();

            $this->logger->info('Réservation confirmée avec succès', [
                'reservation_id' => $reservationId,
                'user_id' => $user->getId(),
                'stock_deductions' => $stockValidation['deductions']
            ]);

            return [
                'success' => true,
                'message' => 'Réservation confirmée avec succès',
                'reservation' => $reservation,
                'stock_deductions' => $stockValidation['deductions']
            ];

        } catch (\Exception $e) {
            $this->entityManager->rollback();
            
            $this->logger->error('Erreur lors de la confirmation de réservation', [
                'reservation_id' => $reservationId,
                'user_id' => $user->getId(),
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Annule une réservation
     * 
     * @param int $reservationId ID de la réservation à annuler
     * @param User $user Utilisateur effectuant l'annulation
     * @param string|null $reason Raison de l'annulation
     * @return array Résultat de l'opération
     * @throws \Exception En cas d'erreur
     */
    public function cancelReservation(int $reservationId, User $user, ?string $reason = null): array
    {
        $this->logger->info('Début d\'annulation de réservation', [
            'reservation_id' => $reservationId,
            'user_id' => $user->getId(),
            'reason' => $reason
        ]);

        // Récupérer la réservation
        $reservation = $this->reservationRepository->find($reservationId);
        if (!$reservation) {
            throw new \InvalidArgumentException("Réservation avec ID {$reservationId} non trouvée");
        }

        // Vérifier que la réservation peut être annulée
        if (!$reservation->isCancellable()) {
            throw new \InvalidArgumentException(
                "La réservation ne peut pas être annulée. Statut actuel: {$reservation->getStatus()}"
            );
        }

        // Commencer une transaction
        $this->entityManager->beginTransaction();

        try {
            // Mettre à jour le statut de la réservation
            $oldStatus = $reservation->getStatus();
            $reservation->setStatus(ReservationStatus::ANNULEE->value);
            $reservation->setCancelledAt(new \DateTime());
            $reservation->setCancelledBy($user);
            $reservation->setCancellationReason($reason);
            $reservation->setUpdatedAt(new \DateTime());
            $reservation->setUpdatedBy($user);

            // Créer l'entrée d'audit trail
            $this->createStatusHistory(
                $reservation,
                $oldStatus,
                ReservationStatus::ANNULEE->value,
                $user,
                $reason ?? 'Annulation de la réservation'
            );

            // Persister les changements
            $this->entityManager->persist($reservation);
            $this->entityManager->flush();
            $this->entityManager->commit();

            $this->logger->info('Réservation annulée avec succès', [
                'reservation_id' => $reservationId,
                'user_id' => $user->getId(),
                'reason' => $reason
            ]);

            return [
                'success' => true,
                'message' => 'Réservation annulée avec succès',
                'reservation' => $reservation,
                'reason' => $reason
            ];

        } catch (\Exception $e) {
            $this->entityManager->rollback();
            
            $this->logger->error('Erreur lors de l\'annulation de réservation', [
                'reservation_id' => $reservationId,
                'user_id' => $user->getId(),
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Valide la disponibilité du stock et effectue la déduction
     * 
     * @param Reservation $reservation
     * @return array Résultat de la validation et déduction
     */
    private function validateAndDeductStock(Reservation $reservation): array
    {
        $deductions = [];
        $insufficientItems = [];

        // Vérifier la disponibilité pour tous les articles
        foreach ($reservation->getLigneReservations() as $ligne) {
            $modeleBoutique = $ligne->getModele();
            $modele = $modeleBoutique->getModele();
            $quantiteDemandee = $ligne->getQuantite();

            // Vérifier stock boutique
            if ($modeleBoutique->getQuantite() < $quantiteDemandee) {
                $insufficientItems[] = [
                    'modele_id' => $modele->getId(),
                    'modele_name' => $modele->getLibelle(),
                    'requested' => $quantiteDemandee,
                    'available_boutique' => $modeleBoutique->getQuantite(),
                    'type' => 'boutique'
                ];
                continue;
            }

            // Vérifier stock global
            if ($modele->getQuantiteGlobale() < $quantiteDemandee) {
                $insufficientItems[] = [
                    'modele_id' => $modele->getId(),
                    'modele_name' => $modele->getLibelle(),
                    'requested' => $quantiteDemandee,
                    'available_global' => $modele->getQuantiteGlobale(),
                    'type' => 'global'
                ];
                continue;
            }

            // Préparer la déduction
            $deductions[] = [
                'modele_boutique' => $modeleBoutique,
                'modele' => $modele,
                'quantite' => $quantiteDemandee,
                'old_stock_boutique' => $modeleBoutique->getQuantite(),
                'old_stock_global' => $modele->getQuantiteGlobale()
            ];
        }

        // Si des articles ont un stock insuffisant, retourner l'erreur
        if (!empty($insufficientItems)) {
            return [
                'success' => false,
                'message' => 'Stock insuffisant pour certains articles',
                'insufficient_items' => $insufficientItems
            ];
        }

        // Effectuer les déductions
        foreach ($deductions as &$deduction) {
            $modeleBoutique = $deduction['modele_boutique'];
            $modele = $deduction['modele'];
            $quantite = $deduction['quantite'];

            // Déduire du stock boutique
            $modeleBoutique->setQuantite($modeleBoutique->getQuantite() - $quantite);
            
            // Déduire du stock global
            $modele->setQuantiteGlobale($modele->getQuantiteGlobale() - $quantite);

            // Enregistrer les nouveaux stocks
            $deduction['new_stock_boutique'] = $modeleBoutique->getQuantite();
            $deduction['new_stock_global'] = $modele->getQuantiteGlobale();

            $this->entityManager->persist($modeleBoutique);
            $this->entityManager->persist($modele);
        }

        return [
            'success' => true,
            'message' => 'Stock déduit avec succès',
            'deductions' => $deductions
        ];
    }

    /**
     * Crée une entrée dans l'historique des statuts
     * 
     * @param Reservation $reservation
     * @param string $oldStatus
     * @param string $newStatus
     * @param User $user
     * @param string|null $reason
     */
    private function createStatusHistory(
        Reservation $reservation,
        string $oldStatus,
        string $newStatus,
        User $user,
        ?string $reason = null
    ): void {
        $history = new ReservationStatusHistory();
        $history->setReservation($reservation);
        $history->setOldStatus($oldStatus);
        $history->setNewStatus($newStatus);
        $history->setChangedBy($user);
        $history->setReason($reason);
        $history->setChangedAt(new \DateTime());
        $history->setCreatedAtValue(new \DateTime());
        $history->setUpdatedAt(new \DateTime());
        $history->setCreatedBy($user);
        $history->setUpdatedBy($user);
        $history->setIsActive(true);

        $this->entityManager->persist($history);
        $reservation->addStatusHistory($history);
    }

    /**
     * Récupère l'historique des changements de statut d'une réservation
     * 
     * @param int $reservationId
     * @return array
     */
    public function getStatusHistory(int $reservationId): array
    {
        return $this->statusHistoryRepository->findByReservation($reservationId);
    }

    /**
     * Valide les transitions d'état possibles
     * 
     * @param string $currentStatus
     * @param string $targetStatus
     * @return bool
     */
    public function isValidTransition(string $currentStatus, string $targetStatus): bool
    {
        $validTransitions = [
            ReservationStatus::EN_ATTENTE->value => [
                ReservationStatus::CONFIRMEE->value,
                ReservationStatus::ANNULEE->value
            ],
            ReservationStatus::CONFIRMEE->value => [
                // Une réservation confirmée ne peut plus changer d'état
            ],
            ReservationStatus::ANNULEE->value => [
                // Une réservation annulée ne peut plus changer d'état
            ]
        ];

        return in_array($targetStatus, $validTransitions[$currentStatus] ?? []);
    }
}