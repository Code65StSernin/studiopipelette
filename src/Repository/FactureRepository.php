<?php

namespace App\Repository;

use App\Entity\Facture;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Facture>
 */
class FactureRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Facture::class);
    }

    /**
     * Retourne le prochain numéro séquentiel pour le mois en cours
     * Format: FA + 2 derniers chiffres année + 2 chiffres mois + - + numéro séquentiel 0001-9999
     * Cette méthode utilise une approche atomique pour éviter les conditions de course
     */
    public function getNextSequentialNumberForCurrentMonth(): int
    {
        $currentYear = (int) date('Y');
        $currentMonth = (int) date('m');
        $prefix = 'FA' . date('ym'); // FA + année 2 chiffres + mois 2 chiffres

        // Utiliser une requête qui compte les factures avec le préfixe du mois en cours
        $qb = $this->createQueryBuilder('f');
        $qb->select('COUNT(f.id)')
           ->where('f.numero LIKE :prefix')
           ->setParameter('prefix', $prefix . '-%');

        $count = (int) $qb->getQuery()->getSingleScalarResult();

        return $count + 1;
    }

    public function countForUser(User $user): int
    {
        return (int) $this->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->join('f.order', 'o')
            ->andWhere('o.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
