<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Product
{
    public static function all(bool $withCategory = true): array
    {
        $db = Database::getInstance();
        $sql = "SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.name ASC";
        if (!$withCategory) {
            $sql = "SELECT * FROM products ORDER BY name ASC";
        }
        $stmt = $db->query($sql);
        return $stmt->fetchAll();
    }

    /** Total product count (avoids loading all rows just to count). */
    public static function count(): int
    {
        $stmt = Database::getInstance()->query("SELECT COUNT(*) AS cnt FROM products");
        return (int) ($stmt->fetch()['cnt'] ?? 0);
    }

    /**
     * Paginated product list with category join.
     *
     * @return array{ data: array, total: int, page: int, perPage: int, pages: int }
     */
    public static function paginate(int $page = 1, int $perPage = 20): array
    {
        $page    = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $offset  = ($page - 1) * $perPage;
        $total   = self::count();

        $stmt = Database::getInstance()->query(
            "SELECT p.*, c.name AS category_name
               FROM products p
               LEFT JOIN categories c ON p.category_id = c.id
              ORDER BY p.name ASC
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
            "SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.id = :id LIMIT 1",
            [':id' => $id]
        );
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** Find product by SKU / barcode (exact match). */
    public static function findBySku(string $sku): ?array
    {
        $sku = trim($sku);
        if ($sku === '') {
            return null;
        }
        $db = Database::getInstance();
        $stmt = $db->query(
            "SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.sku = :sku LIMIT 1",
            [':sku' => $sku]
        );
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function create(array $data): int
    {
        $db = Database::getInstance();
        $db->query(
            "INSERT INTO products (category_id, name, sku, price, cost, quantity, low_stock_threshold, unit, description)
             VALUES (:category_id, :name, :sku, :price, :cost, :quantity, :low_stock_threshold, :unit, :description)",
            [
                ':category_id'         => !empty($data['category_id']) ? (int) $data['category_id'] : null,
                ':name'                => $data['name'],
                ':sku'                 => $data['sku'] ?? null,
                ':price'               => (float) ($data['price'] ?? 0),
                ':cost'                => (float) ($data['cost'] ?? 0),
                ':quantity'            => (int) ($data['quantity'] ?? 0),
                ':low_stock_threshold' => (int) ($data['low_stock_threshold'] ?? 5),
                ':unit'                => $data['unit'] ?? 'قطعة',
                ':description'         => $data['description'] ?? null,
            ]
        );
        return (int) $db->getConnection()->lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        $db = Database::getInstance();
        $stmt = $db->query(
            "UPDATE products SET category_id = :category_id, name = :name, sku = :sku, price = :price,
              cost = :cost, quantity = :quantity, low_stock_threshold = :low_stock_threshold,
              unit = :unit, description = :description
             WHERE id = :id",
            [
                ':id'                  => $id,
                ':category_id'         => !empty($data['category_id']) ? (int) $data['category_id'] : null,
                ':name'                => $data['name'],
                ':sku'                 => $data['sku'] ?? null,
                ':price'               => (float) ($data['price'] ?? 0),
                ':cost'                => (float) ($data['cost'] ?? 0),
                ':quantity'            => (int) ($data['quantity'] ?? 0),
                ':low_stock_threshold' => (int) ($data['low_stock_threshold'] ?? 5),
                ':unit'                => $data['unit'] ?? 'قطعة',
                ':description'         => $data['description'] ?? null,
            ]
        );
        return $stmt->rowCount() > 0;
    }

    public static function delete(int $id): bool
    {
        $db = Database::getInstance();
        $stmt = $db->query("DELETE FROM products WHERE id = :id", [':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public static function decrementStock(int $productId, int $qty): bool
    {
        $db = Database::getInstance();
        $stmt = $db->query(
            "UPDATE products SET quantity = quantity - :qty WHERE id = :id AND quantity >= :qty2",
            [':id' => $productId, ':qty' => $qty, ':qty2' => $qty]
        );
        return $stmt->rowCount() > 0;
    }

    public static function incrementStock(int $productId, int $qty): bool
    {
        $db = Database::getInstance();
        $stmt = $db->query(
            "UPDATE products SET quantity = quantity + :qty WHERE id = :id",
            [':id' => $productId, ':qty' => $qty]
        );
        return $stmt->rowCount() > 0;
    }

    /**
     * قائمة المنتجات لواجهة POS مع دعم البحث النصي في قاعدة البيانات.
     * يُعيد الحقول التي يحتاجها POS فقط (id, name, sku, price, quantity, low_stock_threshold).
     */
    public static function allForPos(string $search = '', int $limit = 300): array
    {
        $db     = Database::getInstance();
        $limit  = max(1, min(1000, $limit));

        if ($search !== '') {
            $like  = '%' . $search . '%';
            $stmt  = $db->query(
                "SELECT id, name, sku, price, quantity, low_stock_threshold
                   FROM products
                  WHERE name LIKE :like OR sku LIKE :like2
                  ORDER BY name ASC
                  LIMIT " . $limit,
                [':like' => $like, ':like2' => $like]
            );
        } else {
            $stmt = $db->query(
                "SELECT id, name, sku, price, quantity, low_stock_threshold
                   FROM products
                  ORDER BY name ASC
                  LIMIT " . $limit
            );
        }

        return $stmt->fetchAll();
    }

    /** البحث عن منتج بالاسم أو الرمز (SKU) — للأوتوكومبليت. */
    public static function search(string $q, int $limit = 10): array
    {
        $q = trim($q);
        if ($q === '') {
            return [];
        }
        $db = Database::getInstance();
        $like = '%' . $q . '%';
        $stmt = $db->query(
            "SELECT p.id, p.name, p.sku, p.price, p.quantity FROM products p
              WHERE (p.name LIKE :like OR p.sku LIKE :like2) AND p.quantity > 0
              ORDER BY p.name ASC LIMIT " . (int) $limit,
            [':like' => $like, ':like2' => $like]
        );
        return $stmt->fetchAll();
    }

    public static function countLowStock(): int
    {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT COUNT(*) AS cnt FROM products WHERE quantity <= low_stock_threshold AND low_stock_threshold > 0");
        $row = $stmt->fetch();
        return (int) ($row['cnt'] ?? 0);
    }
}
