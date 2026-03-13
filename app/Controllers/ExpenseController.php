<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\AuthHelper;
use App\Helpers\Security;
use App\Models\ActivityLog;
use App\Models\Expense;

class ExpenseController extends Controller
{
    public function index(): void
    {
        AuthHelper::requireRole('admin');
        $page   = max(1, (int)($_GET['page'] ?? 1));

        try {
            $result       = Expense::paginate($page, 25);
            $summary      = Expense::summaryByCategory();
            $monthlyTotal = Expense::monthlyTotal();
            $needsMigration = false;
        } catch (\PDOException $e) {
            $result       = ['data' => [], 'total' => 0, 'page' => 1, 'perPage' => 25, 'pages' => 1];
            $summary      = [];
            $monthlyTotal = 0.0;
            $needsMigration = true;
        }

        $this->view('expenses/index', [
            'title'           => 'المصروفات',
            'expenses'        => $result['data'],
            'pagination'      => $result,
            'summary'         => $summary,
            'monthlyTotal'    => $monthlyTotal,
            'csrfToken'       => Security::generateCsrfToken(),
            'needsMigration'  => $needsMigration,
        ]);
    }

    /** GET /api/expenses/list — JSON for mobile (admin only) */
    public function indexApi(): void
    {
        AuthHelper::requireRole('admin');
        $page    = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = max(1, min(50, (int) ($_GET['per_page'] ?? 25)));
        try {
            $result       = Expense::paginate($page, $perPage);
            $monthlyTotal = Expense::monthlyTotal();
        } catch (\PDOException $e) {
            $result       = ['data' => [], 'total' => 0, 'page' => 1, 'perPage' => $perPage, 'pages' => 0];
            $monthlyTotal = 0.0;
        }
        $this->jsonResponse([
            'data'          => $result['data'],
            'page'          => $result['page'],
            'pages'         => $result['pages'],
            'total'         => $result['total'],
            'monthly_total' => $monthlyTotal,
        ]);
    }

    public function create(): void
    {
        AuthHelper::requireRole('admin');
        $this->view('expenses/form', [
            'title'      => 'إضافة مصروف',
            'expense'    => null,
            'csrfToken'  => Security::generateCsrfToken(),
            'categories' => Expense::CATEGORIES,
        ]);
    }

    public function edit(): void
    {
        AuthHelper::requireRole('admin');
        $id      = (int)($_GET['id'] ?? 0);
        $expense = Expense::find($id);
        if (!$expense) {
            header('Location: /expenses');
            exit;
        }
        $this->view('expenses/form', [
            'title'      => 'تعديل مصروف',
            'expense'    => $expense,
            'csrfToken'  => Security::generateCsrfToken(),
            'categories' => Expense::CATEGORIES,
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
            $this->jsonResponse(['error' => 'CSRF token invalid'], 403);
        }

        $amount = (float)($input['amount'] ?? 0);
        if ($amount <= 0) {
            $this->jsonResponse(['error' => 'المبلغ يجب أن يكون أكبر من صفر'], 422);
        }

        $data = [
            'amount'       => $amount,
            'category'     => Security::sanitizeString($input['category'] ?? 'أخرى'),
            'description'  => Security::sanitizeString($input['description'] ?? ''),
            'expense_date' => $input['expense_date'] ?? date('Y-m-d'),
        ];

        $userId = (int)$_SESSION['user_id'];
        $id = Expense::create($userId, $data);
        ActivityLog::log('expense.create', 'expense', $id, $data['category'] . ': ' . $amount);
        $this->jsonResponse(['success' => true, 'id' => $id, 'redirect' => '/expenses'], 201);
    }

    public function update(): void
    {
        AuthHelper::requireRole('admin');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Method not allowed'], 405);
        }
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        if (!Security::validateCsrfToken($input['csrf_token'] ?? '')) {
            $this->jsonResponse(['error' => 'CSRF token invalid'], 403);
        }

        $id     = (int)($input['id'] ?? 0);
        $amount = (float)($input['amount'] ?? 0);
        if ($id <= 0 || $amount <= 0) {
            $this->jsonResponse(['error' => 'بيانات غير صالحة'], 422);
        }

        $data = [
            'amount'       => $amount,
            'category'     => Security::sanitizeString($input['category'] ?? 'أخرى'),
            'description'  => Security::sanitizeString($input['description'] ?? ''),
            'expense_date' => $input['expense_date'] ?? date('Y-m-d'),
        ];

        Expense::update($id, $data);
        ActivityLog::log('expense.update', 'expense', $id, $data['category']);
        $this->jsonResponse(['success' => true, 'redirect' => '/expenses']);
    }

    public function delete(): void
    {
        AuthHelper::requireRole('admin');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Method not allowed'], 405);
        }
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        if (!Security::validateCsrfToken($input['csrf_token'] ?? '')) {
            $this->jsonResponse(['error' => 'CSRF token invalid'], 403);
        }

        $id = (int)($input['id'] ?? 0);
        if ($id <= 0) {
            $this->jsonResponse(['error' => 'معرّف غير صالح'], 422);
        }
        Expense::delete($id);
        ActivityLog::log('expense.delete', 'expense', $id, '');
        $this->jsonResponse(['success' => true]);
    }
}
