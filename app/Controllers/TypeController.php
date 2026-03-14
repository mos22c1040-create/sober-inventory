<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\AuthHelper;
use App\Helpers\Security;
use App\Models\Type;

class TypeController extends Controller
{
    public function index(): void
    {
        AuthHelper::requireAuth();
        $types = Type::all();
        $this->view('types/index', [
            'title'     => 'الأنواع',
            'types'     => $types,
            'csrfToken' => Security::generateCsrfToken(),
        ]);
    }

    /** GET /api/types — JSON for mobile */
    public function indexApi(): void
    {
        AuthHelper::requireAuth();
        $this->jsonResponse(['data' => Type::all()]);
    }

    public function create(): void
    {
        AuthHelper::requireRole('admin');
        $this->view('types/form', [
            'title'     => 'إضافة نوع',
            'type'      => null,
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
        $name = trim($input['name'] ?? '');
        if ($name === '') {
            $this->jsonResponse(['error' => 'اسم النوع مطلوب'], 400);
        }
        $data = [
            'name'        => Security::sanitizeString($name),
            'description' => isset($input['description']) ? Security::sanitizeString($input['description']) : null,
        ];
        $id = Type::create($data);
        $this->jsonResponse(['success' => true, 'id' => $id, 'redirect' => '/types'], 201);
    }

    public function edit(): void
    {
        AuthHelper::requireRole('admin');
        $id   = (int) ($_GET['id'] ?? 0);
        $type = $id ? Type::find($id) : null;
        if (!$type) {
            http_response_code(404);
            require BASE_PATH . '/views/404.php';
            return;
        }
        $this->view('types/form', [
            'title'     => 'تعديل النوع',
            'type'      => $type,
            'csrfToken' => Security::generateCsrfToken(),
        ]);
    }

    public function update(): void
    {
        AuthHelper::requireRole('admin');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Method not allowed'], 405);
        }
        $input  = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        if (!Security::validateCsrfToken($input['csrf_token'] ?? '')) {
            $this->jsonResponse(['error' => 'Invalid CSRF token'], 403);
        }
        $id   = (int) ($input['id'] ?? 0);
        $type = $id ? Type::find($id) : null;
        if (!$type) {
            $this->jsonResponse(['error' => 'النوع غير موجود'], 404);
        }
        $name = trim($input['name'] ?? '');
        if ($name === '') {
            $this->jsonResponse(['error' => 'اسم النوع مطلوب'], 400);
        }
        $data = [
            'name'        => Security::sanitizeString($name),
            'description' => isset($input['description']) ? Security::sanitizeString($input['description']) : null,
        ];
        Type::update($id, $data);
        $this->jsonResponse(['success' => true, 'redirect' => '/types']);
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
            $this->jsonResponse(['error' => 'معرف غير صالح'], 400);
        }
        Type::delete($id);
        $this->jsonResponse(['success' => true]);
    }
}
