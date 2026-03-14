<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\ActivityLog;
use App\Models\Product;
use App\Models\Sale;

class ReturnService
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public static function createReturn(int $userId, int $saleId, array $returnItems, ?string $reason = null): int
    {
        $instance = new self();
        return $instance->processReturn($userId, $saleId, $returnItems, $reason);
    }

    private function processReturn(int $userId, int $saleId, array $returnItems, ?string $reason): int
    {
        if (empty($returnItems) || !is_array($returnItems)) {
            throw new \InvalidArgumentException('قائمة المرتجعات فارغة');
        }

        $sale = Sale::find($saleId);
        if (!$sale) {
            throw new \InvalidArgumentException("الفاتورة رقم $saleId غير موجودة");
        }

        if ($sale['status'] === 'cancelled') {
            throw new \InvalidArgumentException('لا يمكن إرجاع فاتورة ملغاة');
        }

        $validItems = $this->validateReturnItems($saleId, $returnItems);

        $totalRefund = array_sum(array_column($validItems, 'refund_amount'));

        $this->db->beginTransaction();

        try {
            $returnNumber = $this->generateReturnNumberLocked();

            $this->db->query(
                "INSERT INTO returns (sale_id, user_id, return_number, total_refund, reason)
                 VALUES (:sale_id, :user_id, :return_number, :total_refund, :reason)",
                [
                    ':sale_id'      => $saleId,
                    ':user_id'      => $userId,
                    ':return_number'=> $returnNumber,
                    ':total_refund' => $totalRefund,
                    ':reason'       => $reason ?: null,
                ]
            );

            $returnId = $this->db->lastInsertId();

            foreach ($validItems as $item) {
                $this->db->query(
                    "INSERT INTO return_items (return_id, product_id, quantity, refund_amount)
                     VALUES (:return_id, :product_id, :quantity, :refund_amount)",
                    [
                        ':return_id'     => $returnId,
                        ':product_id'    => $item['product_id'],
                        ':quantity'      => $item['quantity'],
                        ':refund_amount' => $item['refund_amount'],
                    ]
                );

                $this->incrementStock($item['product_id'], $item['quantity']);
            }

            ActivityLog::log(
                'return.create',
                'return',
                $returnId,
                "إرجاع فاتورة #$saleId - المبلغ: $totalRefund",
                $userId
            );

            $this->db->commit();

            return $returnId;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    private function validateReturnItems(int $saleId, array $returnItems): array
    {
        $requestedProducts = [];
        foreach ($returnItems as $item) {
            $productId = (int) ($item['product_id'] ?? 0);
            $qty = (int) ($item['quantity'] ?? 0);
            $unitPrice = (float) ($item['unit_price'] ?? 0);

            if ($productId <= 0 || $qty <= 0) {
                throw new \InvalidArgumentException('بيانات المنتجات غير صالحة');
            }

            $requestedProducts[$productId] = [
                'quantity'     => $qty,
                'unit_price'   => $unitPrice,
            ];
        }

        if (empty($requestedProducts)) {
            throw new \InvalidArgumentException('قائمة المرتجعات فارغة');
        }

        $stmt = $this->db->query(
            "SELECT product_id, quantity, unit_price FROM sale_items WHERE sale_id = :sale_id",
            [':sale_id' => $saleId]
        );
        $saleItems = $stmt->fetchAll();

        $soldByProductId = [];
        foreach ($saleItems as $si) {
            $pid = (int) $si['product_id'];
            $soldByProductId[$pid] = [
                'quantity'   => (int) $si['quantity'],
                'unit_price' => (float) $si['unit_price'],
            ];
        }

        $validItems = [];
        foreach ($requestedProducts as $productId => $data) {
            if (!isset($soldByProductId[$productId])) {
                throw new \InvalidArgumentException("المنتج رقم $productId غير موجود في الفاتورة الأصلية");
            }

            $soldQty = $soldByProductId[$productId]['quantity'];
            $requestedQty = $data['quantity'];

            if ($requestedQty > $soldQty) {
                throw new \InvalidArgumentException(
                    "الكمية المراد إرجاعها للمنتج رقم $productId ($requestedQty) أكبر من الكمية المباعة ($soldQty)"
                );
            }

            $validItems[] = [
                'product_id'    => $productId,
                'quantity'      => $requestedQty,
                'refund_amount' => $requestedQty * $data['unit_price'],
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

    private function generateReturnNumberLocked(): string
    {
        $year = date('Y');

        $stmt = $this->db->query(
            "SELECT id, return_number FROM returns WHERE return_number LIKE :prefix ORDER BY id DESC LIMIT 1 FOR UPDATE",
            [':prefix' => "RET-{$year}-%"]
        );
        $row = $stmt->fetch();

        if (!$row || empty($row['return_number'])) {
            return "RET-{$year}-001";
        }

        preg_match('/RET-\d+-(\d+)/', $row['return_number'], $matches);
        $num = (int) ($matches[1] ?? 0) + 1;

        return sprintf("RET-%s-%03d", $year, $num);
    }
}