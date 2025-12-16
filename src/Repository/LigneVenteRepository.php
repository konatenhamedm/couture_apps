<?php

namespace App\Repository;

use App\Entity\LigneVente;
use App\Service\EntityManagerProvider;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<LigneVente>
 */
class LigneVenteRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry, EntityManagerProvider $entityManagerProvider)
    {
        parent::__construct($registry, LigneVente::class, $entityManagerProvider);
    }
}