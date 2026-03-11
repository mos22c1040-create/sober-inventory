<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\AuthHelper;
use App\Helpers\FileCache;
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
        $page = max(1, (int) ($_GET['page'] ?? 1));
        try {
            $paginated    = Sale::paginate($page, 25);
            $todayTotal   = Sale::todayTotal();
            $todayCount   = Sale::todayCount();
            $monthlyTotal = Sale::monthlyTotal();
        } catch (PDOException $e) {
            $paginated    = ['data' => [], 'total' => 0, 'page' => 1, 'perPage' => 25, 'pages' => 0];
            $todayTotal   = 0.0;
            $todayCount   = 0;
            $monthlyTotal = 0.0;
        }

        $this->view('sales/index', [
            'title'        => 'المبيعات',
            'sales'        => $paginated['data'],
            'pagination'   => $paginated,
            'todayTotal'   => $todayTotal,
            'todayCount'   => $todayCount,
            'monthlyTotal' => $monthlyTotal,
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
        $discount      = max(0.0, (float) ($input['discount'] ?? 0));
        $notes         = trim(Security::sanitizeString((string) ($input['notes'] ?? '')));
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
            $saleId = Sale::create($userId, $validItems, $customerName, $paymentMethod, $discount, $notes);

            ActivityLog::log('sale.create', 'sale', $saleId, "فاتورة للعميل: $customerName");

            // Invalidate dashboard daily-totals cache after a new sale
            FileCache::delete('dashboard_daily_totals_7');

            $this->jsonResponse(['success' => true, 'redirect' => '/sales']);
        } catch (\Exception $e) {
            $this->jsonResponse(['error' => 'حدث خطأ أثناء حفظ الفاتورة'], 500);
        }
    }
}