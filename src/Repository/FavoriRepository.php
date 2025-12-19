<?php

namespace App\Repository;

use App\Entity\Favori;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Favori>
 */
class FavoriRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Favori::class);
    }

    /**
     * Trouve les favoris d'un utilisateur connecté
     */
    public function findByUser(User $user): ?Favori
    {
        return $this->createQueryBuilder('f')
            ->where('f.user = :user')
            ->setParameter('user', $user)
            ->orderBy('f.updatedAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouve les favoris par sessionId pour les utilisateurs non connectés
     */
    public function findBySessionId(string $sessionId): ?Favori
    {
        return $this->createQueryBuilder('f')
            ->where('f.sessionId = :sessionId')
            ->setParameter('sessionId', $sessionId)
            ->orderBy('f.updatedAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Supprime les favoris abandonnés (plus de 90 jours sans mise à jour)
     */
    public function deleteOldFavoris(): int
    {
        $date = new \DateTimeImmutable('-90 days');
        
        return $this->createQueryBuilder('f')
            ->delete()
            ->where('f.updatedAt < :date')
            ->andWhere('f.user IS NULL')
            ->setParameter('date', $date)
            ->getQuery()
            ->execute();
    }
}

