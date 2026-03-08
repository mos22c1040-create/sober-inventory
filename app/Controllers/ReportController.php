<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\AuthHelper;
use App\Models\Product;
use App\Models\Sale;
use App\Core\Database;

class ReportController extends Controller
{
    public function index(): void
    {
        AuthHelper::requireAuth();
        $db = Database::getInstance();
        $salesByDay = $db->query(
            "SELECT DATE(created_at) AS day, SUM(total) AS total, COUNT(*) AS count FROM sales WHERE status = 'paid' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) GROUP BY DATE(created_at) ORDER BY day DESC"
        )->fetchAll();
        $topProducts = $db->query(
            "SELECT p.name, SUM(si.quantity) AS qty_sold, SUM(si.total) AS revenue FROM sale_items si JOIN products p ON si.product_id = p.id JOIN sales s ON si.sale_id = s.id WHERE s.status = 'paid' AND s.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) GROUP BY si.product_id ORDER BY qty_sold DESC LIMIT 10"
        )->fetchAll();
        $this->view('reports/index', [
            'title' => 'التقارير',
            'salesByDay' => $salesByDay,
            'topProducts' => $topProducts,
        ]);
    }
}
