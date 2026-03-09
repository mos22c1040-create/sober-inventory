<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDOException;

class ActivityLog
{
    public static function log(
        string $action,
        ?string $entityType = null,
        ?int $entityId = null,
        ?string $details = null,
        ?int $userId = null
    ): void {
        $userId = $userId ?? (isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null);
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        if ($ip && strlen($ip) > 45) {
            $ip = substr($ip, 0, 45);
        }
        $details = $details !== null && strlen($details) > 500 ? substr($details, 0, 497) . '...' : $details;

        try {
            $db = Database::getInstance();
            $db->query(
                'INSERT INTO activity_log (user_id, action, entity_type, entity_id, details, ip_address)
                 VALUES (:user_id, :action, :entity_type, :entity_id, :details, :ip)',
                [
                    ':user_id'     => $userId,
                    ':action'      => $action,
                    ':entity_type' => $entityType,
                    ':entity_id'   => $entityId,
                    ':details'     => $details,
                    ':ip'          => $ip,
                ]
            );
        } catch (PDOException $e) {
            // Table may not exist yet (run storage/patch_activity_log.sql)
            // Don't crash the app; just skip logging
        }
    }

    public static function getRecent(int $limit = 100): array
    {
        try {
            $db = Database::getInstance();
            $stmt = $db->query(
                'SELECT a.id, a.user_id, a.action, a.entity_type, a.entity_id, a.details, a.ip_address, a.created_at,
                        u.username
                 FROM activity_log a
                 LEFT JOIN users u ON u.id = a.user_id
                 ORDER BY a.created_at DESC
                 LIMIT ' . (int) $limit
            );
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }
}
