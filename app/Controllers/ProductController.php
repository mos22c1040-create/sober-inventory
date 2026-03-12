<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\AuthHelper;
use App\Helpers\FileCache;
use App\Helpers\BarcodeBridge;
use App\Helpers\Security;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Product;
use App\Validators\ProductValidator;

class ProductController extends Controller
{
    public function index(): void
    {
        AuthHelper::requireAuth();
        $page       = max(1, (int) ($_GET['page'] ?? 1));
        $paginated  = Product::paginate($page, 20);
        $this->view('products/index', [
            'title'      => 'المنتجات',
            'products'   => $paginated['data'],
            'pagination' => $paginated,
            'csrfToken'  => Security::generateCsrfToken(),
        ]);
    }

    public function create(): void
    {
        AuthHelper::requireRole('admin');
        $categories = Category::all();
        $this->view('products/form', [
            'title' => 'إضافة منتج',
            'product' => null,
            'categories' => $categories,
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
        $csrf = $input['csrf_token'] ?? '';
        if (!Security::validateCsrfToken($csrf)) {
            $this->jsonResponse(['error' => 'Invalid CSRF token'], 403);
        }
        $result = ProductValidator::validate($input);
        if (!$result['valid']) {
            $this->jsonResponse(['error' => implode(' | ', $result['errors'])], 400);
        }
        $data = $result['data'];
        $id = Product::create($data);
        ActivityLog::log('product.create', 'product', $id, (string) $data['name']);
        FileCache::delete('dashboard_product_count');
        $this->jsonResponse(['success' => true, 'id' => $id, 'redirect' => '/products'], 201);
    }

    public function edit(): void
    {
        AuthHelper::requireRole('admin');
        $id = (int) ($_GET['id'] ?? 0);
        $product = $id ? Product::find($id) : null;
        if (!$product) {
            $this->renderNotFound('404');
            return;
        }
        $categories = Category::all();
        $this->view('products/form', [
            'title' => 'تعديل المنتج',
            'product' => $product,
            'categories' => $categories,
            'csrfToken' => Security::generateCsrfToken(),
        ]);
    }

    public function update(): void
    {
        AuthHelper::requireRole('admin');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Method not allowed'], 405);
        }
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        if (!Security::validateCsrfToken($input['csrf_token'] ?? '')) {
            $this->jsonResponse(['error' => 'Invalid CSRF token'], 403);
        }
        $id = (int) ($input['id'] ?? 0);
        $product = $id ? Product::find($id) : null;
        if (!$product) {
            $this->jsonResponse(['error' => 'Product not found'], 404);
        }
        $result = ProductValidator::validate($input);
        if (!$result['valid']) {
            $this->jsonResponse(['error' => implode(' | ', $result['errors'])], 400);
        }
        $data = $result['data'];
        Product::update($id, $data);
        ActivityLog::log('product.update', 'product', $id, (string) $data['name']);
        $this->jsonResponse(['success' => true, 'redirect' => '/products']);
    }

    public function delete(): void
    {
        AuthHelper::requireRole('admin');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Method not allowed'], 405);
        }
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        if (!Security::validateCsrfToken($input['csrf_token'] ?? '')) {
            $this->jsonResponse(['error' => 'Invalid CSRF token'], 403);
        }
        $id = (int) ($input['id'] ?? 0);
        if (!$id) {
            $this->jsonResponse(['error' => 'Invalid ID'], 400);
        }
        $name = Product::find($id)['name'] ?? '';
        Product::delete($id);
        ActivityLog::log('product.delete', 'product', $id, $name);
        FileCache::delete('dashboard_product_count');
        $this->jsonResponse(['success' => true]);
    }

    /** GET /api/products/search?q=xxx — بحث بالاسم أو الرمز (autocomplete). */
    public function search(): void
    {
        AuthHelper::requireAuth();
        $q = trim((string) ($_GET['q'] ?? ''));
        if (strlen($q) < 1) {
            $this->jsonResponse([]);
            return;
        }
        $results = Product::search($q, 12);
        $this->jsonResponse($results);
    }

    /** GET /api/products/barcode?sku=xxx — find product by SKU/barcode for scanner. */
    public function barcode(): void
    {
        AuthHelper::requireAuth();
        $sku = trim((string) ($_GET['sku'] ?? ''));
        if ($sku === '') {
            $this->jsonResponse(['error' => 'الرمز مطلوب'], 400);
        }
        $product = Product::findBySku($sku);
        if (!$product) {
            $this->jsonResponse(['error' => 'لم يُعثر على منتج بهذا الرمز'], 404);
        }
        $this->jsonResponse(['success' => true, 'product' => $product]);
    }

    /** GET /barcode-scan — صفحة مخصّصة للجوال: مسح الباركود وإرساله للحاسوب. */
    public function barcodeScanPage(): void
    {
        AuthHelper::requireAuth();
        $this->view('barcode/scan', [
            'title'     => 'مسح الباركود وإرسال للحاسوب',
            'csrfToken' => Security::generateCsrfToken(),
        ]);
    }

    /**
     * POST /api/barcode-push — من الجوال: إرسال الباركود الممسوح إلى الحاسوب (ربط بالكابل/شبكة).
     */
    public function barcodePush(): void
    {
        AuthHelper::requireAuth();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Method not allowed'], 405);
        }
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $code  = trim((string) ($input['barcode'] ?? $input['code'] ?? ''));
        if ($code === '') {
            $this->jsonResponse(['error' => 'الرمز مطلوب'], 400);
        }
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        if ($userId < 1) {
            $this->jsonResponse(['error' => 'يجب تسجيل الدخول'], 401);
        }
        if (!BarcodeBridge::push($userId, $code)) {
            $this->jsonResponse(['error' => 'فشل حفظ الرمز'], 500);
        }
        $this->jsonResponse(['success' => true, 'message' => 'تم إرسال الرمز إلى الحاسوب']);
    }

    /**
     * GET /api/barcode-last — من الحاسوب: استلام آخر باركود أرسله الجوال (يُستهلك مرة واحدة).
     */
    public function barcodeLast(): void
    {
        AuthHelper::requireAuth();
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        if ($userId < 1) {
            $this->jsonResponse(['barcode' => null]);
        }
        $result = BarcodeBridge::consumeLast($userId);
        if ($result === null) {
            $this->jsonResponse(['barcode' => null]);
        }
        $this->jsonResponse(['barcode' => $result['barcode'], 'time' => $result['time']]);
    }
}

