<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Category
{
    public static function all(): array
    {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT * FROM categories ORDER BY name ASC");
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT * FROM categories WHERE id = :id LIMIT 1", [':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function create(array $data): int
    {
        $slug = self::slugify($data['name'] ?? '');
        $db = Database::getInstance();
        $db->query(
            "INSERT INTO categories (name, slug, description) VALUES (:name, :slug, :description)",
            [
                ':name' => $data['name'],
                ':slug' => $slug,
                ':description' => $data['description'] ?? null,
            ]
        );
        return (int) $db->getConnection()->lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        $slug = self::slugify($data['name'] ?? '');
        $db = Database::getInstance();
        $stmt = $db->query(
            "UPDATE categories SET name = :name, slug = :slug, description = :description WHERE id = :id",
            [
                ':id' => $id,
                ':name' => $data['name'],
                ':slug' => $slug,
                ':description' => $data['description'] ?? null,
            ]
        );
        return $stmt->rowCount() > 0;
    }

    public static function delete(int $id): bool
    {
        $db = Database::getInstance();
        $stmt = $db->query("DELETE FROM categories WHERE id = :id", [':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    private static function slugify(string $text): string
    {
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        $text = preg_replace('~[^-\w]+~', '', $text);
        $text = strtolower(trim($text, '-'));
        return $text ?: 'cat-' . bin2hex(random_bytes(4));
    }
}
