<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\AuthHelper;
use App\Helpers\Security;
use App\Services\ReturnService;
use App\Helpers\FileCache;

class ReturnController extends Controller
{
    public function index(): void
    {
        AuthHelper::requireRole('admin');
        $this->view('returns/index', [
            'title'     => 'المرتجعات',
            'csrfToken' => Security::generateCsrfToken(),
        ]);
    }

    public function create(): void
    {
        AuthHelper::requireRole('admin');
        $this->view('returns/form', [
            'title'     => 'إرجاع جديد',
            'csrfToken' => Security::generateCsrfToken(),
        ]);
    }

    public function store(): void
    {
        AuthHelper::requireRole('admin');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Method not allowed'], 405);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        if (!Security::validateCsrfToken($input['csrf_token'] ?? '')) {
            $this->jsonResponse(['error' => 'Invalid CSRF token'], 403);
            return;
        }

        $saleId = (int) ($input['sale_id'] ?? 0);
        $items = $input['items'] ?? [];
        $reason = isset($input['reason']) ? Security::sanitizeString($input['reason']) : null;

        if ($saleId <= 0) {
            $this->jsonResponse(['error' => 'معرف الفاتورة مطلوب'], 400);
            return;
        }

        if (empty($items) || !is_array($items)) {
            $this->jsonResponse(['error' => 'قائمة المرتجعات فارغة'], 400);
            return;
        }

        $validItems = [];
        foreach ($items as $it) {
            $productId = (int) ($it['product_id'] ?? 0);
            $qty = (int) ($it['quantity'] ?? 0);
            $unitPrice = (float) ($it['unit_price'] ?? 0);

            if ($productId <= 0 || $qty <= 0) {
                continue;
            }

            $validItems[] = [
                'product_id' => $productId,
                'quantity'   => $qty,
                'unit_price' => $unitPrice,
            ];
        }

        if (empty($validItems)) {
            $this->jsonResponse(['error' => 'أضف منتجاً واحداً على الأقل'], 400);
            return;
        }

        try {
            $userId = (int) $_SESSION['user_id'];
            $returnId = ReturnService::createReturn($userId, $saleId, $validItems, $reason);

            FileCache::delete('dashboard_daily_totals_7');

            $this->jsonResponse([
                'success'  => true,
                'id'       => $returnId,
                'redirect' => '/returns',
            ], 201);
        } catch (\InvalidArgumentException $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            error_log('ReturnService::createReturn failed: ' . $e->getMessage());
            $this->jsonResponse(['error' => 'حدث خطأ أثناء حفظ عملية الإرجاع'], 500);
        }
    }

    public function indexApi(): void
    {
        AuthHelper::requireRole('admin');

        $page    = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = max(1, min(50, (int) ($_GET['per_page'] ?? 25)));

        $db = \App\Core\Database::getInstance();

        $offset = ($page - 1) * $perPage;
        $perPageInt = (int) $perPage;

        $countStmt = $db->query("SELECT COUNT(*) AS cnt FROM returns");
        $total = (int) ($countStmt->fetch()['cnt'] ?? 0);

        $stmt = $db->query(
            "SELECT r.*, u.username AS created_by, s.invoice_number AS sale_invoice
             FROM returns r
             LEFT JOIN users u ON r.user_id = u.id
             LEFT JOIN sales s ON r.sale_id = s.id
             ORDER BY r.created_at DESC
             LIMIT {$perPageInt} OFFSET " . (int) $offset
        );
        $data = $stmt->fetchAll();

        $this->jsonResponse([
            'data'    => $data,
            'page'    => $page,
            'pages'   => $total > 0 ? (int) ceil($total / $perPage) : 0,
            'total'   => $total,
        ]);
    }
}