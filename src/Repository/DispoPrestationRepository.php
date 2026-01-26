<?php

namespace App\Repository;

use App\Entity\DispoPrestation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DispoPrestation>
 *
 * @method DispoPrestation|null find($id, $lockMode = null, $lockVersion = null)
 * @method DispoPrestation|null findOneBy(array $criteria, array $orderBy = null)
 * @method DispoPrestation[]    findAll()
 * @method DispoPrestation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DispoPrestationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DispoPrestation::class);
    }

    /**
     * Trouve les suspensions de disponibilité pour une date donnée et une liste de tarifs.
     * Une suspension est une période où le tarif (prestation) n'est PAS disponible.
     *
     * @param \DateTimeInterface $date
     * @param array $tarifIds
     * @return DispoPrestation[]
     */
    public function findSuspensionsPourDate(\DateTimeInterface $date, array $tarifIds): array
    {
        if (empty($tarifIds)) {
            return [];
        }

        return $this->createQueryBuilder('d')
            ->innerJoin('d.Tarif', 't')
            ->andWhere('t.id IN (:tarifIds)')
            ->andWhere('d.dateDebut <= :date')
            ->andWhere('d.dateFin >= :date')
            ->setParameter('tarifIds', $tarifIds)
            ->setParameter('date', $date->format('Y-m-d'))
            ->getQuery()
            ->getResult();
    }
}
