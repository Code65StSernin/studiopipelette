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
            ->andWhere('f.dateCreation BETWEEN :from AND :to')
            ->andWhere('f.order IS NOT NULL') // Uniquement Online
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->groupBy('l.articleDesignation')
            ->orderBy('quantite', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * Retourne les articles les plus vendus EN CAISSE (liés à une Vente).
     */
    public function findTopArticlesCaisse(\DateTimeImmutable $from, \DateTimeImmutable $to, int $limit = 5): array
    {
        return $this->createQueryBuilder('l')
            ->select('l.articleDesignation AS designation, SUM(l.quantite) AS quantite, SUM(l.prixTotal) AS ca')
            ->join('l.facture', 'f')
            ->andWhere('f.dateCreation BETWEEN :from AND :to')
            ->andWhere('f.vente IS NOT NULL') // Uniquement Caisse
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->groupBy('l.articleDesignation')
            ->orderBy('quantite', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult();
    }
}
