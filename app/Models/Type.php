<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Type
{
    public static function all(): array
    {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT * FROM types ORDER BY name ASC");
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT * FROM types WHERE id = :id LIMIT 1", [':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function create(array $data): int
    {
        $db = Database::getInstance();
        $db->query(
            "INSERT INTO types (name, description) VALUES (:name, :description)",
            [
                ':name'        => $data['name'],
                ':description' => $data['description'] ?? null,
            ]
        );
        return (int) $db->getConnection()->lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        $db = Database::getInstance();
        $stmt = $db->query(
            "UPDATE types SET name = :name, description = :description WHERE id = :id",
            [
                ':id'          => $id,
                ':name'        => $data['name'],
                ':description' => $data['description'] ?? null,
            ]
        );
        return $stmt->rowCount() > 0;
    }

    public static function delete(int $id): bool
    {
        $db = Database::getInstance();
        $stmt = $db->query("DELETE FROM types WHERE id = :id", [':id' => $id]);
        return $stmt->rowCount() > 0;
    }
}
