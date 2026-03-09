<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * AuthHelper — session management and route protection middleware.
 *
 * Usage in any protected controller:
 *   AuthHelper::checkAuth();                    // require any logged-in user
 *   AuthHelper::checkAuth();
 *   AuthHelper::hasRole('admin');               // require admin role
 *
 * PSR-12 compliant.
 */
class AuthHelper
{
    // -------------------------------------------------------------------------
    // Session bootstrap
    // -------------------------------------------------------------------------

    /**
     * Start a secure PHP session (once per request).
     *
     * Hardened cookie flags:
     *  - HttpOnly   → JS cannot read the session cookie
     *  - SameSite   → mitigates CSRF at cookie level
     *  - use_only_cookies → no session IDs in URLs
     */
    public static function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.cookie_httponly', '1');
            ini_set('session.use_only_cookies', '1');
            ini_set('session.cookie_samesite', 'Strict');
            session_start();
        }
    }

    // -------------------------------------------------------------------------
    // Authentication guard  (alias: requireAuth)
    // -------------------------------------------------------------------------

    /**
     * Verify that a valid authenticated session exists.
     *
     * - Redirects to /login when no session is present.
     * - Redirects to /login?expired=1 on session timeout.
     * - Updates the last-activity timestamp on every protected request.
     *
     * Alias for the Step-4 requirement naming: checkAuth().
     */
    public static function checkAuth(): void
    {
        self::startSession();

        // Load session timeout from config (default 1 hour)
        $settings = file_exists(BASE_PATH . '/config/app_settings.php')
            ? (array) include BASE_PATH . '/config/app_settings.php'
            : [];

        $timeout = (int) ($settings['session_lifetime'] ?? 3600);

        // --- No session present -------------------------------------------------
        if (!isset($_SESSION['user_id'])) {
            self::redirectToLogin();
        }

        // --- Session idle timeout -----------------------------------------------
        if (
            isset($_SESSION['last_activity'])
            && (time() - (int) $_SESSION['last_activity']) > $timeout
        ) {
            self::destroySession();
            self::redirectToLogin('expired=1');
        }

        // Refresh activity timestamp on every protected page load
        $_SESSION['last_activity'] = time();
    }

    /**
     * Backward-compatible alias for checkAuth().
     * Existing controllers that call requireAuth() continue to work unchanged.
     */
    public static function requireAuth(): void
    {
        self::checkAuth();
    }

    // -------------------------------------------------------------------------
    // Role-based access control
    // -------------------------------------------------------------------------

    /**
     * Check whether the currently authenticated user holds a given role.
     *
     * Does NOT redirect — returns a boolean so callers can decide the response.
     *
     * Example:
     *   if (!AuthHelper::hasRole('admin')) {
     *       $this->jsonResponse(['error' => 'غير مصرح.'], 403);
     *       return;
     *   }
     *
     * @param  string $role  Role slug to check against (e.g. 'admin', 'cashier')
     * @return bool          true if the current user holds that role
     */
    public static function hasRole(string $role): bool
    {
        // Ensure a session exists before checking it
        self::checkAuth();

        return isset($_SESSION['role'])
            && strtolower((string) $_SESSION['role']) === strtolower($role);
    }

    /**
     * Require a specific role — terminates with 403 JSON if the check fails.
     *
     * Wraps hasRole() for convenience in controllers that need a hard gate:
     *   AuthHelper::requireRole('admin');
     *
     * @param  string $role  Required role slug
     * @return void
     */
    public static function requireRole(string $role): void
    {
        if (!self::hasRole($role)) {
            http_response_code(403);
            $wantsJson = isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false;
            $isXhr = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
            if ($wantsJson || $isXhr) {
                header('Content-Type: application/json');
                echo json_encode([
                    'error' => 'ليس لديك صلاحية الوصول إلى هذا المورد.',
                    'required_role' => htmlspecialchars($role, ENT_QUOTES, 'UTF-8'),
                ]);
                exit;
            }
            if (file_exists(BASE_PATH . '/views/403.php')) {
                require BASE_PATH . '/views/403.php';
                exit;
            }
            header('Content-Type: application/json');
            echo json_encode(['error' => 'ليس لديك صلاحية الوصول إلى هذا المورد.']);
            exit;
        }
    }

    // -------------------------------------------------------------------------
    // Convenience helpers
    // -------------------------------------------------------------------------

    /**
     * Return the authenticated user's ID (or null if not logged in).
     */
    public static function userId(): ?int
    {
        return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    }

    /**
     * Return the authenticated user's display name (or null if not logged in).
     */
    public static function userName(): ?string
    {
        return isset($_SESSION['name']) ? (string) $_SESSION['name'] : null;
    }

    /**
     * Return the authenticated user's role (or null if not logged in).
     */
    public static function userRole(): ?string
    {
        return isset($_SESSION['role']) ? (string) $_SESSION['role'] : null;
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Wipe all session data and destroy the session cookie.
     */
    private static function destroySession(): void
    {
        $_SESSION = [];

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
    }

    /**
     * Send an HTTP redirect to the login page and stop execution.
     *
     * @param string $queryString  Optional query string (without leading '?')
     */
    private static function redirectToLogin(string $queryString = ''): never
    {
        $url = '/login' . ($queryString !== '' ? '?' . $queryString : '');
        header('Location: ' . $url);
        exit;
    }
}
