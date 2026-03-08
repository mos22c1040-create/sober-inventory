<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\AuthHelper;
use App\Helpers\Security;
use App\Models\Product;
use App\Models\Sale;

class PosController extends Controller
{
    public function index(): void
    {
        AuthHelper::requireAuth();
        $this->view('pos/index', [
            'title' => 'نقطة البيع',
            'csrfToken' => Security::generateCsrfToken(),
        ]);
    }

    /** GET /api/pos/products - list products for POS (search optional) */
    public function products(): void
    {
        AuthHelper::requireAuth();
        $products = Product::all(false);
        $search = isset($_GET['q']) ? trim($_GET['q']) : '';
        if ($search !== '') {
            $q = Security::sanitizeString($search);
            $products = array_filter($products, function ($p) use ($q) {
                return stripos($p['name'], $q) !== false || stripos((string) $p['sku'], $q) !== false;
            });
            $products = array_values($products);
        }
        $this->jsonResponse(['products' => $products]);
    }

    /** POST /api/pos/complete - complete a sale */
    public function complete(): void
    {
        AuthHelper::requireAuth();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Method not allowed'], 405);
        }
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            $this->jsonResponse(['error' => 'Invalid input'], 400);
        }
        if (!Security::validateCsrfToken($input['csrf_token'] ?? '')) {
            $this->jsonResponse(['error' => 'Invalid CSRF token'], 403);
        }
        $items = $input['items'] ?? [];
        if (empty($items)) {
            $this->jsonResponse(['error' => 'No items in sale'], 400);
        }
        $customerName = isset($input['customer_name']) ? Security::sanitizeString($input['customer_name']) : 'Walk-in Customer';
        $paymentMethod = in_array($input['payment_method'] ?? '', ['cash', 'card', 'mixed']) ? $input['payment_method'] : 'cash';
        $validItems = [];
        foreach ($items as $it) {
            $productId = (int) ($it['product_id'] ?? 0);
            $qty = (int) ($it['quantity'] ?? 0);
            if ($productId <= 0 || $qty <= 0) {
                continue;
            }
            $product = Product::find($productId);
            if (!$product || $product['quantity'] < $qty) {
                $this->jsonResponse(['error' => 'Insufficient stock for product ID ' . $productId], 400);
            }
            $unitPrice = (float) ($product['price']);
            $validItems[] = [
                'product_id' => $productId,
                'quantity' => $qty,
                'unit_price' => $unitPrice,
                'total' => $unitPrice * $qty,
            ];
        }
        if (empty($validItems)) {
            $this->jsonResponse(['error' => 'No valid items'], 400);
        }
        $userId = (int) $_SESSION['user_id'];
        $saleId = Sale::create($userId, $validItems, $customerName, $paymentMethod);
        $sale = Sale::find($saleId);
        $this->jsonResponse([
            'success' => true,
            'sale_id' => $saleId,
            'invoice_number' => $sale['invoice_number'],
            'total' => $sale['total'],
            'redirect' => '/pos',
        ], 201);
    }
}
