    <?php
    if (!isset($basePath)) {
        $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
        $basePath = $basePath === '' || $basePath === '\\' ? '/' : $basePath;
    }
    $basePathSafe = htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8');
    $appSettings = file_exists(BASE_PATH . '/config/app_settings.php') ? (array) include BASE_PATH . '/config/app_settings.php' : [];
    $appName = $appSettings['app_name'] ?? 'نظام المخزون';
    $path = strtok($_SERVER['REQUEST_URI'] ?? '', '?');
    $pathForNav = ($basePath !== '/' && strpos($path, $basePath) === 0)
        ? substr($path, strlen($basePath)) : $path;
    $pathForNav = $pathForNav === '' ? '/' : $pathForNav;
    $nav = [
        'dashboard'  => $pathForNav === '/dashboard',
        'products'   => strpos($pathForNav, '/products') === 0,
        'categories' => strpos($pathForNav, '/categories') === 0,
        'sales'      => strpos($pathForNav, '/sales') === 0,
        'purchases'  => strpos($pathForNav, '/purchases') === 0,
        'expenses'   => strpos($pathForNav, '/expenses') === 0,
        'reports'    => strpos($pathForNav, '/reports') === 0,
        'users'      => strpos($pathForNav, '/users') === 0,
        'activity'   => strpos($pathForNav, '/activity-log') === 0,
        'settings'   => strpos($pathForNav, '/settings') === 0,
        'profile'    => strpos($pathForNav, '/profile') === 0,
    ];
    /* لون تمييز واحد للنشط (frontend-design: one accent) */
    $activeClass = 'bg-[rgb(var(--primary))] text-[rgb(var(--primary-foreground))]';
    $inactiveClass = 'text-slate-300 hover:bg-slate-700/80 hover:text-white';
    $activeIcon = 'text-white';
    $inactiveIcon = 'text-slate-400 group-hover:text-white';
    ?>
    <!-- ستارة الموبايل -->
    <div id="sidebar-backdrop" class="fixed inset-0 bg-black/60 z-[var(--z-sidebar)] hidden md:hidden transition-opacity duration-300" aria-hidden="true"></div>
    <!-- القائمة الجانبية -->
    <aside id="app-sidebar" class="fixed md:relative right-0 top-0 bottom-0 w-[var(--sidebar-width)] text-white flex-col flex z-30 transition-transform duration-300 ease-out translate-x-full md:translate-x-0" style="background: rgb(var(--sidebar-bg)); border-left: 1px solid rgb(var(--sidebar-border)); box-shadow: 4px 0 24px -4px rgb(0 0 0 / 0.08);">
        <div class="h-16 flex items-center border-b px-4 shrink-0" style="border-color: rgb(var(--sidebar-border));">
            <div class="flex items-center gap-3 min-w-0">
                <div class="w-10 h-10 rounded-lg bg-[rgb(var(--primary))] flex items-center justify-center shrink-0" style="box-shadow: 0 2px 8px -2px rgb(var(--primary) / 0.4);">
                    <i class="fa-solid fa-box-open text-white text-lg" aria-hidden="true"></i>
                </div>
                <span class="font-bold text-white truncate text-base tracking-tight"><?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?></span>
            </div>
        </div>

        <nav class="flex-1 overflow-y-auto py-5 px-3 space-y-0.5">
            <p class="px-3 py-2 text-[11px] font-bold text-slate-500 uppercase tracking-widest mb-1">القائمة الرئيسية</p>
            
            <a href="<?= $basePathSafe ?>/dashboard" class="flex items-center px-3 py-2.5 min-h-[44px] text-sm font-medium rounded-xl <?= $nav['dashboard'] ? $activeClass : $inactiveClass ?> group transition-colors duration-200 focus:ring-2 focus:ring-blue-400 focus:ring-offset-2 focus:ring-offset-[#0f172a] cursor-pointer">
                <i class="fa-solid fa-house w-5 text-center ms-3 <?= $nav['dashboard'] ? $activeIcon : $inactiveIcon ?> transition-colors duration-200" aria-hidden="true"></i>
                لوحة التحكم
            </a>
            
            <a href="<?= $basePathSafe ?>/products" class="flex items-center px-3 py-2.5 min-h-[44px] text-sm font-medium rounded-xl <?= $nav['products'] ? $activeClass : $inactiveClass ?> group transition-colors duration-200 focus:ring-2 focus:ring-blue-400 focus:ring-offset-2 focus:ring-offset-[#0f172a] cursor-pointer">
                <i class="fa-solid fa-tags w-5 text-center ms-3 <?= $nav['products'] ? $activeIcon : $inactiveIcon ?>" aria-hidden="true"></i>
                المنتجات
            </a>
            <a href="<?= $basePathSafe ?>/categories" class="flex items-center px-3 py-2.5 min-h-[44px] text-sm font-medium rounded-xl <?= $nav['categories'] ? $activeClass : $inactiveClass ?> group transition-colors duration-200 focus:ring-2 focus:ring-blue-400 focus:ring-offset-2 focus:ring-offset-[#0f172a] cursor-pointer">
                <i class="fa-solid fa-layer-group w-5 text-center ms-3 <?= $nav['categories'] ? $activeIcon : $inactiveIcon ?>" aria-hidden="true"></i>
                التصنيفات
            </a>
            <a href="<?= $basePathSafe ?>/sales" class="flex items-center px-3 py-2.5 min-h-[44px] text-sm font-medium rounded-xl <?= $nav['sales'] ? $activeClass : $inactiveClass ?> group transition-colors duration-200 focus:ring-2 focus:ring-blue-400 focus:ring-offset-2 focus:ring-offset-[#0f172a] cursor-pointer">
                <i class="fa-solid fa-file-invoice-dollar w-5 text-center ms-3 <?= $nav['sales'] ? $activeIcon : $inactiveIcon ?>" aria-hidden="true"></i>
                المبيعات
            </a>

            <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
            <div class="my-3 border-t border-slate-700/60"></div>
            <p class="px-3 py-2 text-[11px] font-bold text-slate-500 uppercase tracking-widest mb-1">المشتريات</p>
            <a href="<?= $basePathSafe ?>/purchases" class="flex items-center px-3 py-2.5 min-h-[44px] text-sm font-medium rounded-xl <?= $nav['purchases'] ? $activeClass : $inactiveClass ?> group transition-colors duration-200 focus:ring-2 focus:ring-blue-400 focus:ring-offset-2 focus:ring-offset-[#0f172a] cursor-pointer">
                <i class="fa-solid fa-truck-ramp-box w-5 text-center ms-3 <?= $nav['purchases'] ? $activeIcon : $inactiveIcon ?>" aria-hidden="true"></i>
                المشتريات
            </a>
            <a href="<?= $basePathSafe ?>/expenses" class="flex items-center px-3 py-2.5 min-h-[44px] text-sm font-medium rounded-xl <?= $nav['expenses'] ? $activeClass : $inactiveClass ?> group transition-colors duration-200 focus:ring-2 focus:ring-blue-400 focus:ring-offset-2 focus:ring-offset-[#0f172a] cursor-pointer">
                <i class="fa-solid fa-money-bill-transfer w-5 text-center ms-3 <?= $nav['expenses'] ? $activeIcon : $inactiveIcon ?>" aria-hidden="true"></i>
                المصروفات
            </a>

            <div class="my-3 border-t border-slate-700/60"></div>
            <p class="px-3 py-2 text-[11px] font-bold text-slate-500 uppercase tracking-widest mb-1">التحليلات</p>
            <a href="<?= $basePathSafe ?>/reports" class="flex items-center px-3 py-2.5 min-h-[44px] text-sm font-medium rounded-xl <?= $nav['reports'] ? $activeClass : $inactiveClass ?> group transition-colors duration-200 focus:ring-2 focus:ring-blue-400 focus:ring-offset-2 focus:ring-offset-[#0f172a] cursor-pointer">
                <i class="fa-solid fa-chart-pie w-5 text-center ms-3 <?= $nav['reports'] ? $activeIcon : $inactiveIcon ?>" aria-hidden="true"></i>
                التقارير
            </a>

            <div class="my-3 border-t border-slate-700/60"></div>
            <p class="px-3 py-2 text-[11px] font-bold text-slate-500 uppercase tracking-widest mb-1">الإدارة</p>
            <a href="<?= $basePathSafe ?>/users" class="flex items-center px-3 py-2.5 min-h-[44px] text-sm font-medium rounded-xl <?= $nav['users'] ? $activeClass : $inactiveClass ?> group transition-colors duration-200 focus:ring-2 focus:ring-blue-400 focus:ring-offset-2 focus:ring-offset-[#0f172a] cursor-pointer">
                <i class="fa-solid fa-users-gear w-5 text-center ms-3 <?= $nav['users'] ? $activeIcon : $inactiveIcon ?>" aria-hidden="true"></i>
                المستخدمون
            </a>
            <a href="<?= $basePathSafe ?>/activity-log" class="flex items-center px-3 py-2.5 min-h-[44px] text-sm font-medium rounded-xl <?= $nav['activity'] ? $activeClass : $inactiveClass ?> group transition-colors duration-200 focus:ring-2 focus:ring-blue-400 focus:ring-offset-2 focus:ring-offset-[#0f172a] cursor-pointer">
                <i class="fa-solid fa-clock-rotate-left w-5 text-center ms-3 <?= $nav['activity'] ? $activeIcon : $inactiveIcon ?>" aria-hidden="true"></i>
                سجل النشاط
            </a>
            <a href="<?= $basePathSafe ?>/settings" class="flex items-center px-3 py-2.5 min-h-[44px] text-sm font-medium rounded-xl <?= $nav['settings'] ? $activeClass : $inactiveClass ?> group transition-colors duration-200 focus:ring-2 focus:ring-blue-400 focus:ring-offset-2 focus:ring-offset-[#0f172a] cursor-pointer">
                <i class="fa-solid fa-gear w-5 text-center ms-3 <?= $nav['settings'] ? $activeIcon : $inactiveIcon ?>" aria-hidden="true"></i>
                إعدادات النظام
            </a>
            <?php endif; ?>
        </nav>

        <div class="p-3 border-t border-slate-700/80 bg-slate-900/40 shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold shrink-0" style="background: rgb(var(--primary)); color: rgb(var(--primary-foreground));">
                    <?= strtoupper(mb_substr($_SESSION['username'] ?? 'م', 0, 1, 'UTF-8')) ?>
                </div>
                <div class="flex-1 min-w-0">
                    <a href="<?= $basePathSafe ?>/profile" class="block truncate focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-2 focus:ring-offset-slate-900 rounded">
                        <p class="text-sm font-semibold text-white truncate"><?= htmlspecialchars($_SESSION['username'] ?? 'مستخدم') ?></p>
                        <p class="text-xs text-slate-400 truncate"><?= ($_SESSION['role'] ?? '') === 'admin' ? 'مدير' : 'كاشير' ?></p>
                    </a>
                    <a href="<?= $basePathSafe ?>/profile" class="text-xs text-blue-400 hover:text-blue-300 mt-0.5 inline-block">حسابي</a>
                </div>
                <form method="POST" action="<?= $basePathSafe ?>/api/logout">
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
        <header class="h-16 glass flex items-center justify-between px-4 md:px-6 shrink-0 sticky top-0 z-[var(--z-header)]" style="border-bottom: 1px solid rgb(var(--border));">
            <div class="flex items-center gap-2">
                <button type="button" id="sidebar-toggle" class="touch-target md:hidden flex items-center justify-center text-slate-500 hover:bg-slate-100 rounded-lg transition-colors duration-200 cursor-pointer" aria-label="فتح القائمة" aria-expanded="false">
                    <i class="fa-solid fa-bars text-lg" aria-hidden="true"></i>
                </button>
                <h2 class="text-lg md:text-xl font-bold leading-tight truncate" style="color: rgb(var(--foreground));"><?= htmlspecialchars($title ?? 'لوحة التحكم') ?></h2>
            </div>
            <div class="flex items-center gap-2 md:gap-3">
                <div class="hidden md:flex items-center rounded-lg px-3 py-2 w-44 lg:w-52 transition-all" style="background: rgb(var(--muted)); border: 1px solid rgb(var(--border));">
                    <i class="fa-solid fa-search text-sm ml-2" style="color: rgb(var(--muted-foreground));" aria-hidden="true"></i>
                    <input type="search" placeholder="بحث..." aria-label="بحث" class="bg-transparent border-none outline-none text-sm flex-1 min-w-0" style="color: rgb(var(--foreground));">
                </div>
                <button type="button" class="touch-target flex items-center justify-center text-slate-500 hover:bg-slate-100 rounded-lg transition-colors duration-200 relative cursor-pointer" aria-label="الإشعارات">
                    <i class="fa-regular fa-bell text-lg" aria-hidden="true"></i>
                    <span class="absolute top-1.5 right-1.5 w-2 h-2 rounded-full bg-red-500 ring-2 ring-white" aria-hidden="true"></span>
                </button>
                <div class="hidden lg:flex items-center gap-2 text-sm font-medium px-3.5 py-2 rounded-lg" style="color: rgb(var(--muted-foreground)); background: rgb(var(--muted)); border: 1px solid rgb(var(--border));" role="img" aria-label="التاريخ">
                    <i class="fa-regular fa-calendar text-xs" style="color: rgb(var(--muted-foreground));" aria-hidden="true"></i>
                    <time datetime="<?= date('Y-m-d') ?>"><?= date('Y/m/d') ?></time>
                </div>
            </div>
        </header>

        <!-- Main Scrollable Area -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-6 lg:p-8 min-h-0" style="background: rgb(var(--background));">
