<?php

declare(strict_types=1);

namespace App\Validators;

use App\Helpers\Security;

class ProductValidator
{
    /**
     * Validate and normalise product input.
     *
     * @return array{
     *     valid: bool,
     *     errors: array<int,string>,
     *     data: array<string,mixed>
     * }
     */
    public static function validate(array $input): array
    {
        $errors = [];

        $name = trim((string) ($input['name'] ?? ''));
        if ($name === '') {
            $errors[] = 'Product name is required';
        }

        $price = (float) ($input['price'] ?? 0);
        if ($price < 0) {
            $errors[] = 'Price cannot be negative';
        }

        $cost = (float) ($input['cost'] ?? 0);
        if ($cost < 0) {
            $errors[] = 'Cost cannot be negative';
        }

        $quantity = (int) ($input['quantity'] ?? 0);
        if ($quantity < 0) {
            $errors[] = 'Quantity cannot be negative';
        }

        $lowStockThreshold = (int) ($input['low_stock_threshold'] ?? 5);
        if ($lowStockThreshold < 0) {
            $errors[] = 'Low stock threshold cannot be negative';
        }

        $categoryId = !empty($input['category_id']) ? (int) $input['category_id'] : null;
        $typeId     = !empty($input['type_id']) ? (int) $input['type_id'] : null;

        $data = [
            'name'                => Security::sanitizeString($name),
            'category_id'         => $categoryId,
            'type_id'             => $typeId,
            'sku'                 => isset($input['sku']) ? Security::sanitizeString((string) $input['sku']) : null,
            'price'               => $price,
            'cost'                => $cost,
            'quantity'            => $quantity,
            'low_stock_threshold' => $lowStockThreshold,
            'unit'                => isset($input['unit']) ? Security::sanitizeString((string) $input['unit']) : 'قطعة',
            'description'         => isset($input['description']) ? Security::sanitizeString((string) $input['description']) : null,
        ];

        return [
            'valid'  => empty($errors),
            'errors' => $errors,
            'data'   => $data,
        ];
    }
}

