<?php

namespace App\Repository;

use App\Entity\ReservationStatusHistory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ReservationStatusHistory>
 */
class ReservationStatusHistoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ReservationStatusHistory::class);
    }

    /**
     * Récupère l'historique des changements de statut pour une réservation
     */
    public function findByReservation(int $reservationId): array
    {
        return $this->createQueryBuilder('h')
            ->andWhere('h.reservation = :reservationId')
            ->setParameter('reservationId', $reservationId)
            ->orderBy('h.changedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère le dernier changement de statut pour une réservation
     */
    public function findLastStatusChange(int $reservationId): ?ReservationStatusHistory
    {
        return $this->createQueryBuilder('h')
            ->andWhere('h.reservation = :reservationId')
            ->setParameter('reservationId', $reservationId)
            ->orderBy('h.changedAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}