<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Purchase
{
    public static function all(int $limit = 50): array
    {
        $db = Database::getInstance();
        $stmt = $db->query(
            "SELECT p.*, u.username AS created_by FROM purchases p LEFT JOIN users u ON p.user_id = u.id ORDER BY p.created_at DESC LIMIT " . (int) $limit
        );
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT * FROM purchases WHERE id = :id LIMIT 1", [':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function getItems(int $purchaseId): array
    {
        $db = Database::getInstance();
        $stmt = $db->query(
            "SELECT pi.*, pr.name AS product_name FROM purchase_items pi JOIN products pr ON pi.product_id = pr.id WHERE pi.purchase_id = :purchase_id",
            [':purchase_id' => $purchaseId]
        );
        return $stmt->fetchAll();
    }

    public static function create(int $userId, array $items, string $supplier = ''): int
    {
        $db = Database::getInstance();
        $total = 0.0;
        foreach ($items as $item) {
            $total += (float) $item['total'];
        }
        $db->query(
            "INSERT INTO purchases (user_id, supplier, total, status) VALUES (:user_id, :supplier, :total, 'completed')",
            [
                ':user_id' => $userId,
                ':supplier' => $supplier,
                ':total' => $total,
            ]
        );
        $purchaseId = (int) $db->getConnection()->lastInsertId();
        foreach ($items as $item) {
            $db->query(
                "INSERT INTO purchase_items (purchase_id, product_id, quantity, unit_cost, total) VALUES (:purchase_id, :product_id, :quantity, :unit_cost, :total)",
                [
                    ':purchase_id' => $purchaseId,
                    ':product_id' => $item['product_id'],
                    ':quantity' => $item['quantity'],
                    ':unit_cost' => $item['unit_cost'],
                    ':total' => $item['total'],
                ]
            );
            Product::incrementStock((int) $item['product_id'], (int) $item['quantity']);
        }
        return $purchaseId;
    }
}
