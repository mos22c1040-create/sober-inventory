<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Helpers\AuthHelper;

/**
 * AuthMiddleware — Role-Based Access Control for protected endpoints.
 *
 * Usage in routes or controllers:
 *   AuthMiddleware::requireRole(['admin', 'manager']);
 *   AuthMiddleware::requireAuth();
 */
class AuthMiddleware
{
    /**
     * Require any authenticated user (alias for AuthHelper::requireAuth).
     */
    public static function requireAuth(): void
    {
        AuthHelper::checkAuth();
    }

    /**
     * Require specific roles to access an endpoint.
     *
     * @param array $allowedRoles Array of allowed role strings (e.g., ['admin', 'manager'])
     * @return void
     */
    public static function requireRole(array $allowedRoles): void
    {
        AuthHelper::checkAuth();

        $userRole = isset($_SESSION['role']) ? strtolower((string) $_SESSION['role']) : '';

        if (!in_array($userRole, array_map('strtolower', $allowedRoles), true)) {
            http_response_code(403);
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode([
                'success' => false,
                'error'   => 'Access denied. Insufficient permissions.',
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    /**
     * Require admin role only.
     */
    public static function requireAdmin(): void
    {
        self::requireRole(['admin']);
    }

    /**
     * Require admin or manager roles.
     */
    public static function requireAdminOrManager(): void
    {
        self::requireRole(['admin', 'manager']);
    }

    /**
     * Get current user role (null if not authenticated).
     */
    public static function getCurrentRole(): ?string
    {
        return isset($_SESSION['role']) ? strtolower((string) $_SESSION['role']) : null;
    }

    /**
     * Check if current user has a specific role.
     */
    public static function hasRole(string $role): bool
    {
        return self::getCurrentRole() === strtolower($role);
    }

    /**
     * Check if current user has any of the specified roles.
     */
    public static function hasAnyRole(array $roles): bool
    {
        $currentRole = self::getCurrentRole();
        if ($currentRole === null) {
            return false;
        }
        return in_array($currentRole, array_map('strtolower', $roles), true);
    }
}