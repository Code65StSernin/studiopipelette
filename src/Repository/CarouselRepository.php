<?php

namespace App\Repository;

use App\Entity\Carousel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Carousel>
 */
class CarouselRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Carousel::class);
    }

    /**
     * @return Carousel[]
     */
    public function findActive(\DateTimeImmutable $now = null): array
    {
        $now ??= new \DateTimeImmutable();

        return $this->createQueryBuilder('c')
            ->andWhere('c.dateDebut <= :now')
            ->andWhere('c.dateFin >= :now')
            ->setParameter('now', $now)
            ->orderBy('c.dateDebut', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
