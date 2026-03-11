<?php
/**
 * وصل / إيصال مبيعات — صفحة طباعة
 * Receipt / Invoice print view
 *
 * Variables expected:
 *   $sale        — sale row (from Sale::find)
 *   $items       — sale items (from Sale::getItems)
 *   $appSettings — merged app settings array
 */
$currencySymbol = htmlspecialchars($appSettings['currency_symbol'] ?? 'د.ع', ENT_QUOTES, 'UTF-8');
$appName        = htmlspecialchars($appSettings['app_name'] ?? 'نظام المخزون', ENT_QUOTES, 'UTF-8');
$invoiceNo      = htmlspecialchars($sale['invoice_number'] ?? '#' . $sale['id'], ENT_QUOTES, 'UTF-8');
$customerName   = htmlspecialchars($sale['customer_name'] ?? 'عميل نقدي', ENT_QUOTES, 'UTF-8');
$cashierName    = htmlspecialchars($sale['cashier_name'] ?? '—', ENT_QUOTES, 'UTF-8');
$paymentMethod  = $sale['payment_method'] ?? 'cash';
$payLabel       = $paymentMethod === 'card' ? 'بطاقة' : ($paymentMethod === 'mixed' ? 'مختلط' : 'نقدي');
$discount       = (float) ($sale['discount'] ?? 0);
$total          = (float) ($sale['total'] ?? 0);
$notes          = htmlspecialchars($sale['notes'] ?? '', ENT_QUOTES, 'UTF-8');
$createdAt      = $sale['created_at'] ?? '';
$dateStr        = $createdAt ? date('Y/m/d', strtotime($createdAt)) : '';
$timeStr        = $createdAt ? date('h:i A', strtotime($createdAt)) : '';

$subtotal = 0;
foreach ($items as $it) {
    $subtotal += (float) $it['total'];
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>وصل #<?= $invoiceNo ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* ─── Base ─── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'IBM Plex Sans Arabic', 'Segoe UI', sans-serif;
            background: #f1f5f9;
            color: #1e293b;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* ─── Screen-only toolbar ─── */
        .toolbar {
            position: fixed;
            bottom: 0;
            left: 0; right: 0;
            z-index: 100;
            background: linear-gradient(to top, rgba(15,23,42,.95), rgba(15,23,42,.85));
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            padding: 14px 20px;
            display: flex;
            justify-content: center;
            gap: 12px;
            border-top: 1px solid rgba(255,255,255,.08);
        }
        .toolbar .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 28px;
            border: none;
            border-radius: 14px;
            font-size: 14px;
            font-weight: 700;
            font-family: inherit;
            cursor: pointer;
            transition: all .2s ease;
            text-decoration: none;
        }
        .toolbar .btn:active { transform: scale(.97); }
        .btn-print {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: #fff;
            box-shadow: 0 4px 14px rgba(37,99,235,.35);
        }
        .btn-print:hover { box-shadow: 0 6px 20px rgba(37,99,235,.45); }
        .btn-back {
            background: rgba(255,255,255,.1);
            color: #e2e8f0;
            border: 1px solid rgba(255,255,255,.15);
        }
        .btn-back:hover { background: rgba(255,255,255,.18); }

        /* ─── Receipt card ─── */
        .receipt-wrapper {
            max-width: 400px;
            margin: 30px auto 100px;
            padding: 0 16px;
        }
        .receipt {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 8px 40px rgba(0,0,0,.08), 0 1px 3px rgba(0,0,0,.04);
            overflow: hidden;
            position: relative;
        }
        /* Decorative top bar */
        .receipt::before {
            content: '';
            display: block;
            height: 6px;
            background: linear-gradient(90deg, #3b82f6, #8b5cf6, #ec4899);
        }

        /* Header */
        .receipt-header {
            text-align: center;
            padding: 28px 24px 20px;
            border-bottom: 2px dashed #e2e8f0;
        }
        .receipt-header .store-name {
            font-size: 22px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 4px;
        }
        .receipt-header .invoice-label {
            font-size: 12px;
            font-weight: 600;
            color: #94a3b8;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-bottom: 10px;
        }
        .receipt-header .invoice-number {
            display: inline-block;
            background: linear-gradient(135deg, #eff6ff, #eef2ff);
            color: #2563eb;
            font-size: 15px;
            font-weight: 700;
            padding: 6px 18px;
            border-radius: 10px;
            border: 1px solid #dbeafe;
        }

        /* Meta rows */
        .receipt-meta {
            padding: 16px 24px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px 16px;
            border-bottom: 1px solid #f1f5f9;
        }
        .meta-item {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        .meta-label {
            font-size: 10px;
            font-weight: 600;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .meta-value {
            font-size: 13px;
            font-weight: 600;
            color: #334155;
        }

        /* Items table */
        .receipt-items {
            padding: 12px 24px 16px;
        }
        .receipt-items table {
            width: 100%;
            border-collapse: collapse;
        }
        .receipt-items thead th {
            font-size: 10px;
            font-weight: 700;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 8px 0;
            border-bottom: 1px solid #e2e8f0;
            text-align: right;
        }
        .receipt-items thead th:last-child { text-align: left; }
        .receipt-items thead th.center { text-align: center; }
        .receipt-items tbody td {
            padding: 10px 0;
            font-size: 13px;
            color: #334155;
            border-bottom: 1px solid #f8fafc;
            text-align: right;
            vertical-align: top;
        }
        .receipt-items tbody td:last-child { text-align: left; font-weight: 600; }
        .receipt-items tbody td.center { text-align: center; }
        .receipt-items .product-name {
            font-weight: 600;
            color: #1e293b;
            line-height: 1.4;
        }
        .receipt-items .unit-price {
            font-size: 11px;
            color: #94a3b8;
            margin-top: 2px;
        }

        /* Totals */
        .receipt-totals {
            padding: 0 24px 20px;
            border-top: 2px dashed #e2e8f0;
            margin-top: 4px;
            padding-top: 16px;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 5px 0;
            font-size: 13px;
            color: #64748b;
        }
        .total-row.discount { color: #ea580c; }
        .total-row.grand {
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
            padding: 12px 0 4px;
            margin-top: 8px;
            border-top: 2px solid #e2e8f0;
        }
        .total-row .label { font-weight: 500; }
        .total-row .amount { font-weight: 600; }
        .total-row.grand .amount {
            color: #2563eb;
            font-size: 20px;
        }

        /* Payment badge */
        .payment-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 14px;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 700;
        }
        .payment-badge.cash { background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; }
        .payment-badge.card { background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; }
        .payment-badge.mixed { background: #fef3c7; color: #d97706; border: 1px solid #fde68a; }

        /* Notes */
        .receipt-notes {
            margin: 0 24px;
            padding: 10px 14px;
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 10px;
            font-size: 12px;
            color: #92400e;
            margin-bottom: 16px;
        }
        .receipt-notes i { margin-left: 6px; }

        /* Footer */
        .receipt-footer {
            text-align: center;
            padding: 20px 24px 26px;
            border-top: 2px dashed #e2e8f0;
        }
        .receipt-footer .thanks {
            font-size: 15px;
            font-weight: 700;
            color: #334155;
            margin-bottom: 4px;
        }
        .receipt-footer .powered {
            font-size: 10px;
            color: #cbd5e1;
            letter-spacing: 1px;
        }

        /* ─── Print styles ─── */
        @media print {
            body { background: #fff; }
            .toolbar { display: none !important; }
            .receipt-wrapper {
                max-width: 80mm;
                margin: 0 auto;
                padding: 0;
            }
            .receipt {
                box-shadow: none;
                border-radius: 0;
            }
            .receipt::before { height: 3px; }
            .receipt-header { padding: 16px 12px 12px; }
            .receipt-header .store-name { font-size: 18px; }
            .receipt-meta { padding: 10px 12px; gap: 6px 10px; }
            .receipt-items { padding: 8px 12px; }
            .receipt-items thead th,
            .receipt-items tbody td { font-size: 11px; padding: 6px 0; }
            .receipt-totals { padding: 10px 12px 14px; }
            .receipt-footer { padding: 14px 12px; }
            .receipt-footer .thanks { font-size: 13px; }

            @page {
                size: 80mm auto;
                margin: 0;
            }
        }

        /* Small screen receipt */
        @media (max-width: 440px) {
            .receipt-wrapper { padding: 0 8px; margin-top: 16px; }
            .receipt-header { padding: 20px 16px 16px; }
            .receipt-meta { padding: 12px 16px; }
            .receipt-items { padding: 10px 16px; }
            .receipt-totals { padding: 0 16px 16px; padding-top: 12px; }
            .receipt-footer { padding: 16px; }
        }
    </style>
</head>
<body>

<div class="receipt-wrapper">
    <div class="receipt">

        <!-- Header -->
        <div class="receipt-header">
            <div class="store-name"><?= $appName ?></div>
            <div class="invoice-label">فاتورة مبيعات</div>
            <div class="invoice-number"><?= $invoiceNo ?></div>
        </div>

        <!-- Meta Info -->
        <div class="receipt-meta">
            <div class="meta-item">
                <span class="meta-label">التاريخ</span>
                <span class="meta-value"><?= $dateStr ?></span>
            </div>
            <div class="meta-item">
                <span class="meta-label">الوقت</span>
                <span class="meta-value"><?= $timeStr ?></span>
            </div>
            <div class="meta-item">
                <span class="meta-label">العميل</span>
                <span class="meta-value"><?= $customerName ?></span>
            </div>
            <div class="meta-item">
                <span class="meta-label">الكاشير</span>
                <span class="meta-value"><?= $cashierName ?></span>
            </div>
            <div class="meta-item">
                <span class="meta-label">طريقة الدفع</span>
                <span class="meta-value">
                    <span class="payment-badge <?= $paymentMethod ?>">
                        <i class="fa-solid fa-<?= $paymentMethod === 'card' ? 'credit-card' : ($paymentMethod === 'mixed' ? 'shuffle' : 'money-bill') ?>"></i>
                        <?= $payLabel ?>
                    </span>
                </span>
            </div>
        </div>

        <!-- Items -->
        <div class="receipt-items">
            <table>
                <thead>
                    <tr>
                        <th>المنتج</th>
                        <th class="center">الكمية</th>
                        <th>المجموع</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $it):
                        $itemTotal = (float) $it['total'];
                        $unitPrice = (float) $it['unit_price'];
                        $qty       = (int)   $it['quantity'];
                    ?>
                    <tr>
                        <td>
                            <div class="product-name"><?= htmlspecialchars($it['product_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="unit-price"><?= $currencySymbol ?> <?= number_format($unitPrice, 0) ?> × <?= $qty ?></div>
                        </td>
                        <td class="center"><?= $qty ?></td>
                        <td><?= $currencySymbol ?> <?= number_format($itemTotal, 0) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Totals -->
        <div class="receipt-totals">
            <?php if ($discount > 0): ?>
            <div class="total-row">
                <span class="label">المجموع الفرعي</span>
                <span class="amount"><?= $currencySymbol ?> <?= number_format($subtotal, 0) ?></span>
            </div>
            <div class="total-row discount">
                <span class="label"><i class="fa-solid fa-tag"></i> الخصم</span>
                <span class="amount">− <?= $currencySymbol ?> <?= number_format($discount, 0) ?></span>
            </div>
            <?php endif; ?>
            <div class="total-row grand">
                <span class="label">الإجمالي</span>
                <span class="amount"><?= $currencySymbol ?> <?= number_format($total, 0) ?></span>
            </div>
        </div>

        <!-- Notes -->
        <?php if ($notes): ?>
        <div class="receipt-notes">
            <i class="fa-solid fa-note-sticky"></i>
            <?= $notes ?>
        </div>
        <?php endif; ?>

        <!-- Footer -->
        <div class="receipt-footer">
            <p class="thanks">شكراً لتعاملكم معنا 🙏</p>
            <p class="powered"><?= $appName ?></p>
        </div>

    </div>
</div>

<!-- Toolbar (screen only) -->
<div class="toolbar">
    <button type="button" class="btn btn-print" onclick="window.print()">
        <i class="fa-solid fa-print"></i>
        طباعة الوصل
    </button>
    <a href="/sales" class="btn btn-back">
        <i class="fa-solid fa-arrow-right"></i>
        العودة
    </a>
</div>

</body>
</html>
