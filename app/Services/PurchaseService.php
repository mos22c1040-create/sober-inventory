<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\ActivityLog;
use App\Models\Product;

class PurchaseService
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public static function createPurchase(int $userId, array $data): int
    {
        $instance = new self();
        return $instance->processPurchase($userId, $data);
    }

    private function processPurchase(int $userId, array $data): int
    {
        $items = $data['items'] ?? [];
        $supplier = trim($data['supplier'] ?? '');

        if (empty($items) || !is_array($items)) {
            throw new \InvalidArgumentException('قائمة المشتريات فارغة');
        }

        $validItems = $this->validateAndPrepareItems($items);

        $total = array_sum(array_column($validItems, 'total'));

        $this->db->beginTransaction();

        try {
            $poNumber = $this->generatePurchaseOrderNumberLocked();

            $this->db->query(
                "INSERT INTO purchases (user_id, purchase_number, supplier, total, status)
                 VALUES (:user_id, :purchase_number, :supplier, :total, 'completed')",
                [
                    ':user_id'         => $userId,
                    ':purchase_number' => $poNumber,
                    ':supplier'        => $supplier ?: null,
                    ':total'           => $total,
                ]
            );

            $purchaseId = $this->db->lastInsertId();

            foreach ($validItems as $item) {
                $this->db->query(
                    "INSERT INTO purchase_items (purchase_id, product_id, quantity, unit_cost, total)
                     VALUES (:purchase_id, :product_id, :quantity, :unit_cost, :total)",
                    [
                        ':purchase_id' => $purchaseId,
                        ':product_id'  => $item['product_id'],
                        ':quantity'    => $item['quantity'],
                        ':unit_cost'   => $item['unit_cost'],
                        ':total'       => $item['total'],
                    ]
                );

                $this->incrementStock($item['product_id'], $item['quantity']);
            }

            ActivityLog::log('purchase.create', 'purchase', $purchaseId, $supplier ?: '—');

            $this->db->commit();

            return $purchaseId;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    private function validateAndPrepareItems(array $items): array
    {
        $productIds = [];
        foreach ($items as $item) {
            $productId = (int) ($item['product_id'] ?? 0);
            $qty = (int) ($item['quantity'] ?? 0);
            $unitCost = (float) ($item['unit_cost'] ?? 0);

            if ($productId <= 0 || $qty <= 0) {
                throw new \InvalidArgumentException('بيانات المنتجات غير صالحة');
            }

            $productIds[$productId] = [
                'quantity'  => $qty,
                'unit_cost' => $unitCost,
            ];
        }

        if (empty($productIds)) {
            throw new \InvalidArgumentException('قائمة المشتريات فارغة');
        }

        $placeholders = [];
        $params = [];
        $index = 0;
        foreach (array_keys($productIds) as $id) {
            $placeholders[] = ":pid{$index}";
            $params[":pid{$index}"] = $id;
            $index++;
        }

        $inClause = implode(',', $placeholders);
        $stmt = $this->db->query(
            "SELECT id, name FROM products WHERE id IN ($inClause)",
            $params
        );
        $products = $stmt->fetchAll();
        $productsById = [];
        foreach ($products as $p) {
            $productsById[(int) $p['id']] = $p;
        }

        $validItems = [];
        foreach ($productIds as $productId => $itemData) {
            if (!isset($productsById[$productId])) {
                throw new \InvalidArgumentException("المنتج رقم $productId غير موجود");
            }

            $product = $productsById[$productId];
            $qty = $itemData['quantity'];
            $unitCost = $itemData['unit_cost'];

            $validItems[] = [
                'product_id' => $productId,
                'quantity'   => $qty,
                'unit_cost'  => $unitCost,
                'total'      => $qty * $unitCost,
            ];
        }

        return $validItems;
    }

    private function incrementStock(int $productId, int $qty): void
    {
        $stmt = $this->db->query(
            "UPDATE products SET quantity = quantity + :qty WHERE id = :id",
            [':id' => $productId, ':qty' => $qty]
        );

        if ($stmt->rowCount() === 0) {
            throw new \RuntimeException("فشل في تحديث المخزون للمنتج رقم $productId");
        }
    }

    private function generatePurchaseOrderNumberLocked(): string
    {
        $year = date('Y');

        $stmt = $this->db->query(
            "SELECT id, purchase_number FROM purchases WHERE purchase_number LIKE :prefix ORDER BY id DESC LIMIT 1 FOR UPDATE",
            [':prefix' => "PO-{$year}-%"]
        );
        $row = $stmt->fetch();

        if (!$row || empty($row['purchase_number'])) {
            return "PO-{$year}-001";
        }

        preg_match('/PO-\d+-(\d+)/', $row['purchase_number'], $matches);
        $num = (int) ($matches[1] ?? 0) + 1;

        return sprintf("PO-%s-%03d", $year, $num);
    }
}