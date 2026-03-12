<?php

declare(strict_types=1);

namespace App\Helpers;

class BarcodeBridge
{
    private const DIRECTORY   = '/storage/barcode_bridge';
    private const TTL_SECONDS = 60;

    /**
     * Persist the scanned barcode for a given user.
     */
    public static function push(int $userId, string $barcode): bool
    {
        if ($userId < 1 || $barcode === '') {
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
            return null;
        }

        $file = BASE_PATH . self::DIRECTORY . '/' . $userId . '.json';

        if (!is_file($file)) {
            return null;
        }

        $raw = @file_get_contents($file);
        @unlink($file);

        if ($raw === false || $raw === '') {
            return null;
        }

        $data = json_decode($raw, true);
        if (!is_array($data)) {
            return null;
        }

        $barcode = isset($data['barcode']) ? trim((string) $data['barcode']) : '';
        $time    = (int) ($data['time'] ?? 0);

        if ($barcode === '' || $time <= 0) {
            return null;
        }

        if (time() - $time > self::TTL_SECONDS) {
            // Expired; do not return stale data
            return null;
        }

        return [
            'barcode' => $barcode,
            'time'    => $time,
        ];
    }
}

