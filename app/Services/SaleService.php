<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\ActivityLog;
use App\Models\Product;

class SaleService
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public static function createSale(int $userId, array $data): int
    {
        $instance = new self();
        return $instance->processSale($userId, $data);
    }

    private function processSale(int $userId, array $data): int
    {
        $items = $data['items'] ?? [];
        $customerName = $data['customer_name'] ?? 'عميل نقدي';
        $paymentMethod = in_array($data['payment_method'] ?? '', ['cash', 'card', 'mixed']) 
            ? $data['payment_method'] 
            : 'cash';
        $discount = max(0.0, (float) ($data['discount'] ?? 0));
        $notes = $data['notes'] ?? '';

        if (empty($items) || !is_array($items)) {
            throw new \InvalidArgumentException('الفاتورة فارغة');
        }

        $validItems = $this->validateAndPrepareItems($items);

        $invoiceNumber = $this->generateInvoiceNumber();
        $subtotal = array_sum(array_column($validItems, 'total'));
        $total = max(0, $subtotal - $discount);

        $this->db->beginTransaction();

        try {
            $this->db->query(
                "INSERT INTO sales (user_id, invoice_number, customer_name, total, discount, notes, payment_method, status)
                 VALUES (:user_id, :invoice_number, :customer_name, :total, :discount, :notes, :payment_method, 'paid')",
                [
                    ':user_id'        => $userId,
                    ':invoice_number' => $invoiceNumber,
                    ':customer_name'  => $customerName,
                    ':total'          => $total,
                    ':discount'       => $discount,
                    ':notes'          => $notes ?: null,
                    ':payment_method' => $paymentMethod,
                ]
            );

            $saleId = $this->db->lastInsertId();

            foreach ($validItems as $item) {
                $this->db->query(
                    "INSERT INTO sale_items (sale_id, product_id, quantity, unit_price, total)
                     VALUES (:sale_id, :product_id, :quantity, :unit_price, :total)",
                    [
                        ':sale_id'    => $saleId,
                        ':product_id' => $item['product_id'],
                        ':quantity'   => $item['quantity'],
                        ':unit_price' => $item['unit_price'],
                        ':total'      => $item['total'],
                    ]
                );

                $this->decrementStock($item['product_id'], $item['quantity']);
            }

            ActivityLog::log('sale.create', 'sale', $saleId, "فاتورة للعميل: $customerName", $userId);

            $this->db->commit();

            return $saleId;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    private function validateAndPrepareItems(array $items): array
    {
        $validItems = [];

        foreach ($items as $item) {
            $productId = (int) ($item['product_id'] ?? 0);
            $qty = (int) ($item['quantity'] ?? 0);

            if ($productId <= 0 || $qty <= 0) {
                throw new \InvalidArgumentException('بيانات المنتجات غير صالحة');
            }

            $product = Product::find($productId);
            if (!$product) {
                throw new \InvalidArgumentException("المنتج رقم $productId غير موجود");
            }

            if ($product['quantity'] < $qty) {
                throw new \InvalidArgumentException(
                    "الكمية المتوفرة من {$product['name']} غير كافية (المتوفر: {$product['quantity']})"
                );
            }

            $price = (float) $product['price'];
            $validItems[] = [
                'product_id' => $productId,
                'quantity'   => $qty,
                'unit_price' => $price,
                'total'      => $qty * $price,
            ];
        }

        return $validItems;
    }

    private function decrementStock(int $productId, int $qty): void
    {
        $stmt = $this->db->query(
            "UPDATE products SET quantity = quantity - :qty WHERE id = :id AND quantity >= :qty2",
            [':id' => $productId, ':qty' => $qty, ':qty2' => $qty]
        );

        if ($stmt->rowCount() === 0) {
            throw new \RuntimeException("فشل في تحديث المخزون للمنتج رقم $productId");
        }
    }

    private function generateInvoiceNumber(): string
    {
        $year = date('Y');
        $stmt = $this->db->query(
            "SELECT invoice_number FROM sales WHERE invoice_number LIKE :prefix ORDER BY id DESC LIMIT 1",
            [':prefix' => "INV-{$year}-%"]
        );
        $row = $stmt->fetch();

        if (!$row) {
            return "INV-{$year}-001";
        }

        preg_match('/INV-\d+-(\d+)/', $row['invoice_number'], $matches);
        $num = (int) ($matches[1] ?? 0) + 1;

        return sprintf("INV-%s-%03d", $year, $num);
    }
}
