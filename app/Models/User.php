<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Helpers\Security;

/**
 * User Model — CRUD for the users table.
 * All write operations return bool; reads return array|null.
 */
class User
{
    // -------------------------------------------------------------------------
    // READ
    // -------------------------------------------------------------------------

    /** Return all users ordered by creation date (newest first). */
    public static function all(): array
    {
        $db   = Database::getInstance();
        $stmt = $db->query(
            'SELECT id, username, email, role, status, created_at
               FROM users
              ORDER BY created_at DESC'
        );
        return $stmt->fetchAll();
    }

    /** Find a single user by primary key. Returns null if not found. */
    public static function find(int $id): ?array
    {
        $db   = Database::getInstance();
        $stmt = $db->query(
            'SELECT id, username, email, role, status, created_at
               FROM users
              WHERE id = :id
              LIMIT 1',
            [':id' => $id]
        );
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** Find a user by email (used for uniqueness checks). */
    public static function findByEmail(string $email, ?int $excludeId = null): ?array
    {
        $sql    = 'SELECT id FROM users WHERE email = :email';
        $params = [':email' => $email];

        if ($excludeId !== null) {
            $sql    .= ' AND id != :exclude';
            $params[':exclude'] = $excludeId;
        }

        $db   = Database::getInstance();
        $stmt = $db->query($sql . ' LIMIT 1', $params);
        $row  = $stmt->fetch();
        return $row ?: null;
    }

    /** Total user count. */
    public static function count(): int
    {
        $db   = Database::getInstance();
        $stmt = $db->query('SELECT COUNT(*) AS cnt FROM users');
        $row  = $stmt->fetch();
        return (int) ($row['cnt'] ?? 0);
    }

    // -------------------------------------------------------------------------
    // WRITE
    // -------------------------------------------------------------------------

    /**
     * Insert a new user.
     *
     * @param array{username:string, email:string, password:string, role:string, status:string} $data
     * @return int  New user ID
     */
    public static function create(array $data): int
    {
        $db = Database::getInstance();
        $db->query(
            'INSERT INTO users (username, email, password, role, status)
             VALUES (:username, :email, :password, :role, :status)',
            [
                ':username' => $data['username'],
                ':email'    => $data['email'],
                ':password' => Security::hashPassword($data['password']),
                ':role'     => $data['role']   ?? 'cashier',
                ':status'   => $data['status'] ?? 'active',
            ]
        );
        return $db->lastInsertId();
    }

    /**
     * Update a user's profile fields (no password change here).
     *
     * @return bool  true if a row was affected
     */
    public static function update(int $id, array $data): bool
    {
        $db   = Database::getInstance();
        $stmt = $db->query(
            'UPDATE users
                SET username = :username,
                    email    = :email,
                    role     = :role,
                    status   = :status
              WHERE id = :id',
            [
                ':id'       => $id,
                ':username' => $data['username'],
                ':email'    => $data['email'],
                ':role'     => $data['role'],
                ':status'   => $data['status'],
            ]
        );
        return $stmt->rowCount() > 0;
    }

    /**
     * Change a user's password.
     * Accepts the new plain-text password — hashing done here.
     *
     * @return bool  true if the row was updated
     */
    public static function changePassword(int $id, string $newPassword): bool
    {
        $db   = Database::getInstance();
        $stmt = $db->query(
            'UPDATE users SET password = :password WHERE id = :id',
            [
                ':id'       => $id,
                ':password' => Security::hashPassword($newPassword),
            ]
        );
        return $stmt->rowCount() > 0;
    }

    /**
     * Soft-delete equivalent: set status to 'inactive'.
     * Prevents deleting the last admin account.
     */
    public static function deactivate(int $id): bool
    {
        $db   = Database::getInstance();
        $stmt = $db->query(
            "UPDATE users SET status = 'inactive' WHERE id = :id",
            [':id' => $id]
        );
        return $stmt->rowCount() > 0;
    }

    /**
     * Hard delete a user record.
     * Use with caution — consider deactivate() for audit trails.
     */
    public static function delete(int $id): bool
    {
        $db   = Database::getInstance();
        $stmt = $db->query(
            'DELETE FROM users WHERE id = :id',
            [':id' => $id]
        );
        return $stmt->rowCount() > 0;
    }

    // -------------------------------------------------------------------------
    // HELPERS
    // -------------------------------------------------------------------------

    /**
     * Count how many active admin accounts exist.
     * Used to prevent removing the last admin.
     */
    public static function countActiveAdmins(): int
    {
        $db   = Database::getInstance();
        $stmt = $db->query(
            "SELECT COUNT(*) AS cnt FROM users WHERE role = 'admin' AND status = 'active'"
        );
        $row = $stmt->fetch();
        return (int) ($row['cnt'] ?? 0);
    }
}
