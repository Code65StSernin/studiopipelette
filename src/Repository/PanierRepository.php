<?php

namespace App\Repository;

use App\Entity\Panier;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Panier>
 */
class PanierRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Panier::class);
    }

    /**
     * Trouve le panier d'un utilisateur connecté
     */
    public function findByUser(User $user): ?Panier
    {
        return $this->createQueryBuilder('p')
            ->where('p.user = :user')
            ->setParameter('user', $user)
            ->orderBy('p.updatedAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouve le panier par sessionId pour les utilisateurs non connectés
     */
    public function findBySessionId(string $sessionId): ?Panier
    {
        return $this->createQueryBuilder('p')
            ->where('p.sessionId = :sessionId')
            ->setParameter('sessionId', $sessionId)
            ->orderBy('p.updatedAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Supprime les paniers abandonnés (plus de 30 jours sans mise à jour)
     */
    public function deleteOldCarts(): int
    {
        $date = new \DateTimeImmutable('-30 days');
        
        return $this->createQueryBuilder('p')
            ->delete()
            ->where('p.updatedAt < :date')
            ->andWhere('p.user IS NULL')
            ->setParameter('date', $date)
            ->getQuery()
            ->execute();
    }
}

