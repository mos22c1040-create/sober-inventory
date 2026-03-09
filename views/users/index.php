<?php require BASE_PATH . '/views/layouts/header.php'; ?>
<?php require BASE_PATH . '/views/layouts/sidebar.php'; ?>

<nav class="flex items-center gap-2 text-sm text-gray-500 mb-4">
    <a href="/dashboard" class="hover:text-blue-600 transition-colors">لوحة التحكم</a>
    <i class="fa-solid fa-chevron-left text-xs text-gray-400"></i>
    <span class="text-slate-700 font-medium">المستخدمون</span>
</nav>

<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-800">المستخدمون</h1>
        <p class="text-sm text-slate-500 mt-1">إدارة حسابات المديرين والكاشير</p>
    </div>
    <a href="/users/create"
       class="inline-flex items-center min-h-[44px] px-5 py-2.5 bg-blue-600 text-white rounded-xl hover:bg-blue-700 text-sm font-medium shadow-md btn-primary focus:ring-2 focus:ring-blue-400 focus:ring-offset-2 transition-colors cursor-pointer">
        <i class="fa-solid fa-user-plus ms-2" aria-hidden="true"></i> إضافة مستخدم
    </a>
</div>

<!-- Alert -->
<div id="alert-box" class="hidden rounded-xl p-4 mb-6 border text-sm font-medium" role="alert" aria-live="polite">
    <span id="alert-icon" class="me-2"></span>
    <span id="alert-message"></span>
</div>

<!-- Table -->
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">#</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">الاسم</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">البريد الإلكتروني</th>
                    <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase">الدور</th>
                    <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase">الحالة</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">تاريخ الإنشاء</th>
                    <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase">إجراءات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100" id="users-tbody">
                <?php if (empty($users)): ?>
                <tr>
                    <td colspan="7" class="px-6 py-10 text-center text-gray-400">
                        لا يوجد مستخدمون بعد.
                        <a href="/users/create" class="text-blue-600 hover:underline">أضف مستخدماً</a>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($users as $i => $u): ?>
                <tr class="table-row-hover transition-colors duration-200" id="row-<?= (int) $u['id'] ?>">
                    <td class="px-6 py-4 text-sm text-gray-500"><?= $i + 1 ?></td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full bg-gradient-to-tr from-blue-500 to-purple-500 flex items-center justify-center text-white text-sm font-bold shrink-0 shadow">
                                <?= mb_strtoupper(mb_substr($u['username'], 0, 1, 'UTF-8'), 'UTF-8') ?>
                            </div>
                            <span class="text-sm font-medium text-slate-800">
                                <?= htmlspecialchars($u['username'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">
                        <?= htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8') ?>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <?php if ($u['role'] === 'admin'): ?>
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-purple-100 text-purple-700">
                            <i class="fa-solid fa-crown text-[10px]"></i> مدير
                        </span>
                        <?php else: ?>
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-700">
                            <i class="fa-solid fa-cash-register text-[10px]"></i> كاشير
                        </span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <?php if ($u['status'] === 'active'): ?>
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700">
                            <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> نشط
                        </span>
                        <?php else: ?>
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700">
                            <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span> موقوف
                        </span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">
                        <?= date('Y/m/d', strtotime($u['created_at'] ?? 'now')) ?>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center justify-center gap-2">
                            <!-- Edit -->
                            <a href="/users/edit?id=<?= (int) $u['id'] ?>"
                               class="p-2 rounded-lg text-blue-600 hover:bg-blue-50 transition-colors" title="تعديل">
                                <i class="fa-regular fa-pen-to-square"></i>
                            </a>
                            <!-- Change Password -->
                            <button type="button"
                                    onclick="openPasswordModal(<?= (int) $u['id'] ?>, '<?= htmlspecialchars($u['username'], ENT_QUOTES, 'UTF-8') ?>')"
                                    class="p-2 rounded-lg text-amber-600 hover:bg-amber-50 transition-colors" title="تغيير كلمة المرور">
                                <i class="fa-solid fa-key"></i>
                            </button>
                            <!-- Delete -->
                            <?php if ((int) $u['id'] !== (int) ($_SESSION['user_id'] ?? 0)): ?>
                            <button type="button"
                                    onclick="deleteUser(<?= (int) $u['id'] ?>, '<?= htmlspecialchars($u['username'], ENT_QUOTES, 'UTF-8') ?>')"
                                    class="p-2 rounded-lg text-red-500 hover:bg-red-50 transition-colors" title="حذف">
                                <i class="fa-regular fa-trash-can"></i>
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

<!-- ─── Password Modal ─────────────────────────────────────── -->
<div id="password-modal"
     class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/40 backdrop-blur-sm p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6 relative">
        <button onclick="closePasswordModal()"
                class="absolute top-4 start-4 text-gray-400 hover:text-red-500 transition-colors">
            <i class="fa-solid fa-xmark text-lg"></i>
        </button>
        <h3 class="text-base font-bold text-slate-800 mb-1">تغيير كلمة المرور</h3>
        <p class="text-sm text-gray-500 mb-5">المستخدم: <strong id="modal-username"></strong></p>

        <input type="hidden" id="modal-user-id">

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">كلمة المرور الجديدة</label>
            <input type="password" id="modal-password"
                   class="w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition"
                   placeholder="••••••••">
        </div>
        <div class="mb-5">
            <label class="block text-sm font-medium text-gray-700 mb-1">تأكيد كلمة المرور</label>
            <input type="password" id="modal-confirm"
                   class="w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition"
                   placeholder="••••••••">
        </div>
        <div id="modal-error" class="hidden mb-4 text-sm text-red-600 bg-red-50 border border-red-200 rounded-xl px-4 py-3"></div>

        <button onclick="submitPasswordChange()"
                id="modal-submit-btn"
                class="w-full py-2.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold transition-colors">
            تغيير كلمة المرور
        </button>
    </div>
</div>

<?php require BASE_PATH . '/views/layouts/footer.php'; ?>

<script>
(() => {
    'use strict';

    const CSRF = <?= json_encode($csrfToken) ?>;

    // ── Alert helpers ──────────────────────────────────────────────────────
    function showAlert(msg, type = 'error') {
        const box  = document.getElementById('alert-box');
        const icon = document.getElementById('alert-icon');
        const text = document.getElementById('alert-message');
        const isOk = type === 'success';
        box.className = `rounded-xl p-4 mb-6 border text-sm font-medium ${
            isOk
            ? 'bg-green-50 border-green-200 text-green-700'
            : 'bg-red-50 border-red-200 text-red-700'
        }`;
        icon.textContent = isOk ? '✓' : '✕';
        text.textContent = msg;
        box.classList.remove('hidden');
        setTimeout(() => box.classList.add('hidden'), 5000);
    }

    // ── Delete ─────────────────────────────────────────────────────────────
    window.deleteUser = function(id, name) {
        if (!confirm(`هل أنت متأكد من حذف المستخدم "${name}"؟ لا يمكن التراجع عن هذا الإجراء.`)) return;
        fetch('/api/users/delete', {
            method : 'POST',
            headers: { 'Content-Type': 'application/json' },
            body   : JSON.stringify({ id, csrf_token: CSRF }),
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                document.getElementById(`row-${id}`)?.remove();
                showAlert('تم حذف المستخدم بنجاح.', 'success');
            } else {
                showAlert(data.error ?? 'حدث خطأ.');
            }
        })
        .catch(() => showAlert('تعذّر الاتصال بالخادم.'));
    };

    // ── Password modal ─────────────────────────────────────────────────────
    window.openPasswordModal = function(id, name) {
        document.getElementById('modal-user-id').value  = id;
        document.getElementById('modal-username').textContent = name;
        document.getElementById('modal-password').value = '';
        document.getElementById('modal-confirm').value  = '';
        document.getElementById('modal-error').classList.add('hidden');
        document.getElementById('password-modal').classList.remove('hidden');
    };

    window.closePasswordModal = function() {
        document.getElementById('password-modal').classList.add('hidden');
    };

    window.submitPasswordChange = function() {
        const id       = document.getElementById('modal-user-id').value;
        const password = document.getElementById('modal-password').value.trim();
        const confirm  = document.getElementById('modal-confirm').value.trim();
        const errEl    = document.getElementById('modal-error');
        const btn      = document.getElementById('modal-submit-btn');

        errEl.classList.add('hidden');

        if (password.length < 6) {
            errEl.textContent = 'كلمة المرور يجب أن تكون 6 أحرف على الأقل.';
            errEl.classList.remove('hidden');
            return;
        }
        if (password !== confirm) {
            errEl.textContent = 'كلمة المرور وتأكيدها غير متطابقين.';
            errEl.classList.remove('hidden');
            return;
        }

        btn.disabled    = true;
        btn.textContent = 'جارٍ الحفظ…';

        fetch('/api/users/password', {
            method : 'POST',
            headers: { 'Content-Type': 'application/json' },
            body   : JSON.stringify({ id: Number(id), password, confirm, csrf_token: CSRF }),
        })
        .then(r => r.json())
        .then(data => {
            btn.disabled    = false;
            btn.textContent = 'تغيير كلمة المرور';
            if (data.success) {
                closePasswordModal();
                showAlert('تم تغيير كلمة المرور بنجاح.', 'success');
            } else {
                errEl.textContent = data.error ?? 'حدث خطأ.';
                errEl.classList.remove('hidden');
            }
        })
        .catch(() => {
            btn.disabled    = false;
            btn.textContent = 'تغيير كلمة المرور';
            errEl.textContent = 'تعذّر الاتصال بالخادم.';
            errEl.classList.remove('hidden');
        });
    };

    // Close modal on backdrop click
    document.getElementById('password-modal').addEventListener('click', function(e) {
        if (e.target === this) closePasswordModal();
    });
})();
</script>
