<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Security — centralised input sanitisation, CSRF protection, password hashing,
 * and HTTP security headers (per backend-security-coder / OWASP).
 *
 * All methods are static; this class is a stateless utility namespace.
 * PSR-12 compliant.
 */
class Security
{
    // -------------------------------------------------------------------------
    // HTTP security headers
    // -------------------------------------------------------------------------

    /**
     * Send security-related HTTP headers on every response.
     *
     * Call once per request (e.g. from front controller) before any output.
     * (Per skill: backend-security-coder — HTTP Security Headers and Cookies.)
     *
     * - X-Content-Type-Options: prevents MIME sniffing
     * - X-Frame-Options: prevents clickjacking
     * - Referrer-Policy: limits referrer leakage
     * - Permissions-Policy: restricts browser features (camera only for self for barcode scan)
     * - HSTS: only in production (HTTPS) to avoid breaking local HTTP dev
     */
    public static function sendSecurityHeaders(): void
    {
        if (headers_sent()) {
            return;
        }

        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        // Restrict browser features: camera allowed for same-origin (barcode scan), others disabled
        header('Permissions-Policy: camera=(self), geolocation=(), microphone=(), payment=()');

        // Content-Security-Policy: restricts resource loading origins to self + trusted CDNs.
        // script-src allows jsdelivr (Chart.js), fonts.googleapis.com, cdnjs (html5-qrcode).
        // style-src allows Google Fonts and inline styles required by Tailwind.
        header(
            "Content-Security-Policy: " .
            "default-src 'self'; " .
            "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://unpkg.com; " .
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com; " .
            "font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com; " .
            "img-src 'self' data: blob:; " .
            "connect-src 'self'; " .
            "frame-ancestors 'none';"
        );

        $env = $_ENV['APP_ENV'] ?? getenv('APP_ENV') ?: '';
        if ($env === 'production') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
        }
    }

    // -------------------------------------------------------------------------
    // Input sanitisation
    // -------------------------------------------------------------------------

    /**
     * Sanitise a string for safe HTML output.
     *
     * Strips HTML tags, trims whitespace, truncates to $maxLen characters,
     * and escapes remaining special chars.
     * Use on EVERY piece of user-supplied text before rendering in a view.
     *
     * @param  string $value   Raw user input
     * @param  int    $maxLen  Maximum character length (default 500)
     * @return string          HTML-safe output
     */
    public static function sanitizeString(string $value, int $maxLen = 500): string
    {
        $trimmed = mb_substr(strip_tags(trim($value)), 0, $maxLen, 'UTF-8');

        return htmlspecialchars($trimmed, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Return a sanitised integer from user input.
     * Returns 0 for non-numeric or negative values unless $allowNegative is true.
     *
     * @param  mixed $value
     * @param  bool  $allowNegative
     * @return int
     */
    public static function sanitizeInt(mixed $value, bool $allowNegative = false): int
    {
        $int = (int) filter_var($value, FILTER_SANITIZE_NUMBER_INT);
        return (!$allowNegative && $int < 0) ? 0 : $int;
    }

    /**
     * Validate and return a sanitised e-mail address, or null on failure.
     *
     * @param  string $email  Raw e-mail input
     * @return string|null    Sanitised e-mail or null if invalid
     */
    public static function sanitizeEmail(string $email): ?string
    {
        $filtered = filter_var(trim($email), FILTER_SANITIZE_EMAIL);

        return filter_var($filtered, FILTER_VALIDATE_EMAIL) !== false
            ? (string) $filtered
            : null;
    }

    // -------------------------------------------------------------------------
    // CSRF protection
    // -------------------------------------------------------------------------

    /**
     * Generate (or return an existing) CSRF token for the current session.
     *
     * Token is a 64-char hex string derived from 32 cryptographically secure bytes.
     *
     * @return string  CSRF token
     */
    public static function generateCsrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return (string) $_SESSION['csrf_token'];
    }

    /**
     * Validate a submitted CSRF token against the session token.
     *
     * Uses hash_equals() — constant-time comparison prevents timing attacks.
     *
     * @param  string $token  Token submitted by the client
     * @return bool           true if valid
     */
    public static function validateCsrfToken(string $token): bool
    {
        return isset($_SESSION['csrf_token'])
            && hash_equals((string) $_SESSION['csrf_token'], $token);
    }

    /**
     * Rotate the CSRF token (call after a successful sensitive action).
     *
     * @return string  New token
     */
    public static function rotateCsrfToken(): string
    {
        unset($_SESSION['csrf_token']);
        return self::generateCsrfToken();
    }

    // -------------------------------------------------------------------------
    // Password hashing
    // -------------------------------------------------------------------------

    /**
     * Hash a plain-text password using PHP's recommended algorithm (bcrypt by default).
     *
     * Never store plain-text passwords. Never use MD5 / SHA1.
     *
     * @param  string $password  Plain-text password
     * @return string            Bcrypt hash (60 chars)
     */
    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * Verify a plain-text password against a stored hash.
     *
     * Constant-time — safe against timing attacks.
     *
     * @param  string $password  Plain-text input from the login form
     * @param  string $hash      Hash stored in the database
     * @return bool              true if the password matches
     */
    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Check whether a stored hash needs to be rehashed (e.g. cost-factor upgrade).
     *
     * Call this after a successful login and re-hash if it returns true.
     *
     * @param  string $hash  Hash from the database
     * @return bool          true if rehashing is recommended
     */
    public static function needsRehash(string $hash): bool
    {
        return password_needs_rehash($hash, PASSWORD_DEFAULT);
    }
}
