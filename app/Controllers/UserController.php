<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\AuthHelper;
use App\Helpers\Security;
use App\Models\ActivityLog;
use App\Models\User;

/**
 * UserController — full user management (Admin only).
 *
 * Routes:
 *   GET  /users                → index()
 *   GET  /users/create         → create()
 *   POST /api/users            → store()
 *   GET  /users/edit?id=N      → edit()
 *   POST /api/users/update     → update()
 *   POST /api/users/password   → changePassword()
 *   POST /api/users/delete     → delete()
 */
class UserController extends Controller
{
    // -------------------------------------------------------------------------
    // GET /users
    // -------------------------------------------------------------------------

    public function index(): void
    {
        AuthHelper::checkAuth();
        AuthHelper::requireRole('admin');

        $users = User::all();

        $this->view('users/index', [
            'title'     => 'إدارة المستخدمين',
            'users'     => $users,
            'csrfToken' => Security::generateCsrfToken(),
        ]);
    }

    // -------------------------------------------------------------------------
    // GET /users/create
    // -------------------------------------------------------------------------

    public function create(): void
    {
        AuthHelper::checkAuth();
        AuthHelper::requireRole('admin');

        $this->view('users/form', [
            'title'     => 'إضافة مستخدم جديد',
            'user'      => null,
            'csrfToken' => Security::generateCsrfToken(),
        ]);
    }

    // -------------------------------------------------------------------------
    // POST /api/users
    // -------------------------------------------------------------------------

    public function store(): void
    {
        AuthHelper::checkAuth();
        AuthHelper::requireRole('admin');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'طريقة الطلب غير مسموح بها.'], 405);
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? [];

        if (!Security::validateCsrfToken((string) ($input['csrf_token'] ?? ''))) {
            $this->jsonResponse(['error' => 'رمز الأمان منتهٍ. أعِد تحميل الصفحة.'], 403);
        }

        [$data, $error] = $this->validateUserInput($input, null);
        if ($error) {
            $this->jsonResponse(['error' => $error], 422);
        }

        // Password required on create
        $password = trim((string) ($input['password'] ?? ''));
        if (strlen($password) < 6) {
            $this->jsonResponse(['error' => 'كلمة المرور يجب أن تكون 6 أحرف على الأقل.'], 422);
        }

        // Unique email check
        if (User::findByEmail($data['email'])) {
            $this->jsonResponse(['error' => 'هذا البريد الإلكتروني مستخدم من قبل.'], 409);
        }

        $data['password'] = $password;
        $id = User::create($data);
        ActivityLog::log('user.create', 'user', $id, $data['username']);

        $this->jsonResponse([
            'success'  => true,
            'id'       => $id,
            'message'  => 'تم إنشاء المستخدم بنجاح.',
            'redirect' => '/users',
        ], 201);
    }

    // -------------------------------------------------------------------------
    // GET /users/edit?id=N
    // -------------------------------------------------------------------------

    public function edit(): void
    {
        AuthHelper::checkAuth();
        AuthHelper::requireRole('admin');

        $id   = (int) ($_GET['id'] ?? 0);
        $user = $id ? User::find($id) : null;

        if (!$user) {
            http_response_code(404);
            require BASE_PATH . '/views/404.php';
            return;
        }

        $this->view('users/form', [
            'title'     => 'تعديل المستخدم',
            'user'      => $user,
            'csrfToken' => Security::generateCsrfToken(),
        ]);
    }

    // -------------------------------------------------------------------------
    // POST /api/users/update
    // -------------------------------------------------------------------------

    public function update(): void
    {
        AuthHelper::checkAuth();
        AuthHelper::requireRole('admin');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'طريقة الطلب غير مسموح بها.'], 405);
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? [];

        if (!Security::validateCsrfToken((string) ($input['csrf_token'] ?? ''))) {
            $this->jsonResponse(['error' => 'رمز الأمان منتهٍ.'], 403);
        }

        $id   = (int) ($input['id'] ?? 0);
        $user = $id ? User::find($id) : null;

        if (!$user) {
            $this->jsonResponse(['error' => 'المستخدم غير موجود.'], 404);
        }

        [$data, $error] = $this->validateUserInput($input, $id);
        if ($error) {
            $this->jsonResponse(['error' => $error], 422);
        }

        // Prevent removing the last active admin
        if (
            $user['role'] === 'admin'
            && ($data['role'] !== 'admin' || $data['status'] !== 'active')
            && User::countActiveAdmins() <= 1
        ) {
            $this->jsonResponse([
                'error' => 'لا يمكن تغيير صلاحية أو تعطيل آخر مدير في النظام.',
            ], 409);
        }

        User::update($id, $data);
        ActivityLog::log('user.update', 'user', $id, $data['username']);

        $this->jsonResponse([
            'success'  => true,
            'message'  => 'تم تحديث بيانات المستخدم.',
            'redirect' => '/users',
        ]);
    }

    // -------------------------------------------------------------------------
    // POST /api/users/password
    // -------------------------------------------------------------------------

    public function changePassword(): void
    {
        AuthHelper::checkAuth();
        AuthHelper::requireRole('admin');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'طريقة الطلب غير مسموح بها.'], 405);
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? [];

        if (!Security::validateCsrfToken((string) ($input['csrf_token'] ?? ''))) {
            $this->jsonResponse(['error' => 'رمز الأمان منتهٍ.'], 403);
        }

        $id       = (int) ($input['id'] ?? 0);
        $password = trim((string) ($input['password'] ?? ''));
        $confirm  = trim((string) ($input['confirm']  ?? ''));

        if (!User::find($id)) {
            $this->jsonResponse(['error' => 'المستخدم غير موجود.'], 404);
        }

        if (strlen($password) < 6) {
            $this->jsonResponse(['error' => 'كلمة المرور يجب أن تكون 6 أحرف على الأقل.'], 422);
        }

        if ($password !== $confirm) {
            $this->jsonResponse(['error' => 'كلمة المرور وتأكيدها غير متطابقين.'], 422);
        }

        User::changePassword($id, $password);
        ActivityLog::log('user.password', 'user', $id);

        $this->jsonResponse([
            'success' => true,
            'message' => 'تم تغيير كلمة المرور بنجاح.',
        ]);
    }

    // -------------------------------------------------------------------------
    // POST /api/users/delete
    // -------------------------------------------------------------------------

    public function delete(): void
    {
        AuthHelper::checkAuth();
        AuthHelper::requireRole('admin');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'طريقة الطلب غير مسموح بها.'], 405);
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? [];

        if (!Security::validateCsrfToken((string) ($input['csrf_token'] ?? ''))) {
            $this->jsonResponse(['error' => 'رمز الأمان منتهٍ.'], 403);
        }

        $id = (int) ($input['id'] ?? 0);

        if (!$id) {
            $this->jsonResponse(['error' => 'معرّف غير صالح.'], 400);
        }

        // Prevent self-deletion
        if ($id === (int) ($_SESSION['user_id'] ?? 0)) {
            $this->jsonResponse(['error' => 'لا يمكنك حذف حسابك الخاص.'], 409);
        }

        $user = User::find($id);
        if (!$user) {
            $this->jsonResponse(['error' => 'المستخدم غير موجود.'], 404);
        }

        // Protect last admin
        if ($user['role'] === 'admin' && User::countActiveAdmins() <= 1) {
            $this->jsonResponse([
                'error' => 'لا يمكن حذف آخر مدير في النظام.',
            ], 409);
        }

        $username = $user['username'] ?? '';
        User::delete($id);
        ActivityLog::log('user.delete', 'user', $id, $username);

        $this->jsonResponse(['success' => true, 'message' => 'تم حذف المستخدم.']);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Validate and sanitise shared user fields.
     *
     * @return array{0: array, 1: string|null}  [data, errorMessage|null]
     */
    private function validateUserInput(array $input, ?int $excludeId): array
    {
        $username = trim(Security::sanitizeString((string) ($input['username'] ?? '')));
        $email    = trim((string) ($input['email'] ?? ''));
        $role     = (string) ($input['role']   ?? 'cashier');
        $status   = (string) ($input['status'] ?? 'active');

        if ($username === '') {
            return [[], 'اسم المستخدم مطلوب.'];
        }

        if (strlen($username) < 3) {
            return [[], 'اسم المستخدم يجب أن يكون 3 أحرف على الأقل.'];
        }

        $cleanEmail = Security::sanitizeEmail($email);
        if ($cleanEmail === null) {
            return [[], 'صيغة البريد الإلكتروني غير صحيحة.'];
        }

        if (!in_array($role, ['admin', 'cashier'], true)) {
            $role = 'cashier';
        }

        if (!in_array($status, ['active', 'inactive'], true)) {
            $status = 'active';
        }

        return [[
            'username' => $username,
            'email'    => $cleanEmail,
            'role'     => $role,
            'status'   => $status,
        ], null];
    }
}
