<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\AuthHelper;
use App\Helpers\Security;
use App\Models\ActivityLog;
use App\Models\Product;
use App\Models\Purchase;

class PurchaseController extends Controller
{
    public function index(): void
    {
        AuthHelper::requireRole('admin');
        $purchases = Purchase::all();
        $this->view('purchases/index', [
            'title' => 'المشتريات',
            'purchases' => $purchases,
        ]);
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
        }
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        if (!Security::validateCsrfToken($input['csrf_token'] ?? '')) {
            $this->jsonResponse(['error' => 'Invalid CSRF token'], 403);
        }
        $items = $input['items'] ?? [];
        $supplier = isset($input['supplier']) ? Security::sanitizeString($input['supplier']) : '';
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
                'quantity' => $qty,
                'unit_cost' => $unitCost,
                'total' => $unitCost * $qty,
            ];
        }
        if (empty($validItems)) {
            $this->jsonResponse(['error' => 'Add at least one item'], 400);
        }
        $userId = (int) $_SESSION['user_id'];
        $purchaseId = Purchase::create($userId, $validItems, $supplier);
        ActivityLog::log('purchase.create', 'purchase', $purchaseId, $supplier ?: '—');
        $this->jsonResponse(['success' => true, 'id' => $purchaseId, 'redirect' => '/purchases'], 201);
    }
}
