<?php
$actionLabels = [
    'login'          => 'تسجيل دخول',
    'logout'         => 'تسجيل خروج',
    'product.create' => 'إضافة منتج',
    'product.update' => 'تعديل منتج',
    'product.delete' => 'حذف منتج',
    'user.create'    => 'إضافة مستخدم',
    'user.update'    => 'تعديل مستخدم',
    'user.delete'    => 'حذف مستخدم',
    'user.password'  => 'تغيير كلمة مرور',
    'purchase.create'=> 'تسجيل مشتريات',
    'expense.create' => 'إضافة مصروف',
    'expense.update' => 'تعديل مصروف',
    'expense.delete' => 'حذف مصروف',
];
require BASE_PATH . '/views/layouts/header.php';
require BASE_PATH . '/views/layouts/sidebar.php';
?>
<nav class="flex items-center gap-2 text-sm mb-4" style="color: rgb(var(--muted-foreground));" aria-label="مسار التنقل">
    <a href="/dashboard" class="hover:opacity-80 transition-colors" style="color: rgb(var(--accent));">لوحة التحكم</a>
    <i class="fa-solid fa-chevron-left text-xs" aria-hidden="true"></i>
    <span class="font-medium" style="color: rgb(var(--foreground));">سجل النشاط</span>
</nav>

<header class="page-header">
    <h1 class="page-title">سجل النشاط</h1>
    <p class="page-subtitle">آخر 200 حدث في النظام</p>
</header>

<div class="app-card-flat overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y" style="border-color: rgb(var(--border));">
            <thead style="background: rgb(var(--muted));">
                <tr>
                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider" style="color: rgb(var(--muted-foreground));">التاريخ والوقت</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider" style="color: rgb(var(--muted-foreground));">المستخدم</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider" style="color: rgb(var(--muted-foreground));">الإجراء</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider" style="color: rgb(var(--muted-foreground));">التفاصيل</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider" style="color: rgb(var(--muted-foreground));">IP</th>
                </tr>
            </thead>
            <tbody class="divide-y" style="border-color: rgb(var(--border));">
                <?php if (empty($entries)): ?>
                <tr><td colspan="5" class="px-6 py-16">
                    <div class="empty-state">
                        <div class="empty-state-icon mx-auto"><i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i></div>
                        <p class="font-medium">لا توجد أحداث مسجّلة بعد.</p>
                    </div>
                </td></tr>
                <?php else: ?>
                <?php foreach ($entries as $e): ?>
                <tr class="app-table-row transition-colors duration-200">
                    <td class="px-6 py-4 text-sm" style="color: rgb(var(--muted-foreground));"><?= htmlspecialchars(date('Y/m/d H:i', strtotime($e['created_at'] ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="px-6 py-4 text-sm font-medium" style="color: rgb(var(--foreground));"><?= htmlspecialchars($e['username'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="px-6 py-4 text-sm font-medium" style="color: rgb(var(--foreground));"><?= htmlspecialchars($actionLabels[$e['action']] ?? $e['action'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="px-6 py-4 text-sm" style="color: rgb(var(--muted-foreground));"><?= htmlspecialchars($e['details'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="px-6 py-4 text-sm font-mono" style="color: rgb(var(--muted-foreground));"><?= htmlspecialchars($e['ip_address'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require BASE_PATH . '/views/layouts/footer.php'; ?>
