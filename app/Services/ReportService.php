<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

class ReportService
{
    public static function getProfitAndLoss(string $startDate, string $endDate): array
    {
        $db = Database::getInstance();
        $isPgsql = $db->getDriver() === 'pgsql';

        $dateCol = $isPgsql ? "(s.created_at::date)" : "DATE(s.created_at)";
        $returnDateCol = $isPgsql ? "(r.created_at::date)" : "DATE(r.created_at)";

        $grossSalesRow = $db->query(
            "SELECT COALESCE(SUM(s.total), 0) AS gross_sales
               FROM sales s
              WHERE s.status = 'paid' AND {$dateCol} BETWEEN :start AND :end",
            [':start' => $startDate, ':end' => $endDate]
        )->fetch();
        $grossSales = (float) ($grossSalesRow['gross_sales'] ?? 0);

        $totalReturnsRow = $db->query(
            "SELECT COALESCE(SUM(r.total_refund), 0) AS total_returns
               FROM returns r
              WHERE {$returnDateCol} BETWEEN :start AND :end",
            [':start' => $startDate, ':end' => $endDate]
        )->fetch();
        $totalReturns = (float) ($totalReturnsRow['total_returns'] ?? 0);

        $netSales = $grossSales - $totalReturns;

        $cogsRow = $db->query(
            "SELECT COALESCE(SUM(si.quantity * p.cost), 0) AS cogs
               FROM sale_items si
               JOIN products p ON si.product_id = p.id
               JOIN sales s ON si.sale_id = s.id
              WHERE s.status = 'paid' AND {$dateCol} BETWEEN :start AND :end",
            [':start' => $startDate, ':end' => $endDate]
        )->fetch();
        $grossCogs = (float) ($cogsRow['cogs'] ?? 0);

        $returnCostRow = $db->query(
            "SELECT COALESCE(SUM(ri.quantity * p.cost), 0) AS return_cost
               FROM return_items ri
               JOIN products p ON ri.product_id = p.id
               JOIN returns r ON ri.return_id = r.id
              WHERE {$returnDateCol} BETWEEN :start AND :end",
            [':start' => $startDate, ':end' => $endDate]
        )->fetch();
        $returnCost = (float) ($returnCostRow['return_cost'] ?? 0);

        $cogs = $grossCogs - $returnCost;

        $netProfit = $netSales - $cogs;

        return [
            'gross_sales'   => $grossSales,
            'total_returns' => $totalReturns,
            'net_sales'     => $netSales,
            'cogs'          => $cogs,
            'net_profit'    => $netProfit,
            'start_date'    => $startDate,
            'end_date'      => $endDate,
        ];
    }

    public static function getProfitByProduct(string $startDate, string $endDate, int $limit = 20): array
    {
        $db = Database::getInstance();
        $isPgsql = $db->getDriver() === 'pgsql';
        $dateCol = $isPgsql ? "(s.created_at::date)" : "DATE(s.created_at)";

        $stmt = $db->query(
            "SELECT p.name,
                    SUM(si.quantity) AS quantity_sold,
                    SUM(si.quantity * si.unit_price) AS revenue,
                    SUM(si.quantity * p.cost) AS cost,
                    SUM(si.quantity * (si.unit_price - p.cost)) AS profit
               FROM sale_items si
               JOIN products p ON si.product_id = p.id
               JOIN sales s ON si.sale_id = s.id
              WHERE s.status = 'paid' AND {$dateCol} BETWEEN :start AND :end
              GROUP BY p.id, p.name
              ORDER BY profit DESC
              LIMIT :limit",
            [':start' => $startDate, ':end' => $endDate, ':limit' => $limit]
        );

        $results = $stmt->fetchAll();

        return array_map(function ($row) {
            return [
                'name'         => $row['name'],
                'quantity'     => (int) $row['quantity_sold'],
                'revenue'      => (float) $row['revenue'],
                'cost'         => (float) $row['cost'],
                'profit'       => (float) $row['profit'],
                'profit_margin' => isset($row['revenue']) && $row['revenue'] > 0
                    ? round(($row['profit'] / $row['revenue']) * 100, 2)
                    : 0,
            ];
        }, $results);
    }

    public static function getDailySummary(string $startDate, string $endDate): array
    {
        $db = Database::getInstance();
        $isPgsql = $db->getDriver() === 'pgsql';

        $dateCol = $isPgsql ? "(s.created_at::date)" : "DATE(s.created_at)";
        $returnDateCol = $isPgsql ? "(r.created_at::date)" : "DATE(r.created_at)";

        $stmt = $db->query(
            "SELECT d.day,
                    COALESCE(sales.total, 0) AS gross_sales,
                    COALESCE(returns.total, 0) AS returns,
                    COALESCE(sales.cogs, 0) AS cogs
               FROM (
                   SELECT generate_series(
                       DATE(:start),
                       DATE(:end),
                       INTERVAL '1 day'
                   ) AS day
               ) d
               LEFT JOIN (
                   SELECT {$dateCol} AS day,
                          SUM(s.total) AS total,
                          SUM(si.quantity * p.cost) AS cogs
                     FROM sales s
                     JOIN sale_items si ON s.id = si.sale_id
                     JOIN products p ON si.product_id = p.id
                    WHERE s.status = 'paid'
                    GROUP BY {$dateCol}
               ) sales ON sales.day = d.day
               LEFT JOIN (
                   SELECT {$returnDateCol} AS day,
                          SUM(r.total_refund) AS total
                     FROM returns r
                    GROUP BY {$returnDateCol}
               ) returns ON returns.day = d.day
              ORDER BY d.day ASC",
            [':start' => $startDate, ':end' => $endDate]
        );

        return array_map(function ($row) {
            $grossSales = (float) $row['gross_sales'];
            $returns = (float) $row['returns'];
            $cogs = (float) $row['cogs'];
            $netSales = $grossSales - $returns;
            $profit = $netSales - $cogs;

            return [
                'date'       => $row['day'],
                'gross_sales' => $grossSales,
                'returns'    => $returns,
                'net_sales'  => $netSales,
                'cogs'       => $cogs,
                'profit'     => $profit,
            ];
        }, $stmt->fetchAll());
    }
}