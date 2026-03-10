<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Helpers\AuthHelper;
use App\Helpers\Security;
use App\Models\ActivityLog;

/**
 * AuthController — handles authentication lifecycle.
 *
 * Covers: login page display, AJAX login, and logout.
 * PSR-12 compliant. All inputs validated and sanitised before use.
 */
class AuthController extends Controller
{
    // -------------------------------------------------------------------------
    // GET /login
    // -------------------------------------------------------------------------

    /**
     * Render the login page.
     * Redirects to /dashboard if a valid session already exists.
     */
    public function showLogin(): void
    {
        // Skip login screen when already authenticated
        if (isset($_SESSION['user_id'])) {
            header('Location: /dashboard');
            exit;
        }

        $csrfToken = Security::generateCsrfToken();

        $this->view('auth/login', [
            'csrfToken' => $csrfToken,
        ]);
    }

    // -------------------------------------------------------------------------
    // POST /api/login  (Fetch API — JSON in / JSON out)
    // -------------------------------------------------------------------------

    /**
     * Authenticate a user via JSON POST.
     *
     * Accepts: { email, password, csrf_token }
     * Returns: JSON success (200) or JSON error (400 / 401 / 403 / 405)
     */
    public function login(): void
    {
        $sendError = function (string $message, int $code = 500): void {
            $this->jsonResponse(['error' => $message], $code);
        };

        // --- Method guard -------------------------------------------------------
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $sendError('طريقة الطلب غير مسموح بها.', 405);
            return;
        }

        // --- Parse JSON body ----------------------------------------------------
        $raw   = file_get_contents('php://input');
        $input = json_decode($raw, true);

        if (!is_array($input)) {
            $sendError('جسم الطلب غير صالح (يُتوقع JSON).', 400);
            return;
        }

        // --- Extract & type-cast fields ----------------------------------------
        $email     = trim((string) ($input['email']      ?? ''));
        $password  =       (string) ($input['password']   ?? '');
        $csrfToken =       (string) ($input['csrf_token'] ?? '');

        // --- CSRF validation ----------------------------------------------------
        if (!Security::validateCsrfToken($csrfToken)) {
            $sendError('رمز الأمان (CSRF) منتهٍ. أعِد تحميل الصفحة وحاول مجدداً.', 403);
            return;
        }

        // --- Input presence check -----------------------------------------------
        if ($email === '' || $password === '') {
            $sendError('البريد الإلكتروني وكلمة المرور مطلوبان.', 400);
            return;
        }

        // --- Email format validation --------------------------------------------
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $sendError('صيغة البريد الإلكتروني غير صحيحة.', 400);
            return;
        }

        // Sanitise the email (display-safe; password must NOT be sanitised before verify)
        $email = Security::sanitizeString($email);

        try {
            // --- Fetch user via Prepared Statement (SQL-injection safe) -------------
            $db   = Database::getInstance();
            $stmt = $db->query(
                'SELECT id, username, email, password, role, status
                   FROM users
                  WHERE email = :email
                  LIMIT 1',
                [':email' => $email]
            );

            /** @var array<string,mixed>|false $user */
            $user = $stmt->fetch();

            // --- Verify password with bcrypt (constant-time, timing-attack-safe) ---
            $storedHash = trim((string) ($user['password'] ?? ''));
            if ($user === false || $storedHash === '' || !Security::verifyPassword($password, $storedHash)) {
                $sendError('البريد الإلكتروني أو كلمة المرور غير صحيحة.', 401);
                return;
            }

            // --- Account status check -----------------------------------------------
            if ($user['status'] !== 'active') {
                $sendError('حسابك موقوف حالياً. تواصل مع المدير.', 403);
                return;
            }

            // --- Build secure session -----------------------------------------------
            session_regenerate_id(true);

            $_SESSION['user_id']       = (int)    $user['id'];
            $_SESSION['username']      = (string) $user['username'];
            $_SESSION['name']          = (string) $user['username'];
            $_SESSION['email']         = (string) $user['email'];
            $_SESSION['role']          = (string) $user['role'];
            $_SESSION['last_activity'] = time();

            unset($_SESSION['csrf_token']);
            Security::generateCsrfToken();

            ActivityLog::log('login', null, null, (string) $user['email']);

            $this->jsonResponse([
                'success'  => true,
                'message'  => 'تم تسجيل الدخول بنجاح.',
                'redirect' => '/dashboard',
            ], 200);
        } catch (\Throwable $e) {
            $isDev = ($_ENV['APP_ENV'] ?? '') !== 'production';
            $msg   = $isDev && $e->getMessage()
                ? 'خطأ في الخادم: ' . $e->getMessage()
                : 'خطأ في الخادم. حاول مرة أخرى لاحقاً.';
            $sendError($msg, 500);
        }
    }

    // -------------------------------------------------------------------------
    // POST /api/logout
    // -------------------------------------------------------------------------

    /**
     * Destroy the session and redirect to /login.
     */
    public function logout(): void
    {
        if (isset($_SESSION['user_id'])) {
            ActivityLog::log('logout');
        }
        // Wipe all session data
        $_SESSION = [];

        // Expire the session cookie in the browser
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();

        header('Location: /login');
        exit;
    }
}
