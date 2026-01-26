<?php

namespace App\Repository;

use App\Entity\SousCategorieVente;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SousCategorieVente>
 *
 * @method SousCategorieVente|null find($id, $lockMode = null, $lockVersion = null)
 * @method SousCategorieVente|null findOneBy(array $criteria, array $orderBy = null)
 * @method SousCategorieVente[]    findAll()
 * @method SousCategorieVente[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SousCategorieVenteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SousCategorieVente::class);
    }

    public function save(SousCategorieVente $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(SousCategorieVente $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
