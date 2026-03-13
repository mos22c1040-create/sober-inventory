<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Core\Database;

/**
 * RateLimiter — database-backed sliding-window rate limiter.
 *
 * Works on serverless platforms (Vercel/Railway) where filesystem is ephemeral.
 * Assumes a `rate_limits` table exists with columns: key_hash, timestamp.
 *
 * Usage:
 *   if (!RateLimiter::attempt('login_127.0.0.1', 5, 900)) {
 *       // too many attempts
 *   }
 */
class RateLimiter
{
    /**
     * Record one attempt and check if the limit is exceeded.
     *
     * @param  string $key            Unique identifier (e.g. "login_127.0.0.1")
     * @param  int    $maxAttempts    Maximum allowed attempts per window
     * @param  int    $windowSeconds  Sliding window size in seconds
     * @return bool   true → attempt allowed, false → limit exceeded
     */
    public static function attempt(string $key, int $maxAttempts = 5, int $windowSeconds = 900): bool
    {
        $db = Database::getInstance();
        $keyHash = md5($key);
        $now = time();
        $windowStart = $now - $windowSeconds;

        $db->beginTransaction();

        try {
            $db->query(
                "DELETE FROM rate_limits WHERE key_hash = :key_hash AND timestamp < :window_start",
                [':key_hash' => $keyHash, ':window_start' => $windowStart]
            );

            $stmt = $db->query(
                "SELECT COUNT(*) AS cnt FROM rate_limits WHERE key_hash = :key_hash",
                [':key_hash' => $keyHash]
            );
            $count = (int) ($stmt->fetch()['cnt'] ?? 0);

            if ($count >= $maxAttempts) {
                $db->rollBack();
                return false;
            }

            $db->query(
                "INSERT INTO rate_limits (key_hash, timestamp) VALUES (:key_hash, :timestamp)",
                [':key_hash' => $keyHash, ':timestamp' => $now]
            );

            $db->commit();
            return true;
        } catch (\Exception $e) {
            $db->rollBack();
            error_log('[RateLimiter] Database error: ' . $e->getMessage());
            return true;
        }
    }

    /**
     * How many seconds remain until the oldest attempt expires.
     * Returns 0 if under the limit.
     *
     * @param  string $key
     * @param  int    $windowSeconds
     * @return int seconds to wait (0 = not blocked)
     */
    public static function retryAfter(string $key, int $windowSeconds = 900): int
    {
        try {
            $db = Database::getInstance();
            $keyHash = md5($key);

            $stmt = $db->query(
                "SELECT MIN(timestamp) AS oldest FROM rate_limits WHERE key_hash = :key_hash",
                [':key_hash' => $keyHash]
            );
            $row = $stmt->fetch();

            if (!$row || empty($row['oldest'])) {
                return 0;
            }

            $oldest = (int) $row['oldest'];
            $retry = ($oldest + $windowSeconds) - time();
            return max(0, $retry);
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Clear all attempts for a key (e.g. on successful login).
     */
    public static function clear(string $key): void
    {
        try {
            $db = Database::getInstance();
            $keyHash = md5($key);
            $db->query("DELETE FROM rate_limits WHERE key_hash = :key_hash", [':key_hash' => $keyHash]);
        } catch (\Exception $e) {
            error_log('[RateLimiter] Clear error: ' . $e->getMessage());
        }
    }
}