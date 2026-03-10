<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\AuthHelper;
use App\Helpers\Security;
use App\Models\Category;

class CategoryController extends Controller
{
    public function index(): void
    {
        AuthHelper::requireAuth();
        $categories = Category::all();
        $this->view('categories/index', [
            'title' => 'التصنيفات',
            'categories' => $categories,
            'csrfToken' => Security::generateCsrfToken(),
        ]);
    }

    public function create(): void
    {
        AuthHelper::requireRole('admin');
        $this->view('categories/form', [
            'title' => 'إضافة تصنيف',
            'category' => null,
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
            $this->jsonResponse(['error' => 'Category name is required'], 400);
        }
        $data = [
            'name' => Security::sanitizeString($name),
            'description' => isset($input['description']) ? Security::sanitizeString($input['description']) : null,
        ];
        $id = Category::create($data);
        $this->jsonResponse(['success' => true, 'id' => $id, 'redirect' => '/categories'], 201);
    }

    public function edit(): void
    {
        AuthHelper::requireRole('admin');
        $id = (int) ($_GET['id'] ?? 0);
        $category = $id ? Category::find($id) : null;
        if (!$category) {
            http_response_code(404);
            require BASE_PATH . '/views/404.php';
            return;
        }
        $this->view('categories/form', [
            'title' => 'تعديل التصنيف',
            'category' => $category,
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
        $category = $id ? Category::find($id) : null;
        if (!$category) {
            $this->jsonResponse(['error' => 'Category not found'], 404);
        }
        $name = trim($input['name'] ?? '');
        if ($name === '') {
            $this->jsonResponse(['error' => 'Category name is required'], 400);
        }
        $data = [
            'name' => Security::sanitizeString($name),
            'description' => isset($input['description']) ? Security::sanitizeString($input['description']) : null,
        ];
        Category::update($id, $data);
        $this->jsonResponse(['success' => true, 'redirect' => '/categories']);
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
        Category::delete($id);
        $this->jsonResponse(['success' => true]);
    }
}
