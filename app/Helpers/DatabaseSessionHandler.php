<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Core\Database;

/**
 * Store PHP sessions in MySQL (for Vercel/serverless where file sessions don't work).
 * Enable with SESSION_DRIVER=database in .env and run storage/patch_sessions_table.sql.
 */
class DatabaseSessionHandler implements \SessionHandlerInterface
{
    private const TABLE = 'sessions';
    private const LIFETIME = 3600; // 1 hour

    public function open(string $path, string $name): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    /** @return string */
    public function read($id)
    {
        try {
            $db = Database::getInstance();
            $stmt = $db->getConnection()->prepare(
                'SELECT payload FROM ' . self::TABLE . ' WHERE id = :id AND last_activity > :expire'
            );
            $stmt->execute([
                ':id'     => $id,
                ':expire' => time() - self::LIFETIME,
            ]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $row ? (string) $row['payload'] : '';
        } catch (\Throwable $e) {
            return '';
        }
    }

    public function write(string $id, string $data): bool
    {
        try {
            $db = Database::getInstance();
            $conn = $db->getConnection();
            $la = time();
            if ($db->getDriver() === 'pgsql') {
                $conn->prepare(
                    'INSERT INTO ' . self::TABLE . ' (id, payload, last_activity) VALUES (:id, :payload, :la) ON CONFLICT (id) DO UPDATE SET payload = EXCLUDED.payload, last_activity = EXCLUDED.last_activity'
                )->execute([':id' => $id, ':payload' => $data, ':la' => $la]);
            } else {
                $conn->prepare(
                    'REPLACE INTO ' . self::TABLE . ' (id, payload, last_activity) VALUES (:id, :payload, :la)'
                )->execute([':id' => $id, ':payload' => $data, ':la' => $la]);
            }
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function destroy(string $id): bool
    {
        try {
            Database::getInstance()->getConnection()->prepare('DELETE FROM ' . self::TABLE . ' WHERE id = :id')->execute([':id' => $id]);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** @return int */
    public function gc($max_lifetime)
    {
        try {
            $stmt = Database::getInstance()->getConnection()->prepare('DELETE FROM ' . self::TABLE . ' WHERE last_activity < :expire');
            $stmt->execute([':expire' => time() - max($max_lifetime, self::LIFETIME)]);
            return $stmt->rowCount();
        } catch (\Throwable $e) {
            return 0;
        }
    }
}
