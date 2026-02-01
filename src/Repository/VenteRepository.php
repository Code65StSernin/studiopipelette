<?php

namespace App\Repository;

use App\Entity\Vente;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Vente>
 *
 * @method Vente|null find($id, $lockMode = null, $lockVersion = null)
 * @method Vente|null findOneBy(array $criteria, array $orderBy = null)
 * @method Vente[]    findAll()
 * @method Vente[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class VenteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Vente::class);
    }

    /**
     * Retourne une série de ventes (CA en centimes) agrégées par période.
     *
     * @param \DateTimeImmutable $from      Date de début (incluse)
     * @param string             $groupBy   day|week|month|year
     * @return array<int,array{label:string,total:int}>
     */
    public function getSalesSeries(\DateTimeImmutable $from, string $groupBy = 'day'): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $groupExpr = match ($groupBy) {
            'year' => "DATE_FORMAT(date_vente, '%Y-01-01')",
            'month' => "DATE_FORMAT(date_vente, '%Y-%m-01')",
            'week' => "STR_TO_DATE(CONCAT(YEARWEEK(date_vente, 3), ' Monday'), '%X%V %W')",
            default => "DATE(date_vente)",
        };

        $sql = "
            SELECT {$groupExpr} AS period_date, SUM(montant_total) AS total
            FROM vente
            WHERE is_annule = 0
              AND date_vente >= :from
            GROUP BY period_date
            ORDER BY period_date ASC
        ";

        $rows = $conn->fetchAllAssociative($sql, [
            'from' => $from->format('Y-m-d 00:00:00'),
        ]);

        $result = [];
        foreach ($rows as $row) {
            $date = new \DateTimeImmutable($row['period_date']);
            $label = match ($groupBy) {
                'year' => $date->format('Y'),
                'month' => $date->format('m/Y'),
                'week' => 'S' . $date->format('W') . ' ' . $date->format('Y'),
                default => $date->format('d/m'),
            };

            // Vente stocke des float (euros), on convertit en centimes pour homogénéiser avec Order
            $result[] = [
                'label' => $label,
                'total' => (int) round(((float) $row['total']) * 100),
            ];
        }

        return $result;
    }

    public function save(Vente $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Vente $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return Vente[] Returns an array of Vente objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('v')
//            ->andWhere('v.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('v.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Vente
//    {
//        return $this->createQueryBuilder('v')
//            ->andWhere('v.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
