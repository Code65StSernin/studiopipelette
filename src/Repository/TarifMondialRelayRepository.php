<?php

namespace App\Repository;

use App\Entity\TarifMondialRelay;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TarifMondialRelay>
 *
 * @method TarifMondialRelay|null find($id, $lockMode = null, $lockVersion = null)
 * @method TarifMondialRelay|null findOneBy(array $criteria, array $orderBy = null)
 * @method TarifMondialRelay[]    findAll()
 * @method TarifMondialRelay[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TarifMondialRelayRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TarifMondialRelay::class);
    }

    public function save(TarifMondialRelay $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(TarifMondialRelay $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
