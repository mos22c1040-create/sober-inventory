<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Helpers\AuthHelper;
use App\Helpers\RateLimiter;
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
        $basePath  = rtrim((string)($_ENV['APP_SUBDIR'] ?? getenv('APP_SUBDIR') ?: ''), '/');
        $expired   = isset($_GET['expired']) && $_GET['expired'] === '1';

        $this->view('auth/login', [
            'csrfToken' => $csrfToken,
            'basePath'  => $basePath,
            'expired'   => $expired,
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
        // Ensure JSON response only (no accidental HTML / 200 + text/html)
        while (ob_get_level()) {
            ob_end_clean();
        }
        header('Content-Type: application/json; charset=UTF-8');

        $sendError = function (string $message, int $code = 500): void {
            $this->jsonResponse(['error' => $message], $code);
        };

        try {
            $this->loginHandler($sendError);
        } catch (\Throwable $e) {
            $isDev = ($_ENV['APP_ENV'] ?? getenv('APP_ENV') ?: '') !== 'production';
            $msg   = $isDev && $e->getMessage()
                ? 'خطأ في الخادم: ' . $e->getMessage()
                : 'خطأ في الخادم. حاول مرة أخرى لاحقاً.';
            $sendError($msg, 500);
        }
    }

    /**
     * Core login logic.
     *
     * @param  callable $sendError function (string $message, int $code): void
     */
    private function loginHandler(callable $sendError): void
    {
        $validated = $this->validateLoginRequest($sendError);
        if ($validated === null) {
            // validateLoginRequest already sent the appropriate JSON error
            return;
        }

        $email          = $validated['email'];
        $password       = $validated['password'];
        $rateLimiterKey = $validated['rateLimiterKey'];

        try {
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

            $storedHash = trim((string) ($user['password'] ?? ''));
            if ($user === false || $storedHash === '' || !Security::verifyPassword($password, $storedHash)) {
                $sendError('البريد الإلكتروني أو كلمة المرور غير صحيحة.', 401);
                return;
            }

            if ($user['status'] !== 'active') {
                $sendError('حسابك موقوف حالياً. تواصل مع المدير.', 403);
                return;
            }

            // session_regenerate_id(true) can fail with PgBouncer/pooler session handlers
            try {
                session_regenerate_id(true);
            } catch (\Throwable $e) {
                $oldData = $_SESSION;
                session_destroy();
                session_start();
                $_SESSION = $oldData;
            }

            $_SESSION['user_id']       = (int)    $user['id'];
            $_SESSION['username']      = (string) $user['username'];
            $_SESSION['name']          = (string) $user['username'];
            $_SESSION['email']         = (string) $user['email'];
            $_SESSION['role']          = (string) $user['role'];
            $_SESSION['last_activity'] = time();

            unset($_SESSION['csrf_token']);
            Security::generateCsrfToken();

            RateLimiter::clear($rateLimiterKey);

            ActivityLog::log('login');

            // Persist session before sending response so redirect to /dashboard sees the session
            session_write_close();

            $this->jsonResponse([
                'success'  => true,
                'message'  => 'تم تسجيل الدخول بنجاح.',
                'redirect' => '/dashboard',
            ], 200);
        } catch (\Throwable $e) {
            $isDev = ($_ENV['APP_ENV'] ?? getenv('APP_ENV') ?: '') !== 'production';
            $msg   = $isDev && $e->getMessage()
                ? 'خطأ في الخادم: ' . $e->getMessage()
                : 'خطأ في الخادم. حاول مرة أخرى لاحقاً.';
            $sendError($msg, 500);
        }
    }

    /**
     * Validate and normalise login request input.
     *
     * Centralises:
     * - Method guard (POST only)
     * - JSON parsing
     * - Presence checks
     * - IP / rate-limiting key derivation
     * - E-mail format validation + sanitisation
     *
     * NOTE: CSRF check remains optional for /api/login and is currently disabled
     * for compatibility with some hosting setups. Can be re-enabled via env flag.
     *
     * @param  callable $sendError function (string $message, int $code): void
     * @return array{
     *     email: string,
     *     password: string,
     *     rateLimiterKey: string
     * }|null
     */
    private function validateLoginRequest(callable $sendError): ?array
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $sendError('طريقة الطلب غير مسموح بها.', 405);
            return null;
        }

        $raw   = file_get_contents('php://input');
        $input = json_decode($raw, true);

        // Flutter Web / بعض البروكسيات قد لا تمرّر JSON كما يتوقع PHP؛ ندعم $_POST كبديل
        if (!is_array($input) && !empty($_POST)) {
            $input = $_POST;
        }

        if (!is_array($input)) {
            $sendError('جسم الطلب غير صالح (أرسل JSON: email, password).', 400);
            return null;
        }

        $email    = trim((string) ($input['email'] ?? ''));
        $password = (string) ($input['password'] ?? '');

        if ($email === '' || $password === '') {
            $sendError('البريد الإلكتروني وكلمة المرور مطلوبان.', 400);
            return null;
        }

        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $ip = trim(explode(',', (string) $ip)[0]);

        $rlKey = 'login_' . $ip;
        if (!RateLimiter::attempt($rlKey, 5, 900)) {
            $wait = RateLimiter::retryAfter($rlKey, 900);
            $min  = (int) ceil($wait / 60);
            $sendError('تجاوزت الحد المسموح به. حاول مجدداً بعد ' . $min . ' دقيقة.', 429);
            return null;
        }

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $sendError('صيغة البريد الإلكتروني غير صحيحة.', 400);
            return null;
        }

        // لا نستخدم sanitizeString على الإيميل قبل الاستعلام من DB (يجب مطابقة القيمة المخزنة كما هي)

        // Optional CSRF for /api/login (disabled by default for hosting compatibility)
        $csrfRequired = (($_ENV['LOGIN_CSRF_REQUIRED'] ?? getenv('LOGIN_CSRF_REQUIRED') ?: 'false') === 'true');
        if ($csrfRequired) {
            $csrfToken = (string) ($input['csrf_token'] ?? '');
            if ($csrfToken === '' || !Security::validateCsrfToken($csrfToken)) {
                $sendError('رمز CSRF غير صالح.', 403);
                return null;
            }
        }

        return [
            'email'          => $email,
            'password'       => $password,
            'rateLimiterKey' => $rlKey,
        ];
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
