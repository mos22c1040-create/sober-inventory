<?php

declare(strict_types=1);

namespace App\Helpers;

class BarcodeBridge
{
    private const DIRECTORY = '/storage/barcode_bridge';

    /**
     * Resolve TTL (seconds) from env, with a safe fallback.
     */
    private static function ttl(): int
    {
        $raw  = $_ENV['BARCODE_BRIDGE_TTL'] ?? getenv('BARCODE_BRIDGE_TTL') ?: '60';
        $ttl  = (int) $raw;
        return $ttl > 0 ? $ttl : 60;
    }

    /**
     * Persist the scanned barcode for a given user.
     */
    public static function push(int $userId, string $barcode): bool
    {
        if ($userId < 1 || $barcode === '') {
            error_log(sprintf('BarcodeBridge push rejected: invalid userId (%d) or empty barcode', $userId));
            return false;
        }

        $dir = BASE_PATH . self::DIRECTORY;

        if (!is_dir($dir)) {
            if (!@mkdir($dir, 0755, true) && !is_dir($dir)) {
                error_log('Failed to create barcode bridge directory: ' . $dir);
                return false;
            }
        }

        $file = $dir . '/' . $userId . '.json';
        $data = [
            'barcode' => $barcode,
            'time'    => time(),
        ];

        $json = json_encode($data, JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            error_log('BarcodeBridge json_encode failed for userId ' . $userId);
            return false;
        }

        if (@file_put_contents($file, $json, LOCK_EX) === false) {
            error_log('Failed to write barcode bridge file: ' . $file);
            return false;
        }

        return true;
    }

    /**
     * Consume (read + delete) the last barcode for a user.
     *
     * @return array{barcode: string, time: int}|null
     */
    public static function consumeLast(int $userId): ?array
    {
        if ($userId < 1) {
            error_log('BarcodeBridge consumeLast called with invalid userId: ' . $userId);
            return null;
        }

        $file = BASE_PATH . self::DIRECTORY . '/' . $userId . '.json';

        if (!is_file($file)) {
            return null;
        }

        $raw = @file_get_contents($file);
        @unlink($file);

        if ($raw === false || $raw === '') {
            error_log('BarcodeBridge consumeLast: empty or unreadable file for userId ' . $userId);
            return null;
        }

        $data = json_decode($raw, true);
        if (!is_array($data)) {
            error_log('BarcodeBridge consumeLast: malformed JSON for userId ' . $userId);
            return null;
        }

        $barcode = isset($data['barcode']) ? trim((string) $data['barcode']) : '';
        $time    = (int) ($data['time'] ?? 0);

        if ($barcode === '' || $time <= 0) {
            error_log('BarcodeBridge consumeLast: missing barcode or time for userId ' . $userId);
            return null;
        }

        $ttl = self::ttl();
        $age = time() - $time;
        if ($age > $ttl) {
            error_log(sprintf('BarcodeBridge consumeLast: expired barcode for userId %d (age=%d, ttl=%d)', $userId, $age, $ttl));
            return null;
        }

        return [
            'barcode' => $barcode,
            'time'    => $time,
        ];
    }
}

