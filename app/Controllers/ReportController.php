<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\AuthHelper;
use App\Core\Database;

class ReportController extends Controller
{
    private static function currencySymbol(): string
    {
        $path = defined('BASE_PATH') ? BASE_PATH . '/config/app_settings.php' : __DIR__ . '/../../config/app_settings.php';
        if (!is_file($path)) {
            return 'د.ع';
        }
        $settings = (array) include $path;
        return (string) ($settings['currency_symbol'] ?? 'د.ع');
    }

    /** Validate and parse a date string; returns Y-m-d or null. */
    private static function parseDate(string $input): ?string
    {
        $d = \DateTime::createFromFormat('Y-m-d', trim($input));
        return ($d && $d->format('Y-m-d') === trim($input)) ? trim($input) : null;
    }

    /** Build the WHERE clause fragment for a date range. */
    private static function dateClause(bool $isPgsql, string $from, string $to): array
    {
        if ($isPgsql) {
            return [
                'sql'    => "(s.created_at::date) BETWEEN :from AND :to",
                'params' => [':from' => $from, ':to' => $to],
            ];
        }
        return [
            'sql'    => "DATE(s.created_at) BETWEEN :from AND :to",
            'params' => [':from' => $from, ':to' => $to],
        ];
    }

    /** GET /api/reports/data — JSON for mobile (admin only) */
    public function indexApi(): void
    {
        AuthHelper::requireRole('admin');

        $from = self::parseDate((string) ($_GET['from'] ?? '')) ?? date('Y-m-d', strtotime('-30 days'));
        $to   = self::parseDate((string) ($_GET['to']   ?? '')) ?? date('Y-m-d');
        if ($from > $to) { [$from, $to] = [$to, $from]; }

        $db      = Database::getInstance();
        $isPgsql = $db->getDriver() === 'pgsql';
        $dw      = self::dateClause($isPgsql, $from, $to);
        $dayCol  = $isPgsql ? "(s.created_at::date)" : "DATE(s.created_at)";

        $salesByDay = $db->query(
            "SELECT {$dayCol} AS day, SUM(s.total) AS total, COUNT(*) AS count
               FROM sales s WHERE s.status = 'paid' AND {$dw['sql']}
              GROUP BY {$dayCol} ORDER BY day DESC",
            $dw['params']
        )->fetchAll();

        $topProducts = $db->query(
            "SELECT p.name, SUM(si.quantity) AS qty_sold, SUM(si.total) AS revenue
               FROM sale_items si JOIN products p ON si.product_id = p.id
               JOIN sales s ON si.sale_id = s.id
              WHERE s.status = 'paid' AND {$dw['sql']}
              GROUP BY si.product_id, p.name ORDER BY qty_sold DESC LIMIT 10",
            $dw['params']
        )->fetchAll();

        $profitRow = null;
        try {
            $profitRow = $db->query(
                "SELECT SUM(si.quantity * si.unit_price) AS total_revenue,
                        SUM(si.quantity * p.cost) AS total_cost,
                        SUM(si.quantity * (si.unit_price - p.cost)) AS gross_profit
                   FROM sale_items si JOIN products p ON si.product_id = p.id
                   JOIN sales s ON si.sale_id = s.id
                  WHERE s.status = 'paid' AND {$dw['sql']}",
                $dw['params']
            )->fetch();
        } catch (\PDOException $e) {}

        $this->jsonResponse([
            'sales_by_day'   => $salesByDay,
            'top_products'   => $topProducts,
            'profit'         => $profitRow ?: ['total_revenue' => 0, 'total_cost' => 0, 'gross_profit' => 0],
            'date_from'      => $from,
            'date_to'        => $to,
        ]);
    }

    public function index(): void
    {
        AuthHelper::requireRole('admin');

        // ── Date range ───────────────────────────────────────────────────
        $from = self::parseDate((string) ($_GET['from'] ?? '')) ?? date('Y-m-d', strtotime('-30 days'));
        $to   = self::parseDate((string) ($_GET['to']   ?? '')) ?? date('Y-m-d');

        // Prevent reversed range
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        $db      = Database::getInstance();
        $isPgsql = $db->getDriver() === 'pgsql';
        $dw      = self::dateClause($isPgsql, $from, $to);

        // ── Sales by day ─────────────────────────────────────────────────
        $dayCol    = $isPgsql ? "(s.created_at::date)" : "DATE(s.created_at)";
        $salesByDay = $db->query(
            "SELECT {$dayCol} AS day, SUM(s.total) AS total, COUNT(*) AS count
               FROM sales s
              WHERE s.status = 'paid' AND {$dw['sql']}
              GROUP BY {$dayCol}
              ORDER BY day DESC",
            $dw['params']
        )->fetchAll();

        // ── Top products ─────────────────────────────────────────────────
        $topParams = $dw['params'];
        $topProducts = $db->query(
            "SELECT p.name, SUM(si.quantity) AS qty_sold, SUM(si.total) AS revenue
               FROM sale_items si
               JOIN products p  ON si.product_id = p.id
               JOIN sales s     ON si.sale_id = s.id
              WHERE s.status = 'paid' AND {$dw['sql']}
              GROUP BY si.product_id, p.name
              ORDER BY qty_sold DESC
              LIMIT 10",
            $topParams
        )->fetchAll();

        // ── Profit summary ────────────────────────────────────────────────
        $profitRow       = null;
        $profitByProduct = [];
        try {
            $profitRow = $db->query(
                "SELECT
                    SUM(si.quantity * si.unit_price)               AS total_revenue,
                    SUM(si.quantity * p.cost)                      AS total_cost,
                    SUM(si.quantity * (si.unit_price - p.cost))    AS gross_profit
                   FROM sale_items si
                   JOIN products p ON si.product_id = p.id
                   JOIN sales s    ON si.sale_id = s.id
                  WHERE s.status = 'paid' AND {$dw['sql']}",
                $dw['params']
            )->fetch();

            $profitByProduct = $db->query(
                "SELECT p.name,
                    SUM(si.quantity)                               AS qty_sold,
                    SUM(si.quantity * si.unit_price)               AS revenue,
                    SUM(si.quantity * p.cost)                      AS cost,
                    SUM(si.quantity * (si.unit_price - p.cost))    AS profit
                   FROM sale_items si
                   JOIN products p ON si.product_id = p.id
                   JOIN sales s    ON si.sale_id = s.id
                  WHERE s.status = 'paid' AND {$dw['sql']}
                  GROUP BY si.product_id, p.name
                  ORDER BY profit DESC
                  LIMIT 10",
                $dw['params']
            )->fetchAll();
        } catch (\PDOException $e) {
            // profit columns unavailable — skip silently
        }

        $this->view('reports/index', [
            'title'           => 'التقارير',
            'salesByDay'      => $salesByDay,
            'topProducts'     => $topProducts,
            'profitRow'       => $profitRow ?: ['total_revenue' => 0, 'total_cost' => 0, 'gross_profit' => 0],
            'profitByProduct' => $profitByProduct ?: [],
            'dateFrom'        => $from,
            'dateTo'          => $to,
        ]);
    }

    /** GET /reports/export/sales */
    public function exportSalesCsv(): void
    {
        AuthHelper::requireRole('admin');

        $from = self::parseDate((string) ($_GET['from'] ?? '')) ?? date('Y-m-d', strtotime('-30 days'));
        $to   = self::parseDate((string) ($_GET['to']   ?? '')) ?? date('Y-m-d');
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        $db      = Database::getInstance();
        $isPgsql = $db->getDriver() === 'pgsql';
        $dw      = self::dateClause($isPgsql, $from, $to);
        $dayCol  = $isPgsql ? "(created_at::date)" : "DATE(created_at)";

        $rows = $db->query(
            "SELECT {$dayCol} AS day, SUM(total) AS total, COUNT(*) AS count
               FROM sales
              WHERE status = 'paid' AND {$dw['sql']}
              GROUP BY {$dayCol}
              ORDER BY day ASC",
            [':from' => $from, ':to' => $to]
        )->fetchAll();

        $filename = 'sales_report_' . $from . '_to_' . $to . '.csv';
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $out = fopen('php://output', 'w');
        fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($out, ['التاريخ', 'عدد الفواتير', 'الإجمالي (' . self::currencySymbol() . ')']);
        foreach ($rows as $r) {
            fputcsv($out, [$r['day'], (int) $r['count'], (float) $r['total']]);
        }
        fclose($out);
        exit;
    }

    /** GET /reports/export/products */
    public function exportTopProductsCsv(): void
    {
        AuthHelper::requireRole('admin');

        $from = self::parseDate((string) ($_GET['from'] ?? '')) ?? date('Y-m-d', strtotime('-30 days'));
        $to   = self::parseDate((string) ($_GET['to']   ?? '')) ?? date('Y-m-d');
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        $db      = Database::getInstance();
        $isPgsql = $db->getDriver() === 'pgsql';
        $dw      = self::dateClause($isPgsql, $from, $to);

        $rows = $db->query(
            "SELECT p.name AS product_name, SUM(si.quantity) AS qty_sold, SUM(si.total) AS revenue
               FROM sale_items si
               JOIN products p ON si.product_id = p.id
               JOIN sales s    ON si.sale_id = s.id
              WHERE s.status = 'paid' AND {$dw['sql']}
              GROUP BY si.product_id, p.name
              ORDER BY qty_sold DESC
              LIMIT 100",
            [':from' => $from, ':to' => $to]
        )->fetchAll();

        $filename = 'top_products_' . $from . '_to_' . $to . '.csv';
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $out = fopen('php://output', 'w');
        fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($out, ['المنتج', 'الكمية المباعة', 'الإيراد (' . self::currencySymbol() . ')']);
        foreach ($rows as $r) {
            fputcsv($out, [$r['product_name'], (int) $r['qty_sold'], (float) $r['revenue']]);
        }
        fclose($out);
        exit;
    }
}
