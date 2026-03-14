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
use App\Models\Type;
use App\Validators\ProductValidator;

class ProductController extends Controller
{
    public function index(): void
    {
        AuthHelper::requireAuth();
        $page   = max(1, (int) ($_GET['page'] ?? 1));
        $stock  = (string) ($_GET['stock'] ?? 'all');
        $allowed = ['all', 'in_stock', 'low', 'out'];
        if (!in_array($stock, $allowed, true)) {
            $stock = 'all';
        }
        $paginated = Product::paginateByStockFilter($page, 20, $stock);
        $this->view('products/index', [
            'title'       => 'المنتجات',
            'products'    => $paginated['data'],
            'pagination'  => $paginated,
            'stockFilter' => $stock,
            'csrfToken'   => Security::generateCsrfToken(),
        ]);
    }

    /**
     * GET /api/products — JSON endpoint for mobile/SPA clients.
     *
     * Query params:
     * - page     (int, optional, default 1)
     * - per_page (int, optional, default 20, max 100)
     */
    public function indexApi(): void
    {
        AuthHelper::requireAuth();

        $page    = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = (int) ($_GET['per_page'] ?? 20);

        if ($perPage < 1) {
            $perPage = 20;
        } elseif ($perPage > 100) {
            $perPage = 100;
        }

        $paginated = Product::paginate($page, $perPage);

        $this->jsonResponse([
            'data'    => $paginated['data'],
            'page'    => $paginated['page'],
            'pages'   => $paginated['pages'],
            'perPage' => $paginated['perPage'],
            'total'   => $paginated['total'],
        ]);
    }

    /** GET /api/products/low-stock — منتجات منخفضة المخزون (للإشعارات والتقارير) */
    public function lowStockApi(): void
    {
        AuthHelper::requireAuth();
        $limit = max(1, min(100, (int) ($_GET['limit'] ?? 30)));
        $this->jsonResponse(['data' => Product::getLowStockProducts($limit)]);
    }

    public function create(): void
    {
        AuthHelper::requireRole('admin');
        $categories = Category::all();
        $types     = Type::all();
        $this->view('products/form', [
            'title'      => 'إضافة منتج',
            'product'    => null,
            'categories' => $categories,
            'types'      => $types,
            'csrfToken'  => Security::generateCsrfToken(),
        ]);
    }

    public function store(): void
    {
        AuthHelper::requireRole('admin');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Method not allowed'], 405);
        }
        $input = $this->normalizeProductInput([]);
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
        $types      = Type::all();
        $this->view('products/form', [
            'title'      => 'تعديل المنتج',
            'product'    => $product,
            'categories' => $categories,
            'types'      => $types,
            'csrfToken'  => Security::generateCsrfToken(),
        ]);
    }

    public function update(): void
    {
        AuthHelper::requireRole('admin');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Method not allowed'], 405);
        }
        $id = (int) ($_POST['id'] ?? json_decode((string) file_get_contents('php://input'), true)['id'] ?? 0);
        $product = $id ? Product::find($id) : null;
        if (!$product) {
            $this->jsonResponse(['error' => 'Product not found'], 404);
        }
        $input = $this->normalizeProductInput($product);
        if (!Security::validateCsrfToken($input['csrf_token'] ?? '')) {
            $this->jsonResponse(['error' => 'Invalid CSRF token'], 403);
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
        $product = Product::find($id);
        if (!$product) {
            $this->jsonResponse(['error' => 'Product not found'], 404);
        }
        $name = $product['name'] ?? '';
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
            $this->jsonResponse(['data' => []]);
            return;
        }
        $results = Product::search($q, 12);
        $this->jsonResponse(['data' => $results]);
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

    /**
     * Normalize product form input: support JSON body or multipart/form-data with optional image.
     * When multipart and a new image is uploaded, saves it under public/uploads/products/ and sets image path.
     * For update, when no new file is sent, preserves existing product image.
     *
     * @param array<string,mixed> $existingProduct Current product row for update (empty for create)
     * @return array<string,mixed>
     */
    private function normalizeProductInput(array $existingProduct): array
    {
        $isMultipart = isset($_SERVER['CONTENT_TYPE'])
            && str_starts_with((string) $_SERVER['CONTENT_TYPE'], 'multipart/form-data');

        if ($isMultipart) {
            $input = $_POST;
            $uploadPath = $this->handleProductImageUpload($existingProduct['image'] ?? null);
            if ($uploadPath !== null) {
                $input['image'] = $uploadPath;
            } elseif (!empty($existingProduct['image'])) {
                $input['image'] = $existingProduct['image'];
            }
            return $input;
        }

        $input = json_decode((string) file_get_contents('php://input'), true) ?? [];
        return is_array($input) ? $input : [];
    }

    /**
     * If a new image was uploaded via $_FILES['image'], validate, save to public/uploads/products/, return path.
     * Otherwise return null.
     */
    private function handleProductImageUpload(?string $existingPath): ?string
    {
        if (empty($_FILES['image']['tmp_name']) || !is_uploaded_file($_FILES['image']['tmp_name'])) {
            return null;
        }
        $file = $_FILES['image'];
        $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo ? (finfo_file($finfo, $file['tmp_name']) ?: '') : '';
        if ($finfo) {
            finfo_close($finfo);
        }
        if (!in_array($mime, $allowed, true)) {
            return null;
        }
        $maxSize = 5 * 1024 * 1024; // 5 MB
        if ($file['size'] > $maxSize) {
            return null;
        }
        $dir = BASE_PATH . '/public/uploads/products';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $ext = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            default => 'jpg',
        };
        $name = 'p_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $path = $dir . '/' . $name;
        if (!move_uploaded_file($file['tmp_name'], $path)) {
            return null;
        }
        return 'uploads/products/' . $name;
    }
}

