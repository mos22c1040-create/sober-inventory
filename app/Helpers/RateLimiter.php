<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * RateLimiter — file-based sliding-window rate limiter.
 *
 * Stores attempt timestamps in /storage/rate_limits/ (one JSON file per key).
 * Zero external dependencies — works on any PHP 7.4+ setup.
 *
 * Usage:
 *   if (!RateLimiter::attempt('login_' . $ip, 5, 900)) {
 *       // too many attempts
 *   }
 */
class RateLimiter
{
    private static string $storageDir = '';

    private static function dir(): string
    {
        if (self::$storageDir === '') {
            self::$storageDir = defined('BASE_PATH')
                ? BASE_PATH . '/storage/rate_limits'
                : sys_get_temp_dir() . '/sober_rate_limits';
        }
        if (!is_dir(self::$storageDir)) {
            @mkdir(self::$storageDir, 0700, true);
        }
        return self::$storageDir;
    }

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
        $file = self::dir() . '/' . md5($key) . '.json';
        $now  = time();

        // Load existing timestamps
        $timestamps = [];
        if (is_file($file)) {
            $raw = @file_get_contents($file);
            if ($raw !== false) {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    $timestamps = $decoded;
                }
            }
        }

        // Remove expired entries (outside the sliding window)
        $windowStart = $now - $windowSeconds;
        $timestamps  = array_values(array_filter(
            $timestamps,
            static fn(int $ts) => $ts > $windowStart
        ));

        // Check before adding the new attempt
        if (count($timestamps) >= $maxAttempts) {
            // Still persist cleaned list (but don't add the new attempt)
            @file_put_contents($file, json_encode($timestamps), LOCK_EX);
            return false;
        }

        // Record this attempt
        $timestamps[] = $now;
        @file_put_contents($file, json_encode($timestamps), LOCK_EX);
        return true;
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
        $file = self::dir() . '/' . md5($key) . '.json';
        if (!is_file($file)) {
            return 0;
        }
        $raw  = @file_get_contents($file);
        if ($raw === false) {
            return 0;
        }
        $timestamps = json_decode($raw, true);
        if (!is_array($timestamps) || empty($timestamps)) {
            return 0;
        }
        $oldest = min($timestamps);
        $retry  = ($oldest + $windowSeconds) - time();
        return max(0, $retry);
    }

    /**
     * Clear all attempts for a key (e.g. on successful login).
     */
    public static function clear(string $key): void
    {
        $file = self::dir() . '/' . md5($key) . '.json';
        @unlink($file);
    }
}
