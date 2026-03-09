<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\AuthHelper;
use App\Helpers\Security;
use App\Models\Sale;
use App\Models\Product;
use App\Models\ActivityLog;
use PDOException;

class SaleController extends Controller
{
    public function index(): void
    {
        AuthHelper::requireAuth();
        try {
            $sales = Sale::all(100);
        } catch (PDOException $e) {
            $sales = [];
        }

        $this->view('sales/index', [
            'title' => 'المبيعات',
            'sales' => $sales,
        ]);
    }

    public function create(): void
    {
        AuthHelper::requireAuth();
        $this->view('sales/form', [
            'title'     => 'فاتورة مبيعات جديدة',
            'csrfToken' => Security::generateCsrfToken(),
            // We won't load all products here. We will rely on barcode scanning or auto-complete API
        ]);
    }

    /** POST /api/sales */
    public function store(): void
    {
        AuthHelper::requireAuth();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Method not allowed'], 405);
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        if (!Security::validateCsrfToken($input['csrf_token'] ?? '')) {
            $this->jsonResponse(['error' => 'رمز الأمان غير صالح'], 403);
        }

        $customerName  = trim(Security::sanitizeString((string) ($input['customer_name'] ?? '')));
        $paymentMethod = in_array($input['payment_method'] ?? '', ['cash', 'card']) ? $input['payment_method'] : 'cash';
        $items         = $input['items'] ?? [];

        if (empty($customerName)) {
            $customerName = 'عميل نقدي';
        }

        if (empty($items) || !is_array($items)) {
            $this->jsonResponse(['error' => 'الفاتورة فارغة'], 422);
        }

        // Validate items and check stock
        $validItems = [];
        foreach ($items as $item) {
            $productId = (int) ($item['product_id'] ?? 0);
            $qty       = (int) ($item['quantity'] ?? 0);
            
            if ($productId <= 0 || $qty <= 0) {
                $this->jsonResponse(['error' => 'بيانات المنتجات غير صالحة'], 422);
            }

            $product = Product::find($productId);
            if (!$product) {
                $this->jsonResponse(['error' => "المنتج رقم $productId غير موجود"], 404);
            }

            if ($product['quantity'] < $qty) {
                $this->jsonResponse(['error' => "الكمية المتوفرة من {$product['name']} غير كافية (المتوفر: {$product['quantity']})"], 422);
            }

            $price = (float) $product['price'];
            $validItems[] = [
                'product_id' => $productId,
                'quantity'   => $qty,
                'unit_price' => $price,
                'total'      => $qty * $price,
            ];
        }

        try {
            $userId = AuthHelper::userId();
            $saleId = Sale::create($userId, $validItems, $customerName, $paymentMethod);

            ActivityLog::log('sale.create', 'sale', $saleId, "فاتورة للعميل: $customerName");

            $this->jsonResponse(['success' => true, 'redirect' => '/sales']);
        } catch (\Exception $e) {
            $this->jsonResponse(['error' => 'حدث خطأ أثناء حفظ الفاتورة'], 500);
        }
    }
}