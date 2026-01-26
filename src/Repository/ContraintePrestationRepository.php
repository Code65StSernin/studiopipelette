<?php

namespace App\Repository;

use App\Entity\ContraintePrestation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ContraintePrestation>
 */
class ContraintePrestationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ContraintePrestation::class);
    }

    /**
     * Retourne toutes les contraintes actives
     *
     * @return ContraintePrestation[]
     */
    public function findActive(): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.actif = :actif')
            ->setParameter('actif', true)
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne les contraintes pour un tarif donné
     *
     * @return ContraintePrestation[]
     */
    public function findByTarif($tarif): array
    {
        return $this->createQueryBuilder('c')
            ->innerJoin('c.tarifs', 't')
            ->where('t.id = :tarifId')
            ->andWhere('c.actif = :actif')
            ->setParameter('tarifId', $tarif->getId())
            ->setParameter('actif', true)
            ->getQuery()
            ->getResult();
    }
}

