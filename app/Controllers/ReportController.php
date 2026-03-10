<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\AuthHelper;
use App\Models\Product;
use App\Models\Sale;
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
    public function index(): void
    {
        AuthHelper::requireRole('admin');
        $db = Database::getInstance();
        $isPgsql = $db->getDriver() === 'pgsql';
        $salesByDay = $isPgsql
            ? $db->query(
                "SELECT (s.created_at::date) AS day, SUM(s.total) AS total, COUNT(*) AS count FROM sales s WHERE s.status = 'paid' AND s.created_at >= (CURRENT_DATE - INTERVAL '30 days') GROUP BY (s.created_at::date) ORDER BY day DESC"
            )->fetchAll()
            : $db->query(
                "SELECT DATE(created_at) AS day, SUM(total) AS total, COUNT(*) AS count FROM sales WHERE status = 'paid' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) GROUP BY DATE(created_at) ORDER BY day DESC"
            )->fetchAll();
        $topProducts = $isPgsql
            ? $db->query(
                "SELECT p.name, SUM(si.quantity) AS qty_sold, SUM(si.total) AS revenue FROM sale_items si JOIN products p ON si.product_id = p.id JOIN sales s ON si.sale_id = s.id WHERE s.status = 'paid' AND s.created_at >= (CURRENT_DATE - INTERVAL '30 days') GROUP BY si.product_id, p.name ORDER BY qty_sold DESC LIMIT 10"
            )->fetchAll()
            : $db->query(
                "SELECT p.name, SUM(si.quantity) AS qty_sold, SUM(si.total) AS revenue FROM sale_items si JOIN products p ON si.product_id = p.id JOIN sales s ON si.sale_id = s.id WHERE s.status = 'paid' AND s.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) GROUP BY si.product_id ORDER BY qty_sold DESC LIMIT 10"
            )->fetchAll();
        $this->view('reports/index', [
            'title' => 'التقارير',
            'salesByDay' => $salesByDay,
            'topProducts' => $topProducts,
        ]);
    }

    /** GET /reports/export/sales — تصدير مبيعات آخر 30 يوماً كـ CSV */
    public function exportSalesCsv(): void
    {
        AuthHelper::requireRole('admin');
        $db = Database::getInstance();
        $isPgsql = $db->getDriver() === 'pgsql';
        $rows = $isPgsql
            ? $db->query("SELECT (created_at::date) AS day, SUM(total) AS total, COUNT(*) AS count FROM sales WHERE status = 'paid' AND created_at >= (CURRENT_DATE - INTERVAL '30 days') GROUP BY (created_at::date) ORDER BY day ASC")->fetchAll()
            : $db->query("SELECT DATE(created_at) AS day, SUM(total) AS total, COUNT(*) AS count FROM sales WHERE status = 'paid' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) GROUP BY DATE(created_at) ORDER BY day ASC")->fetchAll();

        $filename = 'sales_report_' . date('Y-m-d') . '.csv';
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $out = fopen('php://output', 'w');
        fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
        fputcsv($out, ['التاريخ', 'عدد الفواتير', 'الإجمالي (' . self::currencySymbol() . ')']);
        foreach ($rows as $r) {
            fputcsv($out, [$r['day'], (int) $r['count'], (float) $r['total']]);
        }
        fclose($out);
        exit;
    }

    /** GET /reports/export/products — تصدير أكثر المنتجات مبيعاً كـ CSV */
    public function exportTopProductsCsv(): void
    {
        AuthHelper::requireRole('admin');
        $db = Database::getInstance();
        $isPgsql = $db->getDriver() === 'pgsql';
        $rows = $isPgsql
            ? $db->query("SELECT p.name AS product_name, SUM(si.quantity) AS qty_sold, SUM(si.total) AS revenue FROM sale_items si JOIN products p ON si.product_id = p.id JOIN sales s ON si.sale_id = s.id WHERE s.status = 'paid' AND s.created_at >= (CURRENT_DATE - INTERVAL '30 days') GROUP BY si.product_id, p.name ORDER BY qty_sold DESC LIMIT 100")->fetchAll()
            : $db->query("SELECT p.name AS product_name, SUM(si.quantity) AS qty_sold, SUM(si.total) AS revenue FROM sale_items si JOIN products p ON si.product_id = p.id JOIN sales s ON si.sale_id = s.id WHERE s.status = 'paid' AND s.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) GROUP BY si.product_id ORDER BY qty_sold DESC LIMIT 100")->fetchAll();

        $filename = 'top_products_' . date('Y-m-d') . '.csv';
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $out = fopen('php://output', 'w');
        fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
        fputcsv($out, ['المنتج', 'الكمية المباعة', 'الإيراد (' . self::currencySymbol() . ')']);
        foreach ($rows as $r) {
            fputcsv($out, [$r['product_name'], (int) $r['qty_sold'], (float) $r['revenue']]);
        }
        fclose($out);
        exit;
    }
}
