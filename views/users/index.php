<?php require BASE_PATH . '/views/layouts/header.php'; ?>
<?php require BASE_PATH . '/views/layouts/sidebar.php'; ?>

<nav class="flex items-center gap-2 text-sm mb-4" style="color: rgb(var(--muted-foreground));" aria-label="مسار التنقل">
    <a href="/dashboard" class="hover:opacity-80 transition-colors" style="color: rgb(var(--accent));">لوحة التحكم</a>
    <i class="fa-solid fa-chevron-left text-xs" aria-hidden="true"></i>
    <span class="font-medium" style="color: rgb(var(--foreground));">المستخدمون</span>
</nav>

<div class="flex flex-wrap justify-between items-start gap-4 mb-6">
    <header class="page-header mb-0">
        <h1 class="page-title">المستخدمون</h1>
        <p class="page-subtitle">إدارة حسابات المديرين والكاشير</p>
    </header>
    <a href="/users/create" class="inline-flex items-center min-h-[44px] px-5 py-2.5 rounded-lg text-sm font-medium btn-primary focus:ring-2 focus:ring-offset-2 transition-colors duration-200 cursor-pointer" style="background: rgb(var(--primary)); color: rgb(var(--primary-foreground));">
        <i class="fa-solid fa-user-plus ms-2" aria-hidden="true"></i> إضافة مستخدم
    </a>
</div>

<div id="alert-box" class="hidden rounded-lg p-4 mb-6 border text-sm font-medium" role="alert" aria-live="polite">
    <span id="alert-icon" class="me-2"></span>
    <span id="alert-message"></span>
</div>

<div class="app-card-flat overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y" style="border-color: rgb(var(--border));">
            <thead style="background: rgb(var(--muted));">
                <tr>
                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider" style="color: rgb(var(--muted-foreground));">#</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider" style="color: rgb(var(--muted-foreground));">الاسم</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider" style="color: rgb(var(--muted-foreground));">البريد الإلكتروني</th>
                    <th class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider" style="color: rgb(var(--muted-foreground));">الدور</th>
                    <th class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider" style="color: rgb(var(--muted-foreground));">الحالة</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider" style="color: rgb(var(--muted-foreground));">تاريخ الإنشاء</th>
                    <th class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider" style="color: rgb(var(--muted-foreground));">إجراءات</th>
                </tr>
            </thead>
            <tbody class="divide-y" style="border-color: rgb(var(--border));" id="users-tbody">
                <?php if (empty($users)): ?>
                <tr><td colspan="7" class="px-6 py-16">
                    <div class="empty-state">
                        <div class="empty-state-icon mx-auto"><i class="fa-solid fa-users-gear" aria-hidden="true"></i></div>
                        <p class="font-medium">لا يوجد مستخدمون بعد.</p>
                        <a href="/users/create" class="inline-flex items-center gap-2 mt-3 text-sm font-bold" style="color: rgb(var(--primary));"><i class="fa-solid fa-user-plus" aria-hidden="true"></i> إضافة مستخدم</a>
                    </div>
                </td></tr>
                <?php else: ?>
                <?php foreach ($users as $i => $u): ?>
                <tr class="app-table-row transition-colors duration-200" id="row-<?= (int)$u['id'] ?>">
                    <td class="px-6 py-4 text-sm" style="color: rgb(var(--muted-foreground));"><?= $i + 1 ?></td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold shrink-0" style="background: rgb(var(--primary)); color: rgb(var(--primary-foreground));">
                                <?= mb_strtoupper(mb_substr($u['username'], 0, 1, 'UTF-8'), 'UTF-8') ?>
                            </div>
                            <span class="text-sm font-medium" style="color: rgb(var(--foreground));">
                                <?= htmlspecialchars($u['username'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-sm" style="color: rgb(var(--muted-foreground));">
                        <?= htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8') ?>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <?php if ($u['role'] === 'admin'): ?>
                        <span class="badge bg-purple-100 text-purple-700">
                            <i class="fa-solid fa-crown text-[10px]" aria-hidden="true"></i> مدير
                        </span>
                        <?php else: ?>
                        <span class="badge" style="background: rgb(219 234 254); color: rgb(29 78 216);">
                            <i class="fa-solid fa-cash-register text-[10px]" aria-hidden="true"></i> كاشير
                        </span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <?php if ($u['status'] === 'active'): ?>
                        <span class="badge bg-green-100 text-green-700">
                            <span class="w-1.5 h-1.5 rounded-full bg-green-500" aria-hidden="true"></span> نشط
                        </span>
                        <?php else: ?>
                        <span class="badge bg-red-100 text-red-700">
                            <span class="w-1.5 h-1.5 rounded-full bg-red-500" aria-hidden="true"></span> موقوف
                        </span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 text-sm" style="color: rgb(var(--muted-foreground));">
                        <?= date('Y/m/d', strtotime($u['created_at'] ?? 'now')) ?>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center justify-center gap-1">
                            <a href="/users/edit?id=<?= (int)$u['id'] ?>"
                               class="p-2 rounded-lg transition-colors duration-200 cursor-pointer" style="color: rgb(var(--primary));" title="تعديل" aria-label="تعديل المستخدم">
                                <i class="fa-regular fa-pen-to-square" aria-hidden="true"></i>
                            </a>
                            <button type="button"
                                    onclick="openPasswordModal(<?= (int)$u['id'] ?>, '<?= htmlspecialchars($u['username'], ENT_QUOTES, 'UTF-8') ?>')"
                                    class="p-2 rounded-lg transition-colors duration-200 cursor-pointer" style="color: rgb(var(--color-warning));" title="تغيير كلمة المرور" aria-label="تغيير كلمة مرور المستخدم">
                                <i class="fa-solid fa-key" aria-hidden="true"></i>
                            </button>
                            <?php if ((int)$u['id'] !== (int)($_SESSION['user_id'] ?? 0)): ?>
                            <button type="button"
                                    onclick="deleteUser(<?= (int)$u['id'] ?>, '<?= htmlspecialchars($u['username'], ENT_QUOTES, 'UTF-8') ?>')"
                                    class="p-2 rounded-lg transition-colors duration-200 cursor-pointer" style="color: rgb(var(--color-danger));" title="حذف" aria-label="حذف المستخدم">
                                <i class="fa-regular fa-trash-can" aria-hidden="true"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: تغيير كلمة المرور -->
<div id="password-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4" style="background: rgb(0 0 0 / 0.5);" role="dialog" aria-modal="true" aria-labelledby="modal-title">
    <div class="app-card-flat w-full max-w-sm p-6 relative animate-slide-up">
        <button onclick="closePasswordModal()" class="absolute top-4 start-4 p-1.5 rounded-lg transition-colors duration-200 cursor-pointer" style="color: rgb(var(--muted-foreground));" aria-label="إغلاق">
            <i class="fa-solid fa-xmark text-lg" aria-hidden="true"></i>
        </button>
        <h3 id="modal-title" class="text-base font-bold mb-1" style="color: rgb(var(--foreground));">تغيير كلمة المرور</h3>
        <p class="text-sm mb-5" style="color: rgb(var(--muted-foreground));">المستخدم: <strong id="modal-username"></strong></p>

        <input type="hidden" id="modal-user-id">

        <div class="mb-4">
            <label class="block text-sm font-semibold mb-1.5" style="color: rgb(var(--foreground));">كلمة المرور الجديدة</label>
            <input type="password" id="modal-password" class="app-input w-full rounded-lg px-4 py-2.5 text-sm border" style="border-color: rgb(var(--border)); background: rgb(var(--muted));" placeholder="••••••••">
        </div>
        <div class="mb-5">
            <label class="block text-sm font-semibold mb-1.5" style="color: rgb(var(--foreground));">تأكيد كلمة المرور</label>
            <input type="password" id="modal-confirm" class="app-input w-full rounded-lg px-4 py-2.5 text-sm border" style="border-color: rgb(var(--border)); background: rgb(var(--muted));" placeholder="••••••••">
        </div>
        <div id="modal-error" class="hidden mb-4 text-sm rounded-lg px-4 py-3 bg-red-50 border border-red-200 text-red-700"></div>
        <button onclick="submitPasswordChange()" id="modal-submit-btn"
                class="w-full py-2.5 rounded-lg text-sm font-semibold transition-colors duration-200 cursor-pointer" style="background: rgb(var(--color-warning)); color: white;">
            تغيير كلمة المرور
        </button>
    </div>
</div>

<?php require BASE_PATH . '/views/layouts/footer.php'; ?>

<script>
(() => {
    'use strict';
    const CSRF = <?= json_encode($csrfToken) ?>;

    function showAlert(msg, type = 'error') {
        const box = document.getElementById('alert-box');
        const isOk = type === 'success';
        box.className = `rounded-lg p-4 mb-6 border text-sm font-medium ${isOk ? 'bg-green-50 border-green-200 text-green-700' : 'bg-red-50 border-red-200 text-red-700'}`;
        document.getElementById('alert-icon').textContent = isOk ? '✓' : '✕';
        document.getElementById('alert-message').textContent = msg;
        box.classList.remove('hidden');
        setTimeout(() => box.classList.add('hidden'), 5000);
    }

    window.deleteUser = function(id, name) {
        if (!confirm(`هل أنت متأكد من حذف المستخدم "${name}"؟`)) return;
        fetch('/api/users/delete', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, csrf_token: CSRF }),
        }).then(r => r.json()).then(data => {
            if (data.success) { document.getElementById(`row-${id}`)?.remove(); showAlert('تم حذف المستخدم بنجاح.', 'success'); }
            else showAlert(data.error ?? 'حدث خطأ.');
        }).catch(() => showAlert('تعذّر الاتصال بالخادم.'));
    };

    window.openPasswordModal = function(id, name) {
        document.getElementById('modal-user-id').value = id;
        document.getElementById('modal-username').textContent = name;
        document.getElementById('modal-password').value = '';
        document.getElementById('modal-confirm').value = '';
        document.getElementById('modal-error').classList.add('hidden');
        document.getElementById('password-modal').classList.remove('hidden');
    };

    window.closePasswordModal = function() {
        document.getElementById('password-modal').classList.add('hidden');
    };

    window.submitPasswordChange = function() {
        const id = document.getElementById('modal-user-id').value;
        const password = document.getElementById('modal-password').value.trim();
        const confirm = document.getElementById('modal-confirm').value.trim();
        const errEl = document.getElementById('modal-error');
        const btn = document.getElementById('modal-submit-btn');
        errEl.classList.add('hidden');
        if (password.length < 6) { errEl.textContent = 'كلمة المرور يجب أن تكون 6 أحرف على الأقل.'; errEl.classList.remove('hidden'); return; }
        if (password !== confirm) { errEl.textContent = 'كلمة المرور وتأكيدها غير متطابقين.'; errEl.classList.remove('hidden'); return; }
        btn.disabled = true; btn.textContent = 'جارٍ الحفظ…';
        fetch('/api/users/password', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: Number(id), password, confirm, csrf_token: CSRF }),
        }).then(r => r.json()).then(data => {
            btn.disabled = false; btn.textContent = 'تغيير كلمة المرور';
            if (data.success) { closePasswordModal(); showAlert('تم تغيير كلمة المرور بنجاح.', 'success'); }
            else { errEl.textContent = data.error ?? 'حدث خطأ.'; errEl.classList.remove('hidden'); }
        }).catch(() => { btn.disabled = false; btn.textContent = 'تغيير كلمة المرور'; errEl.textContent = 'تعذّر الاتصال بالخادم.'; errEl.classList.remove('hidden'); });
    };

    document.getElementById('password-modal').addEventListener('click', function(e) { if (e.target === this) closePasswordModal(); });
})();
</script>
