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
];
require BASE_PATH . '/views/layouts/header.php';
require BASE_PATH . '/views/layouts/sidebar.php';
?>
<nav class="flex items-center gap-2 text-sm text-gray-500 mb-4">
    <a href="/dashboard" class="hover:text-blue-600 transition-colors">لوحة التحكم</a>
    <i class="fa-solid fa-chevron-left text-xs text-gray-400"></i>
    <span class="text-slate-700 font-medium">سجل النشاط</span>
</nav>
<div class="mb-6">
    <h1 class="text-2xl font-bold text-slate-800">سجل النشاط</h1>
    <p class="text-sm text-gray-500 mt-1">آخر 200 حدث في النظام</p>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">التاريخ والوقت</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">المستخدم</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">الإجراء</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">التفاصيل</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">IP</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if (empty($entries)): ?>
                <tr>
                    <td colspan="5" class="px-6 py-10 text-center text-gray-500">لا توجد أحداث مسجّلة بعد.</td>
                </tr>
                <?php else: ?>
                <?php foreach ($entries as $e): ?>
                <tr class="table-row-hover transition-colors duration-200">
                    <td class="px-6 py-4 text-sm text-gray-600"><?= htmlspecialchars(date('Y/m/d H:i', strtotime($e['created_at'] ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="px-6 py-4 text-sm font-medium text-slate-800"><?= htmlspecialchars($e['username'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="px-6 py-4 text-sm"><?= htmlspecialchars($actionLabels[$e['action']] ?? $e['action'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="px-6 py-4 text-sm text-gray-600"><?= htmlspecialchars($e['details'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="px-6 py-4 text-sm text-gray-500"><?= htmlspecialchars($e['ip_address'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require BASE_PATH . '/views/layouts/footer.php'; ?>
