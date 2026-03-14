<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\AuthHelper;
use App\Helpers\Security;
use App\Models\ActivityLog;
use App\Models\Product;
use App\Models\Purchase;
use App\Services\PurchaseService;
use App\Helpers\FileCache;

class PurchaseController extends Controller
{
    public function index(): void
    {
        AuthHelper::requireRole('admin');
        $purchases = Purchase::all();
        $this->view('purchases/index', [
            'title'     => 'المشتريات',
            'purchases' => $purchases,
            'csrfToken' => Security::generateCsrfToken(),
        ]);
    }

    /** GET /api/purchases/list — JSON for mobile (admin only) */
    public function indexApi(): void
    {
        AuthHelper::requireRole('admin');
        $this->jsonResponse(['data' => Purchase::all()]);
    }

    public function create(): void
    {
        AuthHelper::requireRole('admin');
        $products = Product::all(true);
        $this->view('purchases/form', [
            'title' => 'مشتريات جديدة',
            'products' => $products,
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

        $items = $input['items'] ?? [];
        $supplier = isset($input['supplier']) ? Security::sanitizeString($input['supplier']) : '';

        if (empty($items) || !is_array($items)) {
            $this->jsonResponse(['error' => 'قائمة المشتريات فارغة'], 400);
            return;
        }

        $validItems = [];
        foreach ($items as $it) {
            $productId = (int) ($it['product_id'] ?? 0);
            $qty = (int) ($it['quantity'] ?? 0);
            $unitCost = (float) ($it['unit_cost'] ?? 0);

            if ($productId <= 0 || $qty <= 0) {
                continue;
            }

            $validItems[] = [
                'product_id' => $productId,
                'quantity'   => $qty,
                'unit_cost'  => $unitCost,
            ];
        }

        if (empty($validItems)) {
            $this->jsonResponse(['error' => 'أضف منتجاً واحداً على الأقل'], 400);
            return;
        }

        try {
            $userId = (int) $_SESSION['user_id'];
            $purchaseId = PurchaseService::createPurchase($userId, [
                'items'    => $validItems,
                'supplier' => $supplier,
            ]);

            FileCache::delete('dashboard_product_count');

            $this->jsonResponse([
                'success'  => true,
                'id'       => $purchaseId,
                'redirect' => '/purchases',
            ], 201);
        } catch (\InvalidArgumentException $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            error_log('PurchaseService::createPurchase failed: ' . $e->getMessage());
            $this->jsonResponse(['error' => 'حدث خطأ أثناء حفظ عملية الشراء'], 500);
        }
    }

    /** POST /api/purchases/delete */
    public function delete(): void
    {
        AuthHelper::requireRole('admin');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Method not allowed'], 405);
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        if (!Security::validateCsrfToken($input['csrf_token'] ?? '')) {
            $this->jsonResponse(['error' => 'Invalid CSRF token'], 403);
        }

        $id = (int) ($input['id'] ?? 0);
        if ($id <= 0) {
            $this->jsonResponse(['error' => 'معرف الشراء غير صالح'], 400);
        }

        try {
            $result = Purchase::delete($id);
            if (!$result['ok']) {
                $this->jsonResponse(['error' => $result['error']], 422);
            }
            ActivityLog::log('purchase.delete', 'purchase', $id, 'حذف طلب الشراء #' . $id);
            FileCache::delete('dashboard_product_count');
            $this->jsonResponse(['success' => true]);
        } catch (\Exception $e) {
            $this->jsonResponse(['error' => 'حدث خطأ أثناء الحذف'], 500);
        }
    }
}