<?php

namespace App\Repository;

use App\Entity\LigneVente;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LigneVente>
 *
 * @method LigneVente|null find($id, $lockMode = null, $lockVersion = null)
 * @method LigneVente|null findOneBy(array $criteria, array $orderBy = null)
 * @method LigneVente[]    findAll()
 * @method LigneVente[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LigneVenteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LigneVente::class);
    }

    public function save(LigneVente $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(LigneVente $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Retourne les articles les plus vendus EN CAISSE (basé sur LigneVente).
     */
    public function findTopArticlesCaisse(\DateTimeImmutable $from, \DateTimeImmutable $to, int $limit = 5): array
    {
        return $this->createQueryBuilder('l')
            ->select('l.nom AS designation, SUM(l.quantite) AS quantite')
            ->join('l.vente', 'v')
            ->andWhere('v.dateVente BETWEEN :from AND :to')
            ->andWhere('v.isAnnule = :false')
            ->andWhere('l.quantite > 0') // Valeurs positives uniquement
            ->andWhere('l.nom NOT LIKE :remise')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->setParameter('false', false)
            ->setParameter('remise', '%Remise%')
            ->groupBy('l.nom')
            ->orderBy('quantite', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult();
    }
}
