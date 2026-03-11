<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * FileCache — lightweight file-based key/value cache.
 *
 * Stores serialised PHP values in /storage/cache/.
 * No dependencies. Safe for single-server deployments.
 *
 * Usage:
 *   FileCache::set('my_key', $data, 300); // 5-minute TTL
 *   $val = FileCache::get('my_key');      // null on miss/expired
 *   FileCache::delete('my_key');
 */
class FileCache
{
    private static string $cacheDir = '';

    private static function dir(): string
    {
        if (self::$cacheDir === '') {
            self::$cacheDir = defined('BASE_PATH')
                ? BASE_PATH . '/storage/cache'
                : sys_get_temp_dir() . '/sober_cache';
        }
        if (!is_dir(self::$cacheDir)) {
            @mkdir(self::$cacheDir, 0700, true);
        }
        return self::$cacheDir;
    }

    private static function path(string $key): string
    {
        return self::dir() . '/' . md5($key) . '.cache';
    }

    /**
     * Store a value in the cache.
     *
     * @param  string $key  Cache key
     * @param  mixed  $value Any serialisable PHP value
     * @param  int    $ttl   Seconds until expiry (0 = never)
     */
    public static function set(string $key, mixed $value, int $ttl = 0): void
    {
        $payload = serialize([
            'expires' => $ttl > 0 ? time() + $ttl : 0,
            'data'    => $value,
        ]);
        @file_put_contents(self::path($key), $payload, LOCK_EX);
    }

    /**
     * Retrieve a cached value.
     *
     * @return mixed|null  Null if key does not exist or has expired.
     */
    public static function get(string $key): mixed
    {
        $file = self::path($key);
        if (!is_file($file)) {
            return null;
        }
        $raw = @file_get_contents($file);
        if ($raw === false) {
            return null;
        }
        $payload = @unserialize($raw);
        if (!is_array($payload) || !array_key_exists('data', $payload)) {
            return null;
        }
        // Check expiry
        if ($payload['expires'] > 0 && $payload['expires'] < time()) {
            @unlink($file);
            return null;
        }
        return $payload['data'];
    }

    /**
     * Returns $default if the key is not in cache, otherwise returns cached value.
     * Convenience wrapper that accepts a callable to populate the cache on miss.
     *
     * @param  string   $key
     * @param  callable $callback  Called on cache miss; its return value is cached.
     * @param  int      $ttl       TTL in seconds
     * @return mixed
     */
    public static function remember(string $key, callable $callback, int $ttl = 300): mixed
    {
        $cached = self::get($key);
        if ($cached !== null) {
            return $cached;
        }
        $value = $callback();
        self::set($key, $value, $ttl);
        return $value;
    }

    /** Remove a single cache entry. */
    public static function delete(string $key): void
    {
        @unlink(self::path($key));
    }

    /** Remove all cache entries (e.g. after data mutation). */
    public static function flush(): void
    {
        $files = glob(self::dir() . '/*.cache') ?: [];
        foreach ($files as $file) {
            @unlink($file);
        }
    }
}
