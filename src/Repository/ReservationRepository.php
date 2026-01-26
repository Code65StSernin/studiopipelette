<?php

namespace App\Repository;

use App\Entity\Reservation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Reservation>
 */
class ReservationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reservation::class);
    }

    public function findReservationsByDay(\DateTimeInterface $day): array
    {
        $startOfDay = (clone $day)->setTime(0, 0, 0);
        $endOfDay = (clone $day)->setTime(23, 59, 59);

        return $this->createQueryBuilder('r')
            ->andWhere('r.dateStart BETWEEN :start AND :end')
            ->orWhere('r.dateEnd BETWEEN :start AND :end')
            ->orWhere('r.dateStart < :start AND r.dateEnd > :end')
            ->setParameter('start', $startOfDay)
            ->setParameter('end', $endOfDay)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Reservation[]
     */
    public function findReservationsBetween(\DateTimeInterface $debut, \DateTimeInterface $fin): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.dateStart < :fin AND r.dateEnd > :debut')
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->getQuery()
            ->getResult()
        ;
    }

    public function countReservationsForTariffs(\DateTimeInterface $date, array $tarifIds): int
    {
        if (empty($tarifIds)) {
            return 0;
        }

        $startOfDay = (clone $date)->setTime(0, 0, 0);
        $endOfDay = (clone $date)->setTime(23, 59, 59);

        return $this->createQueryBuilder('r')
            ->select('COUNT(DISTINCT r.id)')
            ->innerJoin('r.prestations', 'p')
            ->where('r.dateStart BETWEEN :start AND :end')
            ->andWhere('p.id IN (:tarifIds)')
            ->setParameter('start', $startOfDay)
            ->setParameter('end', $endOfDay)
            ->setParameter('tarifIds', $tarifIds)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return Reservation[]
     */
    public function findByClientEmail(string $email): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.clientEmail = :email')
            ->setParameter('email', $email)
            ->orderBy('r.dateStart', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
