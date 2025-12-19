<?php

namespace App\Repository;

use App\Entity\Order;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\DBAL\ArrayParameterType;

class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
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
            'year' => "DATE_FORMAT(created_at, '%Y-01-01')",
            'month' => "DATE_FORMAT(created_at, '%Y-%m-01')",
            'week' => "STR_TO_DATE(CONCAT(YEARWEEK(created_at, 3), ' Monday'), '%X%V %W')",
            default => "DATE(created_at)",
        };

        $sql = "
            SELECT {$groupExpr} AS period_date, SUM(amount_total_cents) AS total
            FROM `order`
            WHERE status = :status
              AND created_at >= :from
            GROUP BY period_date
            ORDER BY period_date ASC
        ";

        $rows = $conn->fetchAllAssociative($sql, [
            'status' => Order::STATUS_PAID,
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

            $result[] = [
                'label' => $label,
                'total' => (int) $row['total'],
            ];
        }

        return $result;
    }
}
