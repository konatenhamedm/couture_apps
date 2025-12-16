<?php


// src/Repository/UserRepository.php

namespace App\Repository;

use App\Entity\User;
use App\Service\EntityManagerProvider;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

class UserRepository extends BaseRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry, EntityManagerProvider $entityManagerProvider)
    {
        parent::__construct($registry, User::class, $entityManagerProvider);
    }

    public function add(User $entity, bool $flush = false): void
    {
        $this->saveInEnvironment($entity, $flush);
    }

    public function remove(User $entity, bool $flush = false): void
    {
        $this->removeInEnvironment($entity, $flush);
    }

    /**
     * Trouve un utilisateur par son login (email ou téléphone)
     */
    public function findOneByLogin(string $login): ?User
    {
        return $this->createQueryBuilderForEnvironment('u')
            ->where('u.login = :login')
            ->setParameter('login', $login)
            ->getQuery()
            ->getOneOrNullResult();
    }
    public function getUserByCodeType($entreprise): ?User
    {
        return $this->createQueryBuilderForEnvironment('u')
            ->innerJoin('u.type','t')
            ->where('t.code = :code')
            ->andWhere('u.entreprise = :entreprise')
            ->setParameter('code', "SADM")
            ->setParameter('entreprise', $entreprise)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouve les utilisateurs actifs/inactifs
     */
    public function findByActiveStatus(bool $isActive): array
    {
        return $this->findByInEnvironment(['isActive' => $isActive]);
    }

    /**
     * Met à jour le statut isActive
     */
    public function updateActiveStatus(User $user, bool $isActive): void
    {
        $user->setIsActive($isActive);
        $this->getEntityManagerForEnvironment()->flush();
    }

    /**
     * Utilisé pour la mise à jour du mot de passe (PasswordUpgraderInterface)
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_class($user)));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManagerForEnvironment()->flush();
    }

    public function countActiveByEntreprise($entreprise): int
    {
        return $this->createQueryBuilderForEnvironment('u')
            ->select('COUNT(u.id)')
            ->where('u.isActive = :active')
            ->andWhere('u.entreprise = :entreprise')
            ->setParameter('active', true)
            ->setParameter('entreprise', $entreprise)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
