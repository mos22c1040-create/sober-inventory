<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Expense
{
    /** All expense categories */
    public const CATEGORIES = [
        'إيجار',
        'كهرباء وماء',
        'رواتب',
        'صيانة',
        'مواصلات',
        'تسويق وإعلان',
        'مستلزمات مكتبية',
        'ضرائب ورسوم',
        'نثريات',
        'أخرى',
    ];

    public static function all(int $limit = 100): array
    {
        $db = Database::getInstance();
        $stmt = $db->query(
            "SELECT e.*, u.username AS created_by
               FROM expenses e
               LEFT JOIN users u ON e.user_id = u.id
              ORDER BY e.expense_date DESC, e.id DESC
              LIMIT " . (int)$limit
        );
        return $stmt->fetchAll();
    }

    public static function paginate(int $page = 1, int $perPage = 25): array
    {
        $page    = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $offset  = ($page - 1) * $perPage;
        $total   = self::count();

        $stmt = Database::getInstance()->query(
            "SELECT e.*, u.username AS created_by
               FROM expenses e
               LEFT JOIN users u ON e.user_id = u.id
              ORDER BY e.expense_date DESC, e.id DESC
              LIMIT {$perPage} OFFSET {$offset}"
        );

        return [
            'data'    => $stmt->fetchAll(),
            'total'   => $total,
            'page'    => $page,
            'perPage' => $perPage,
            'pages'   => (int)ceil($total / $perPage),
        ];
    }

    public static function count(): int
    {
        $stmt = Database::getInstance()->query("SELECT COUNT(*) AS cnt FROM expenses");
        return (int)($stmt->fetch()['cnt'] ?? 0);
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::getInstance()->query(
            "SELECT * FROM expenses WHERE id = :id LIMIT 1",
            [':id' => $id]
        );
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function create(int $userId, array $data): int
    {
        $db = Database::getInstance();
        $db->query(
            "INSERT INTO expenses (user_id, amount, category, description, expense_date)
             VALUES (:user_id, :amount, :category, :description, :expense_date)",
            [
                ':user_id'      => $userId,
                ':amount'       => (float)($data['amount'] ?? 0),
                ':category'     => $data['category'] ?? 'أخرى',
                ':description'  => $data['description'] ?? null,
                ':expense_date' => $data['expense_date'] ?? date('Y-m-d'),
            ]
        );
        return (int)$db->getConnection()->lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        $stmt = Database::getInstance()->query(
            "UPDATE expenses SET amount = :amount, category = :category,
                description = :description, expense_date = :expense_date
              WHERE id = :id",
            [
                ':id'           => $id,
                ':amount'       => (float)($data['amount'] ?? 0),
                ':category'     => $data['category'] ?? 'أخرى',
                ':description'  => $data['description'] ?? null,
                ':expense_date' => $data['expense_date'] ?? date('Y-m-d'),
            ]
        );
        return $stmt->rowCount() > 0;
    }

    public static function delete(int $id): bool
    {
        $stmt = Database::getInstance()->query(
            "DELETE FROM expenses WHERE id = :id",
            [':id' => $id]
        );
        return $stmt->rowCount() > 0;
    }

    /** Monthly total expenses */
    public static function monthlyTotal(): float
    {
        $db = Database::getInstance();
        $isPgsql = $db->getDriver() === 'pgsql';
        $stmt = $isPgsql
            ? $db->query("SELECT COALESCE(SUM(amount),0) AS total FROM expenses WHERE expense_date >= date_trunc('month', CURRENT_DATE)")
            : $db->query("SELECT COALESCE(SUM(amount),0) AS total FROM expenses WHERE expense_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')");
        return (float)($stmt->fetch()['total'] ?? 0);
    }

    /** Summary by category (last 30 days) */
    public static function summaryByCategory(): array
    {
        $db = Database::getInstance();
        $isPgsql = $db->getDriver() === 'pgsql';
        $stmt = $isPgsql
            ? $db->query("SELECT category, SUM(amount) AS total, COUNT(*) AS cnt FROM expenses WHERE expense_date >= (CURRENT_DATE - INTERVAL '30 days') GROUP BY category ORDER BY total DESC")
            : $db->query("SELECT category, SUM(amount) AS total, COUNT(*) AS cnt FROM expenses WHERE expense_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) GROUP BY category ORDER BY total DESC");
        return $stmt->fetchAll();
    }
}
