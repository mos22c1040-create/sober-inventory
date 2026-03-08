<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Sale
{
    public static function all(int $limit = 50): array
    {
        $db = Database::getInstance();
        $stmt = $db->query(
            "SELECT s.*, u.username AS cashier_name FROM sales s LEFT JOIN users u ON s.user_id = u.id ORDER BY s.created_at DESC LIMIT " . (int) $limit
        );
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $db = Database::getInstance();
        $stmt = $db->query(
            "SELECT s.*, u.username AS cashier_name FROM sales s LEFT JOIN users u ON s.user_id = u.id WHERE s.id = :id LIMIT 1",
            [':id' => $id]
        );
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function getItems(int $saleId): array
    {
        $db = Database::getInstance();
        $stmt = $db->query(
            "SELECT si.*, p.name AS product_name FROM sale_items si JOIN products p ON si.product_id = p.id WHERE si.sale_id = :sale_id",
            [':sale_id' => $saleId]
        );
        return $stmt->fetchAll();
    }

    public static function generateInvoiceNumber(): string
    {
        $db = Database::getInstance();
        $y = date('Y');
        $stmt = $db->query(
            "SELECT invoice_number FROM sales WHERE invoice_number LIKE :prefix ORDER BY id DESC LIMIT 1",
            [':prefix' => "INV-{$y}-%"]
        );
        $row = $stmt->fetch();
        if (!$row) {
            return "INV-{$y}-001";
        }
        preg_match('/INV-\d+-(\d+)/', $row['invoice_number'], $m);
        $num = (int) ($m[1] ?? 0) + 1;
        return sprintf("INV-%s-%03d", $y, $num);
    }

    public static function create(int $userId, array $items, string $customerName = 'Walk-in Customer', string $paymentMethod = 'cash'): int
    {
        $db = Database::getInstance();
        $invoiceNumber = self::generateInvoiceNumber();
        $total = 0.0;
        foreach ($items as $item) {
            $total += (float) $item['total'];
        }
        $db->query(
            "INSERT INTO sales (user_id, invoice_number, customer_name, total, payment_method, status) VALUES (:user_id, :invoice_number, :customer_name, :total, :payment_method, 'paid')",
            [
                ':user_id' => $userId,
                ':invoice_number' => $invoiceNumber,
                ':customer_name' => $customerName,
                ':total' => $total,
                ':payment_method' => $paymentMethod,
            ]
        );
        $saleId = (int) $db->getConnection()->lastInsertId();
        foreach ($items as $item) {
            $db->query(
                "INSERT INTO sale_items (sale_id, product_id, quantity, unit_price, total) VALUES (:sale_id, :product_id, :quantity, :unit_price, :total)",
                [
                    ':sale_id' => $saleId,
                    ':product_id' => $item['product_id'],
                    ':quantity' => $item['quantity'],
                    ':unit_price' => $item['unit_price'],
                    ':total' => $item['total'],
                ]
            );
            Product::decrementStock((int) $item['product_id'], (int) $item['quantity']);
        }
        return $saleId;
    }

    public static function todayTotal(): float
    {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT COALESCE(SUM(total), 0) AS total FROM sales WHERE DATE(created_at) = CURDATE() AND status = 'paid'");
        $row = $stmt->fetch();
        return (float) ($row['total'] ?? 0);
    }

    public static function todayCount(): int
    {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT COUNT(*) AS cnt FROM sales WHERE DATE(created_at) = CURDATE() AND status = 'paid'");
        $row = $stmt->fetch();
        return (int) ($row['cnt'] ?? 0);
    }
}
