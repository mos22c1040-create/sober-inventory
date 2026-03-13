<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\AuthHelper;
use App\Helpers\FileCache;
use App\Helpers\RateLimiter;
use App\Helpers\Security;
use App\Models\Sale;
use App\Models\Product;
use App\Models\ActivityLog;
use App\Services\SaleService;
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
            'csrfToken'    => Security::generateCsrfToken(),
            'isAdmin'      => ($_SESSION['role'] ?? '') === 'admin',
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

    /** GET /sales/receipt?id=N */
    public function receipt(): void
    {
        AuthHelper::requireAuth();
        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) {
            header('Location: /sales');
            exit;
        }
        $sale = Sale::find($id);
        if (!$sale) {
            header('Location: /sales');
            exit;
        }
        $items       = Sale::getItems($id);
        $appSettings = file_exists(BASE_PATH . '/config/app_settings.php')
            ? (array) include BASE_PATH . '/config/app_settings.php'
            : [];

        // Receipt is a standalone page – just extract variables and include the view.
        extract(['sale' => $sale, 'items' => $items, 'appSettings' => $appSettings]);
        require BASE_PATH . '/views/sales/receipt.php';
        exit;
    }

    /** POST /api/sales/cancel */
    public function cancel(): void
    {
        AuthHelper::requireRole('admin');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Method not allowed'], 405);
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        if (!Security::validateCsrfToken($input['csrf_token'] ?? '')) {
            $this->jsonResponse(['error' => 'رمز الأمان غير صالح'], 403);
        }

        $id = (int) ($input['id'] ?? 0);
        if ($id <= 0) {
            $this->jsonResponse(['error' => 'معرف الفاتورة غير صالح'], 400);
        }

        try {
            $ok = Sale::cancel($id);
            if (!$ok) {
                $this->jsonResponse(['error' => 'تعذر إلغاء الفاتورة — تأكد أنها مدفوعة وغير ملغاة'], 422);
            }
            ActivityLog::log('sale.cancel', 'sale', $id, 'تم إلغاء الفاتورة #' . $id);
            FileCache::delete('dashboard_daily_totals_7');
            $this->jsonResponse(['success' => true]);
        } catch (\Exception $e) {
            $this->jsonResponse(['error' => 'حدث خطأ أثناء الإلغاء'], 500);
        }
    }

    /** GET /api/sales — JSON list for mobile */
    public function indexApi(): void
    {
        AuthHelper::requireAuth();
        $page    = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = max(1, min(50, (int) ($_GET['per_page'] ?? 25)));
        try {
            $paginated    = Sale::paginate($page, $perPage);
            $todayTotal   = Sale::todayTotal();
            $todayCount   = Sale::todayCount();
            $monthlyTotal = Sale::monthlyTotal();
        } catch (\PDOException $e) {
            $paginated    = ['data' => [], 'total' => 0, 'page' => 1, 'perPage' => $perPage, 'pages' => 0];
            $todayTotal   = 0.0;
            $todayCount   = 0;
            $monthlyTotal = 0.0;
        }
        $this->jsonResponse([
            'data'          => $paginated['data'],
            'page'          => $paginated['page'],
            'pages'         => $paginated['pages'],
            'total'         => $paginated['total'],
            'today_total'   => $todayTotal,
            'today_count'   => $todayCount,
            'monthly_total' => $monthlyTotal,
        ]);
    }

    /** POST /api/sales */
    public function store(): void
    {
        AuthHelper::requireAuth();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Method not allowed'], 405);
        }

        $userId = (int) $_SESSION['user_id'];
        $rateLimitKey = "sale_create_{$userId}";
        if (!RateLimiter::attempt($rateLimitKey, 20, 60)) {
            $wait = RateLimiter::retryAfter($rateLimitKey, 60);
            $this->jsonResponse([
                'error' => 'طلبات كثيرة جداً. انتظر ' . ceil($wait) . ' ثانية قبل المحاولة مجدداً.'
            ], 429);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        if (!Security::validateCsrfToken($input['csrf_token'] ?? '')) {
            $this->jsonResponse(['error' => 'رمز الأمان غير صالح'], 403);
        }

        $customerName  = trim(Security::sanitizeString((string) ($input['customer_name'] ?? '')));
        $paymentMethod = in_array($input['payment_method'] ?? '', ['cash', 'card', 'mixed']) ? $input['payment_method'] : 'cash';
        $discount      = max(0.0, (float) ($input['discount'] ?? 0));
        $notes         = trim(Security::sanitizeString((string) ($input['notes'] ?? '')));
        $items         = $input['items'] ?? [];

        if (empty($customerName)) {
            $customerName = 'عميل نقدي';
        }

        if (empty($items) || !is_array($items)) {
            $this->jsonResponse(['error' => 'الفاتورة فارغة'], 422);
        }

        try {
            $userId = AuthHelper::userId();
            $saleId = SaleService::createSale($userId, [
                'items'          => $items,
                'customer_name'  => $customerName,
                'payment_method' => $paymentMethod,
                'discount'       => $discount,
                'notes'          => $notes,
            ]);

            FileCache::delete('dashboard_daily_totals_7');

            $this->jsonResponse(['success' => true, 'sale_id' => $saleId, 'redirect' => '/sales']);
        } catch (\InvalidArgumentException $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            error_log('SaleService::createSale failed: ' . $e->getMessage());
            $this->jsonResponse(['error' => 'حدث خطأ أثناء حفظ الفاتورة'], 500);
        }
    }
}