<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\AuthHelper;
use App\Helpers\Security;
use App\Models\User;

class ProfileController extends Controller
{
    public function index(): void
    {
        AuthHelper::checkAuth();
        $userId = AuthHelper::userId();
        $user   = $userId ? User::find($userId) : null;
        if (!$user) {
            header('Location: /login');
            exit;
        }

        $this->view('profile/index', [
            'title'     => 'حسابي',
            'user'      => $user,
            'csrfToken' => Security::generateCsrfToken(),
        ]);
    }

    /** POST /api/profile/password — تغيير كلمة مرور المستخدم الحالي */
    public function updatePassword(): void
    {
        AuthHelper::checkAuth();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'طريقة الطلب غير مسموح بها.'], 405);
        }

        $input   = json_decode(file_get_contents('php://input'), true) ?? [];
        $current = (string) ($input['current_password'] ?? '');
        $new     = trim((string) ($input['new_password'] ?? ''));
        $confirm = trim((string) ($input['confirm_password'] ?? ''));

        if (!Security::validateCsrfToken((string) ($input['csrf_token'] ?? ''))) {
            $this->jsonResponse(['error' => 'رمز الأمان منتهٍ. أعِد تحميل الصفحة.'], 403);
        }

        $userId = AuthHelper::userId();
        if (!$userId) {
            $this->jsonResponse(['error' => 'يجب تسجيل الدخول.'], 401);
        }

        if (!User::verifyPasswordFor($userId, $current)) {
            $this->jsonResponse(['error' => 'كلمة المرور الحالية غير صحيحة.'], 400);
        }

        if (strlen($new) < 6) {
            $this->jsonResponse(['error' => 'كلمة المرور الجديدة يجب أن تكون 6 أحرف على الأقل.'], 422);
        }

        if ($new !== $confirm) {
            $this->jsonResponse(['error' => 'كلمة المرور الجديدة وتأكيدها غير متطابقين.'], 422);
        }

        User::changePassword($userId, $new);

        $this->jsonResponse(['success' => true, 'message' => 'تم تغيير كلمة المرور بنجاح.']);
    }
}
