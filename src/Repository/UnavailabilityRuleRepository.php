<?php

namespace App\Repository;

use App\Entity\UnavailabilityRule;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UnavailabilityRule>
 */
class UnavailabilityRuleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UnavailabilityRule::class);
    }

    /**
     * Récupère toutes les règles d'indisponibilité actives pour une date donnée.
     *
     * @param \DateTimeInterface $date
     * @return UnavailabilityRule[]
     */
    public function findActiveRules(): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.active = :isActive')
            ->setParameter('isActive', true)
            ->getQuery()
            ->getResult();
    }
}
