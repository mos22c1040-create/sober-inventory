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

    /** Total sale count. */
    public static function count(): int
    {
        $stmt = Database::getInstance()->query("SELECT COUNT(*) AS cnt FROM sales");
        return (int) ($stmt->fetch()['cnt'] ?? 0);
    }

    /**
     * Paginated sales list.
     *
     * @return array{ data: array, total: int, page: int, perPage: int, pages: int }
     */
    public static function paginate(int $page = 1, int $perPage = 25): array
    {
        $page    = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $offset  = ($page - 1) * $perPage;
        $total   = self::count();

        $stmt = Database::getInstance()->query(
            "SELECT s.*, u.username AS cashier_name
               FROM sales s
               LEFT JOIN users u ON s.user_id = u.id
              ORDER BY s.created_at DESC
              LIMIT {$perPage} OFFSET {$offset}"
        );

        return [
            'data'    => $stmt->fetchAll(),
            'total'   => $total,
            'page'    => $page,
            'perPage' => $perPage,
            'pages'   => (int) ceil($total / $perPage),
        ];
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

    public static function create(int $userId, array $items, string $customerName = 'Walk-in Customer', string $paymentMethod = 'cash', float $discount = 0.0, string $notes = ''): int
    {
        $db = Database::getInstance();
        $invoiceNumber = self::generateInvoiceNumber();
        $subtotal = 0.0;
        foreach ($items as $item) {
            $subtotal += (float) $item['total'];
        }
        $total = max(0, $subtotal - $discount);

        $db->beginTransaction();
        try {
            $db->query(
                "INSERT INTO sales (user_id, invoice_number, customer_name, total, discount, notes, payment_method, status) VALUES (:user_id, :invoice_number, :customer_name, :total, :discount, :notes, :payment_method, 'paid')",
                [
                    ':user_id'        => $userId,
                    ':invoice_number' => $invoiceNumber,
                    ':customer_name'  => $customerName,
                    ':total'          => $total,
                    ':discount'       => $discount,
                    ':notes'          => $notes !== '' ? $notes : null,
                    ':payment_method' => $paymentMethod,
                ]
            );
            $saleId = $db->lastInsertId();
            foreach ($items as $item) {
                $db->query(
                    "INSERT INTO sale_items (sale_id, product_id, quantity, unit_price, total) VALUES (:sale_id, :product_id, :quantity, :unit_price, :total)",
                    [
                        ':sale_id'    => $saleId,
                        ':product_id' => $item['product_id'],
                        ':quantity'   => $item['quantity'],
                        ':unit_price' => $item['unit_price'],
                        ':total'      => $item['total'],
                    ]
                );
                Product::decrementStock((int) $item['product_id'], (int) $item['quantity']);
            }
            $db->commit();
        } catch (\Exception $e) {
            $db->rollBack();
            throw $e;
        }

        return $saleId;
    }

    /**
     * إلغاء فاتورة مدفوعة وإعادة المخزون لكل منتجاتها.
     * يُعيد false إذا كانت الفاتورة غير موجودة أو ليست بحالة 'paid'.
     */
    public static function cancel(int $id): bool
    {
        $db   = Database::getInstance();
        $sale = self::find($id);

        if (!$sale || $sale['status'] !== 'paid') {
            return false;
        }

        $items = self::getItems($id);

        $db->beginTransaction();
        try {
            $db->query(
                "UPDATE sales SET status = 'cancelled' WHERE id = :id",
                [':id' => $id]
            );
            foreach ($items as $item) {
                Product::incrementStock((int) $item['product_id'], (int) $item['quantity']);
            }
            $db->commit();
        } catch (\Exception $e) {
            $db->rollBack();
            throw $e;
        }

        return true;
    }

    public static function monthlyTotal(): float
    {
        $db  = Database::getInstance();
        $sql = $db->getDriver() === 'pgsql'
            ? "SELECT COALESCE(SUM(total), 0) AS total FROM sales WHERE date_trunc('month', created_at) = date_trunc('month', CURRENT_DATE) AND status = 'paid'"
            : "SELECT COALESCE(SUM(total), 0) AS total FROM sales WHERE YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE()) AND status = 'paid'";
        $row = $db->query($sql)->fetch();
        return (float) ($row['total'] ?? 0);
    }

    public static function todayTotal(): float
    {
        $db = Database::getInstance();
        $sql = $db->getDriver() === 'pgsql'
            ? "SELECT COALESCE(SUM(total), 0) AS total FROM sales WHERE (created_at::date) = CURRENT_DATE AND status = 'paid'"
            : "SELECT COALESCE(SUM(total), 0) AS total FROM sales WHERE DATE(created_at) = CURDATE() AND status = 'paid'";
        $stmt = $db->query($sql);
        $row = $stmt->fetch();
        return (float) ($row['total'] ?? 0);
    }

    public static function todayCount(): int
    {
        $db = Database::getInstance();
        $sql = $db->getDriver() === 'pgsql'
            ? "SELECT COUNT(*) AS cnt FROM sales WHERE (created_at::date) = CURRENT_DATE AND status = 'paid'"
            : "SELECT COUNT(*) AS cnt FROM sales WHERE DATE(created_at) = CURDATE() AND status = 'paid'";
        $stmt = $db->query($sql);
        $row = $stmt->fetch();
        return (int) ($row['cnt'] ?? 0);
    }

    /**
     * إجمالي المبيعات (المدفوعة) لكل يوم لآخر $days يوم.
     * المفتاح: تاريخ Y-m-d، القيمة: المجموع.
     *
     * @return array<string, float>
     */
    public static function getDailyTotalsLastDays(int $days = 7): array
    {
        $db = Database::getInstance();
        $days = (int) $days;
        if ($db->getDriver() === 'pgsql') {
            $sql = "SELECT (created_at::date) AS d, COALESCE(SUM(total), 0) AS total
                      FROM sales
                     WHERE status = 'paid'
                       AND created_at >= (CURRENT_DATE - INTERVAL '1 day' * :days)
                     GROUP BY (created_at::date)
                     ORDER BY d ASC";
            $stmt = $db->query($sql, [':days' => $days]);
        } else {
            $stmt = $db->query(
                "SELECT DATE(created_at) AS d, COALESCE(SUM(total), 0) AS total
                   FROM sales
                  WHERE status = 'paid'
                    AND created_at >= DATE_SUB(CURDATE(), INTERVAL {$days} DAY)
                  GROUP BY DATE(created_at)
                  ORDER BY d ASC"
            );
        }
        $rows = $stmt->fetchAll();
        $out  = [];
        foreach ($rows as $r) {
            $out[(string) $r['d']] = (float) $r['total'];
        }
        return $out;
    }
}
