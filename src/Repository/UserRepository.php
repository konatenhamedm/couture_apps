<?php


// src/Repository/UserRepository.php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function add(User $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(User $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Trouve un utilisateur par son login (email ou téléphone)
     */
    public function findOneByLogin(string $login): ?User
    {
        return $this->createQueryBuilder('u')
            ->where('u.login = :login')
            ->setParameter('login', $login)
            ->getQuery()
            ->getOneOrNullResult();
    }
    public function getUserByCodeType($entreprise): ?User
    {
        return $this->createQueryBuilder('u')
            ->innerJoin('u.typeUser', 't')
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
        return $this->findBy(['isActive' => $isActive]);
    }

    /**
     * Met à jour le statut isActive
     */
    public function updateActiveStatus(User $user, bool $isActive): void
    {
        $user->setIsActive($isActive);
        $this->getEntityManager()->flush();
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
        $this->getEntityManager()->flush();
    }

    public function countActiveByEntreprise($entreprise): int
    {
        return $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.isActive = :active')
            ->andWhere('u.entreprise = :entreprise')
            ->setParameter('active', true)
            ->setParameter('entreprise', $entreprise)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
