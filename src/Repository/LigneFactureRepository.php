<?php

namespace App\Repository;

use App\Entity\LigneFacture;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LigneFacture>
 */
class LigneFactureRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LigneFacture::class);
    }

    /**
     * Retourne les articles les plus vendus (par désignation) sur une période.
     *
     * @return array<int,array{designation:string,quantite:int,ca:int}>
     */
    public function findTopArticles(\DateTimeImmutable $from, \DateTimeImmutable $to, int $limit = 5): array
    {
        return $this->createQueryBuilder('l')
            ->select('l.articleDesignation AS designation, SUM(l.quantite) AS quantite, SUM(l.prixTotal) AS ca')
            ->join('l.facture', 'f')
            ->andWhere('f.dateCreation BETWEEN :from AND :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->groupBy('l.articleDesignation')
            ->orderBy('quantite', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult();
    }
}
