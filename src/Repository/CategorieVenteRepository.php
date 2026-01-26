<?php

namespace App\Repository;

use App\Entity\CategorieVente;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CategorieVente>
 *
 * @method CategorieVente|null find($id, $lockMode = null, $lockVersion = null)
 * @method CategorieVente|null findOneBy(array $criteria, array $orderBy = null)
 * @method CategorieVente[]    findAll()
 * @method CategorieVente[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CategorieVenteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CategorieVente::class);
    }

    public function save(CategorieVente $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(CategorieVente $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
