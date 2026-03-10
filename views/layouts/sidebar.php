    <?php
    $appSettings = file_exists(BASE_PATH . '/config/app_settings.php') ? (array) include BASE_PATH . '/config/app_settings.php' : [];
    $appName = $appSettings['app_name'] ?? 'نظام المخزون';
    $path = strtok($_SERVER['REQUEST_URI'] ?? '', '?');
    $nav = [
        'dashboard'  => $path === '/dashboard',
        'products'   => strpos($path, '/products') === 0,
        'categories' => strpos($path, '/categories') === 0,
        'sales'      => strpos($path, '/sales') === 0,
        'purchases'  => strpos($path, '/purchases') === 0,
        'reports'    => strpos($path, '/reports') === 0,
        'users'      => strpos($path, '/users') === 0,
        'activity'   => strpos($path, '/activity-log') === 0,
        'settings'   => strpos($path, '/settings') === 0,
        'profile'    => strpos($path, '/profile') === 0,
    ];
    $activeClass = 'bg-blue-600 text-white shadow-md shadow-blue-500/20';
    $inactiveClass = 'text-slate-300 hover:bg-slate-800 hover:text-white';
    $activeIcon = 'text-white';
    $inactiveIcon = 'text-slate-400 group-hover:text-blue-400';
    ?>
    <!-- ستارة الموبايل -->
    <div id="sidebar-backdrop" class="fixed inset-0 bg-black/60 z-[var(--z-sidebar)] hidden md:hidden transition-opacity duration-300" aria-hidden="true"></div>
    <!-- القائمة الجانبية -->
    <aside id="app-sidebar" class="fixed md:relative right-0 top-0 bottom-0 w-[var(--sidebar-width)] bg-[var(--sidebar-bg)] text-white flex-col flex shadow-xl z-30 transition-transform duration-300 ease-out translate-x-full md:translate-x-0">
        <div class="h-16 flex items-center border-b border-[var(--sidebar-border)] px-4 shrink-0">
            <div class="flex items-center gap-3 min-w-0">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center shadow-lg shadow-blue-500/25 shrink-0">
                    <i class="fa-solid fa-box-open text-white text-lg"></i>
                </div>
                <span class="font-bold text-white truncate text-base tracking-tight"><?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?></span>
            </div>
        </div>

        <nav class="flex-1 overflow-y-auto py-5 px-3 space-y-0.5">
            <p class="px-3 py-2 text-[11px] font-bold text-slate-500 uppercase tracking-widest mb-1">القائمة الرئيسية</p>
            
            <a href="/dashboard" class="flex items-center px-3 py-2.5 min-h-[44px] text-sm font-medium rounded-xl <?= $nav['dashboard'] ? $activeClass : $inactiveClass ?> group transition-all duration-200 focus:ring-2 focus:ring-blue-400 focus:ring-offset-2 focus:ring-offset-[#0f172a] cursor-pointer">
                <i class="fa-solid fa-house w-5 text-center ms-3 <?= $nav['dashboard'] ? $activeIcon : $inactiveIcon ?> transition-transform duration-200 group-hover:scale-110" aria-hidden="true"></i>
                لوحة التحكم
            </a>
            
            <a href="/products" class="flex items-center px-3 py-2.5 min-h-[44px] text-sm font-medium rounded-xl <?= $nav['products'] ? $activeClass : $inactiveClass ?> group transition-all duration-200 focus:ring-2 focus:ring-blue-400 focus:ring-offset-2 focus:ring-offset-[#0f172a] cursor-pointer">
                <i class="fa-solid fa-tags w-5 text-center ms-3 <?= $nav['products'] ? $activeIcon : 'text-slate-400 group-hover:text-blue-400' ?>" aria-hidden="true"></i>
                المنتجات
            </a>
            <a href="/categories" class="flex items-center px-3 py-2.5 min-h-[44px] text-sm font-medium rounded-xl <?= $nav['categories'] ? $activeClass : $inactiveClass ?> group transition-all duration-200 focus:ring-2 focus:ring-amber-400 focus:ring-offset-2 focus:ring-offset-[#0f172a] cursor-pointer">
                <i class="fa-solid fa-layer-group w-5 text-center ms-3 <?= $nav['categories'] ? $activeIcon : 'text-slate-400 group-hover:text-amber-400' ?>" aria-hidden="true"></i>
                التصنيفات
            </a>
            <a href="/sales" class="flex items-center px-3 py-2.5 min-h-[44px] text-sm font-medium rounded-xl <?= $nav['sales'] ? $activeClass : $inactiveClass ?> group transition-all duration-200 focus:ring-2 focus:ring-emerald-400 focus:ring-offset-2 focus:ring-offset-[#0f172a] cursor-pointer">
                <i class="fa-solid fa-file-invoice-dollar w-5 text-center ms-3 <?= $nav['sales'] ? $activeIcon : 'text-slate-400 group-hover:text-emerald-400' ?>" aria-hidden="true"></i>
                المبيعات
            </a>

            <div class="my-3 border-t border-slate-700/60"></div>
            <p class="px-3 py-2 text-[11px] font-bold text-slate-500 uppercase tracking-widest mb-1">المشتريات</p>
            <a href="/purchases" class="flex items-center px-3 py-2.5 min-h-[44px] text-sm font-medium rounded-xl <?= $nav['purchases'] ? $activeClass : $inactiveClass ?> group transition-all duration-200 focus:ring-2 focus:ring-purple-400 focus:ring-offset-2 focus:ring-offset-[#0f172a] cursor-pointer">
                <i class="fa-solid fa-truck-ramp-box w-5 text-center ms-3 <?= $nav['purchases'] ? $activeIcon : 'text-slate-400 group-hover:text-purple-400' ?>" aria-hidden="true"></i>
                المشتريات
            </a>

            <div class="my-3 border-t border-slate-700/60"></div>
            <p class="px-3 py-2 text-[11px] font-bold text-slate-500 uppercase tracking-widest mb-1">التحليلات</p>
            <a href="/reports" class="flex items-center px-3 py-2.5 min-h-[44px] text-sm font-medium rounded-xl <?= $nav['reports'] ? $activeClass : $inactiveClass ?> group transition-all duration-200 focus:ring-2 focus:ring-pink-400 focus:ring-offset-2 focus:ring-offset-[#0f172a] cursor-pointer">
                <i class="fa-solid fa-chart-pie w-5 text-center ms-3 <?= $nav['reports'] ? $activeIcon : 'text-slate-400 group-hover:text-pink-400' ?>" aria-hidden="true"></i>
                التقارير
            </a>

            <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
            <div class="my-3 border-t border-slate-700/60"></div>
            <p class="px-3 py-2 text-[11px] font-bold text-slate-500 uppercase tracking-widest mb-1">الإدارة</p>
            <a href="/users" class="flex items-center px-3 py-2.5 min-h-[44px] text-sm font-medium rounded-xl <?= $nav['users'] ? $activeClass : $inactiveClass ?> group transition-all duration-200 focus:ring-2 focus:ring-teal-400 focus:ring-offset-2 focus:ring-offset-[#0f172a] cursor-pointer">
                <i class="fa-solid fa-users-gear w-5 text-center ms-3 <?= $nav['users'] ? $activeIcon : 'text-slate-400 group-hover:text-teal-400' ?>" aria-hidden="true"></i>
                المستخدمون
            </a>
            <a href="/activity-log" class="flex items-center px-3 py-2.5 min-h-[44px] text-sm font-medium rounded-xl <?= $nav['activity'] ? $activeClass : $inactiveClass ?> group transition-all duration-200 focus:ring-2 focus:ring-amber-400 focus:ring-offset-2 focus:ring-offset-[#0f172a] cursor-pointer">
                <i class="fa-solid fa-clock-rotate-left w-5 text-center ms-3 <?= $nav['activity'] ? $activeIcon : 'text-slate-400 group-hover:text-amber-400' ?>" aria-hidden="true"></i>
                سجل النشاط
            </a>
            <a href="/settings" class="flex items-center px-3 py-2.5 min-h-[44px] text-sm font-medium rounded-xl <?= $nav['settings'] ? $activeClass : $inactiveClass ?> group transition-all duration-200 focus:ring-2 focus:ring-slate-400 focus:ring-offset-2 focus:ring-offset-[#0f172a] cursor-pointer">
                <i class="fa-solid fa-gear w-5 text-center ms-3 <?= $nav['settings'] ? $activeIcon : 'text-slate-400 group-hover:text-slate-300' ?>" aria-hidden="true"></i>
                إعدادات النظام
            </a>
            <?php endif; ?>
        </nav>

        <div class="p-3 border-t border-slate-700/80 bg-slate-900/40 shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-gradient-to-tr from-blue-500 to-purple-500 flex items-center justify-center text-sm font-bold shadow-lg">
                    <?= strtoupper(mb_substr($_SESSION['username'] ?? 'م', 0, 1, 'UTF-8')) ?>
                </div>
                <div class="flex-1 min-w-0">
                    <a href="/profile" class="block truncate focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-2 focus:ring-offset-slate-900 rounded">
                        <p class="text-sm font-semibold text-white truncate"><?= htmlspecialchars($_SESSION['username'] ?? 'مستخدم') ?></p>
                        <p class="text-xs text-slate-400 truncate"><?= ($_SESSION['role'] ?? '') === 'admin' ? 'مدير' : 'كاشير' ?></p>
                    </a>
                    <a href="/profile" class="text-xs text-blue-400 hover:text-blue-300 mt-0.5 inline-block">حسابي</a>
                </div>
                <form method="POST" action="/api/logout">
                    <button type="submit" class="touch-target min-w-[44px] min-h-[44px] rounded-xl bg-slate-800 text-slate-400 hover:bg-red-500/10 hover:text-red-400 flex items-center justify-center transition-colors duration-200 focus:ring-2 focus:ring-red-400 focus:ring-offset-2 focus:ring-offset-slate-900 cursor-pointer" title="تسجيل الخروج" aria-label="تسجيل الخروج">
                        <i class="fa-solid fa-arrow-right-from-bracket" aria-hidden="true"></i>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <!-- Main Content Wrapper -->
    <div class="flex-1 flex flex-col overflow-hidden relative">
        <!-- Top Navbar -->
        <header class="h-16 glass border-b border-slate-200/80 flex items-center justify-between px-4 md:px-6 shrink-0 sticky top-0 z-[var(--z-header)]">
            <div class="flex items-center gap-2">
                <button type="button" id="sidebar-toggle" class="touch-target md:hidden flex items-center justify-center text-slate-500 hover:text-blue-600 hover:bg-slate-100 rounded-xl transition-colors cursor-pointer" aria-label="فتح القائمة" aria-expanded="false">
                    <i class="fa-solid fa-bars text-lg" aria-hidden="true"></i>
                </button>
                <h2 class="text-lg md:text-xl font-bold text-slate-800 leading-tight truncate"><?= htmlspecialchars($title ?? 'لوحة التحكم') ?></h2>
            </div>
            <div class="flex items-center gap-2 md:gap-3">
                <div class="hidden md:flex items-center bg-slate-100/80 rounded-xl px-3 py-2 w-44 lg:w-52 border border-slate-200/60 focus-within:border-blue-400 focus-within:ring-2 focus-within:ring-blue-400/20 transition-all">
                    <i class="fa-solid fa-search text-slate-400 text-sm ml-2" aria-hidden="true"></i>
                    <input type="search" placeholder="بحث..." aria-label="بحث" class="bg-transparent border-none outline-none text-sm text-slate-700 placeholder-slate-400 flex-1 min-w-0">
                </div>
                <button type="button" class="touch-target flex items-center justify-center text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-xl transition-colors relative cursor-pointer" aria-label="الإشعارات">
                    <i class="fa-regular fa-bell text-lg" aria-hidden="true"></i>
                    <span class="absolute top-1.5 right-1.5 w-2 h-2 rounded-full bg-red-500 ring-2 ring-white" aria-hidden="true"></span>
                </button>
                <div class="hidden lg:flex items-center gap-2 text-sm font-medium text-slate-600 bg-slate-100/80 px-3.5 py-2 rounded-xl border border-slate-200/60" role="img" aria-label="التاريخ">
                    <i class="fa-regular fa-calendar text-slate-400 text-xs" aria-hidden="true"></i>
                    <time datetime="<?= date('Y-m-d') ?>"><?= date('Y/m/d') ?></time>
                </div>
            </div>
        </header>

        <!-- Main Scrollable Area -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gradient-to-b from-slate-50/90 to-slate-100/50 p-4 md:p-6 lg:p-8 min-h-0">
