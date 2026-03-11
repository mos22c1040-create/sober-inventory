<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * AppSetting — persistent key/value store backed by the `settings` table.
 *
 * Replaces the ephemeral storage/settings.json file which is lost every
 * time the Railway container restarts.
 */
class AppSetting
{
    /**
     * Fetch all settings as an associative array [key => value].
     * Returns an empty array if the table does not yet exist.
     */
    public static function all(): array
    {
        try {
            $rows = Database::getInstance()->query('SELECT key, value FROM settings')->fetchAll();
            $out  = [];
            foreach ($rows as $row) {
                $out[$row['key']] = $row['value'];
            }
            return $out;
        } catch (\PDOException) {
            return [];
        }
    }

    /**
     * Retrieve a single setting by key, or return $default if not found.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        try {
            $row = Database::getInstance()
                ->query('SELECT value FROM settings WHERE key = :key LIMIT 1', [':key' => $key])
                ->fetch();
            return $row !== false ? $row['value'] : $default;
        } catch (\PDOException) {
            return $default;
        }
    }

    /**
     * Insert or update a setting (upsert).
     * Silently ignores errors if the table does not yet exist.
     */
    public static function set(string $key, string $value): bool
    {
        try {
            $db    = Database::getInstance();
            $driver = $db->getDriver();

            if ($driver === 'pgsql') {
                $db->query(
                    'INSERT INTO settings (key, value) VALUES (:key, :value)
                     ON CONFLICT (key) DO UPDATE SET value = EXCLUDED.value, updated_at = CURRENT_TIMESTAMP',
                    [':key' => $key, ':value' => $value]
                );
            } else {
                $db->query(
                    'INSERT INTO settings (`key`, `value`) VALUES (:key, :value)
                     ON DUPLICATE KEY UPDATE value = VALUES(value), updated_at = CURRENT_TIMESTAMP',
                    [':key' => $key, ':value' => $value]
                );
            }
            return true;
        } catch (\PDOException) {
            return false;
        }
    }

    /**
     * Bulk-upsert multiple settings at once.
     *
     * @param array<string, string> $data  Associative array of key → value pairs
     */
    public static function setMany(array $data): bool
    {
        $ok = true;
        foreach ($data as $key => $value) {
            if (!self::set((string) $key, (string) $value)) {
                $ok = false;
            }
        }
        return $ok;
    }
}
