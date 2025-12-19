<?php

namespace App\Repository;

use App\Entity\Offre;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Offre>
 */
class OffreRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Offre::class);
    }

    /**
     * Retourne les offres actives pour une date donnée (par défaut maintenant),
     * triées par date de début croissante.
     *
     * @return Offre[]
     */
    public function findActive(\DateTimeImmutable $now = null): array
    {
        $now ??= new \DateTimeImmutable();

        return $this->createQueryBuilder('o')
            ->andWhere('o.dateDebut <= :now')
            ->andWhere('o.dateFin >= :now')
            ->setParameter('now', $now)
            ->orderBy('o.dateDebut', 'ASC')
            ->getQuery()
            ->getResult();
    }
}

