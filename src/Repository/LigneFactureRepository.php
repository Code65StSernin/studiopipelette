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
     * Retourne les articles les plus vendus EN LIGNE (liés à une Order).
     */
    public function findTopArticlesOnline(\DateTimeImmutable $from, \DateTimeImmutable $to, int $limit = 5): array
    {
        return $this->createQueryBuilder('l')
            ->select('l.articleDesignation AS designation, SUM(l.quantite) AS quantite, SUM(l.prixTotal) AS ca')
            ->join('l.facture', 'f')
            ->join('f.order', 'o')
            ->andWhere('f.dateCreation BETWEEN :from AND :to')
            ->andWhere('o.status = :status')
            ->andWhere('l.articleDesignation NOT LIKE :remise')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->setParameter('status', 'paid')
            ->setParameter('remise', '%Remise%')
            ->groupBy('l.articleDesignation')
            ->having('quantite > 0')
            ->orderBy('quantite', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult();
    }
}
