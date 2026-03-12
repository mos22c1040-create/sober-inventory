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
        'pos'        => $pathForNav === '/pos',
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
    $activeClass   = 'sidebar-link-active';
    $inactiveClass = 'sidebar-link-inactive';
    ?>
    <!-- Mobile backdrop: يظهر فقط على الموبايل عند فتح القائمة -->
    <div id="sidebar-backdrop"
         class="fixed inset-0 bg-black/65 backdrop-blur-sm z-[var(--z-sidebar)] transition-opacity duration-300 md:!hidden"
         style="display: none;"
         aria-hidden="true"></div>

    <!-- Sidebar -->
    <aside id="app-sidebar"
           class="fixed md:relative right-0 top-0 bottom-0 w-[var(--sidebar-width)] text-white flex flex-col z-30 transition-transform duration-300 ease-out translate-x-full md:translate-x-0"
           style="background: rgb(var(--sidebar-bg)); border-inline-start: 1px solid rgb(var(--sidebar-border));">

        <!-- Logo -->
        <div class="h-16 flex items-center gap-3 px-4 shrink-0" style="border-bottom: 1px solid rgb(var(--sidebar-border));">
            <div class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0"
                 style="background: rgb(var(--primary)); box-shadow: 0 2px 10px rgb(37 99 235 / 0.4);">
                <i class="fa-solid fa-box-open text-white text-base" aria-hidden="true"></i>
            </div>
            <div class="min-w-0 flex-1">
                <span class="font-bold text-white text-[0.92rem] truncate block tracking-tight leading-tight">
                    <?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?>
                </span>
                <span class="text-[10px] font-medium text-slate-500 tracking-wider uppercase">نظام المخزون</span>
            </div>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-0.5">

            <!-- POS — Special link always visible -->
            <a href="<?= $basePathSafe ?>/pos"
               class="flex items-center px-3 py-2.5 min-h-[44px] text-sm font-bold rounded-xl mb-3 transition-all duration-200 focus:ring-2 focus:ring-emerald-400 focus:ring-offset-1 focus:ring-offset-slate-900 cursor-pointer <?= $nav['pos'] ? 'sidebar-pos-link' : 'text-emerald-400 hover:bg-emerald-900/30 hover:text-emerald-300' ?>"
               title="نقطة البيع">
                <i class="fa-solid fa-cash-register w-5 text-center ms-3" aria-hidden="true"></i>
                نقطة البيع
                <span class="ms-auto text-[9px] font-bold px-1.5 py-0.5 rounded tracking-wide"
                      style="background: rgb(16 185 129 / 0.2); color: rgb(52 211 153);">POS</span>
            </a>

            <!-- Divider -->
            <div class="my-2 border-t" style="border-color: rgb(var(--sidebar-border));"></div>
            <p class="px-3 py-1.5 text-[10px] font-bold text-slate-600 uppercase tracking-widest">الرئيسية</p>

            <a href="<?= $basePathSafe ?>/dashboard"
               class="flex items-center px-3 py-2.5 min-h-[44px] text-sm font-medium rounded-xl <?= $nav['dashboard'] ? $activeClass : $inactiveClass ?> group transition-all duration-200 focus:ring-2 focus:ring-blue-400 focus:ring-offset-1 focus:ring-offset-slate-900 cursor-pointer">
                <i class="fa-solid fa-house w-5 text-center ms-3 transition-transform duration-200 group-hover:scale-110" aria-hidden="true"></i>
                لوحة التحكم
            </a>

            <a href="<?= $basePathSafe ?>/products"
               class="flex items-center px-3 py-2.5 min-h-[44px] text-sm font-medium rounded-xl <?= $nav['products'] ? $activeClass : $inactiveClass ?> group transition-all duration-200 focus:ring-2 focus:ring-blue-400 focus:ring-offset-1 focus:ring-offset-slate-900 cursor-pointer">
                <i class="fa-solid fa-tags w-5 text-center ms-3 transition-transform duration-200 group-hover:scale-110" aria-hidden="true"></i>
                المنتجات
            </a>

            <a href="<?= $basePathSafe ?>/categories"
               class="flex items-center px-3 py-2.5 min-h-[44px] text-sm font-medium rounded-xl <?= $nav['categories'] ? $activeClass : $inactiveClass ?> group transition-all duration-200 focus:ring-2 focus:ring-blue-400 focus:ring-offset-1 focus:ring-offset-slate-900 cursor-pointer">
                <i class="fa-solid fa-layer-group w-5 text-center ms-3 transition-transform duration-200 group-hover:scale-110" aria-hidden="true"></i>
                التصنيفات
            </a>

            <a href="<?= $basePathSafe ?>/sales"
               class="flex items-center px-3 py-2.5 min-h-[44px] text-sm font-medium rounded-xl <?= $nav['sales'] ? $activeClass : $inactiveClass ?> group transition-all duration-200 focus:ring-2 focus:ring-blue-400 focus:ring-offset-1 focus:ring-offset-slate-900 cursor-pointer">
                <i class="fa-solid fa-file-invoice-dollar w-5 text-center ms-3 transition-transform duration-200 group-hover:scale-110" aria-hidden="true"></i>
                المبيعات
            </a>

            <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
            <div class="my-2 border-t" style="border-color: rgb(var(--sidebar-border));"></div>
            <p class="px-3 py-1.5 text-[10px] font-bold text-slate-600 uppercase tracking-widest">المشتريات</p>

            <a href="<?= $basePathSafe ?>/purchases"
               class="flex items-center px-3 py-2.5 min-h-[44px] text-sm font-medium rounded-xl <?= $nav['purchases'] ? $activeClass : $inactiveClass ?> group transition-all duration-200 focus:ring-2 focus:ring-blue-400 focus:ring-offset-1 focus:ring-offset-slate-900 cursor-pointer">
                <i class="fa-solid fa-truck-ramp-box w-5 text-center ms-3 transition-transform duration-200 group-hover:scale-110" aria-hidden="true"></i>
                المشتريات
            </a>

            <a href="<?= $basePathSafe ?>/expenses"
               class="flex items-center px-3 py-2.5 min-h-[44px] text-sm font-medium rounded-xl <?= $nav['expenses'] ? $activeClass : $inactiveClass ?> group transition-all duration-200 focus:ring-2 focus:ring-blue-400 focus:ring-offset-1 focus:ring-offset-slate-900 cursor-pointer">
                <i class="fa-solid fa-money-bill-transfer w-5 text-center ms-3 transition-transform duration-200 group-hover:scale-110" aria-hidden="true"></i>
                المصروفات
            </a>

            <div class="my-2 border-t" style="border-color: rgb(var(--sidebar-border));"></div>
            <p class="px-3 py-1.5 text-[10px] font-bold text-slate-600 uppercase tracking-widest">التحليلات</p>

            <a href="<?= $basePathSafe ?>/reports"
               class="flex items-center px-3 py-2.5 min-h-[44px] text-sm font-medium rounded-xl <?= $nav['reports'] ? $activeClass : $inactiveClass ?> group transition-all duration-200 focus:ring-2 focus:ring-blue-400 focus:ring-offset-1 focus:ring-offset-slate-900 cursor-pointer">
                <i class="fa-solid fa-chart-pie w-5 text-center ms-3 transition-transform duration-200 group-hover:scale-110" aria-hidden="true"></i>
                التقارير
            </a>

            <div class="my-2 border-t" style="border-color: rgb(var(--sidebar-border));"></div>
            <p class="px-3 py-1.5 text-[10px] font-bold text-slate-600 uppercase tracking-widest">الإدارة</p>

            <a href="<?= $basePathSafe ?>/users"
               class="flex items-center px-3 py-2.5 min-h-[44px] text-sm font-medium rounded-xl <?= $nav['users'] ? $activeClass : $inactiveClass ?> group transition-all duration-200 focus:ring-2 focus:ring-blue-400 focus:ring-offset-1 focus:ring-offset-slate-900 cursor-pointer">
                <i class="fa-solid fa-users-gear w-5 text-center ms-3 transition-transform duration-200 group-hover:scale-110" aria-hidden="true"></i>
                المستخدمون
            </a>

            <a href="<?= $basePathSafe ?>/activity-log"
               class="flex items-center px-3 py-2.5 min-h-[44px] text-sm font-medium rounded-xl <?= $nav['activity'] ? $activeClass : $inactiveClass ?> group transition-all duration-200 focus:ring-2 focus:ring-blue-400 focus:ring-offset-1 focus:ring-offset-slate-900 cursor-pointer">
                <i class="fa-solid fa-clock-rotate-left w-5 text-center ms-3 transition-transform duration-200 group-hover:scale-110" aria-hidden="true"></i>
                سجل النشاط
            </a>

            <a href="<?= $basePathSafe ?>/settings"
               class="flex items-center px-3 py-2.5 min-h-[44px] text-sm font-medium rounded-xl <?= $nav['settings'] ? $activeClass : $inactiveClass ?> group transition-all duration-200 focus:ring-2 focus:ring-blue-400 focus:ring-offset-1 focus:ring-offset-slate-900 cursor-pointer">
                <i class="fa-solid fa-gear w-5 text-center ms-3 transition-transform duration-200 group-hover:scale-110" aria-hidden="true"></i>
                إعدادات النظام
            </a>
            <?php endif; ?>
        </nav>

        <!-- User footer -->
        <div class="px-3 py-3 shrink-0" style="border-top: 1px solid rgb(var(--sidebar-border)); background: rgba(0,0,0,0.2);">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl flex items-center justify-center text-sm font-bold shrink-0"
                     style="background: rgb(var(--primary)); color: white;">
                    <?= strtoupper(mb_substr($_SESSION['username'] ?? 'م', 0, 1, 'UTF-8')) ?>
                </div>
                <div class="flex-1 min-w-0">
                    <a href="<?= $basePathSafe ?>/profile"
                       class="block focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-1 focus:ring-offset-slate-900 rounded">
                        <p class="text-sm font-bold text-white truncate leading-tight">
                            <?= htmlspecialchars($_SESSION['username'] ?? 'مستخدم') ?>
                        </p>
                        <p class="text-[11px] font-medium mt-0.5" style="color: rgb(var(--sidebar-muted));">
                            <?= ($_SESSION['role'] ?? '') === 'admin' ? 'مدير النظام' : 'كاشير' ?>
                        </p>
                    </a>
                </div>
                <form method="POST" action="<?= $basePathSafe ?>/api/logout">
                    <button type="submit"
                            class="w-9 h-9 rounded-xl flex items-center justify-center transition-all duration-200 focus:ring-2 focus:ring-red-400 focus:ring-offset-1 focus:ring-offset-slate-900 cursor-pointer"
                            style="background: rgba(255,255,255,0.06); color: rgb(var(--sidebar-muted));"
                            onmouseover="this.style.background='rgba(220,38,38,0.15)';this.style.color='rgb(248 113 113)'"
                            onmouseout="this.style.background='rgba(255,255,255,0.06)';this.style.color='rgb(100 116 139)'"
                            title="تسجيل الخروج"
                            aria-label="تسجيل الخروج">
                        <i class="fa-solid fa-arrow-right-from-bracket text-sm" aria-hidden="true"></i>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <!-- Main Content Wrapper -->
    <div class="flex-1 flex flex-col overflow-hidden relative min-w-0">
        <!-- Top Navbar -->
        <header class="h-16 glass flex items-center justify-between px-4 md:px-6 shrink-0 sticky top-0 z-[var(--z-header)]"
                style="border-bottom: 1px solid rgb(var(--border));">
            <div class="flex items-center gap-3">
                <!-- Mobile toggle -->
                <button type="button" id="sidebar-toggle"
                        class="w-9 h-9 md:hidden flex items-center justify-center rounded-xl transition-colors duration-200 cursor-pointer"
                        style="color: rgb(var(--muted-foreground));"
                        aria-label="فتح القائمة" aria-expanded="false">
                    <i class="fa-solid fa-bars text-base" aria-hidden="true"></i>
                </button>
                <h2 class="text-lg md:text-xl font-bold leading-tight truncate" style="color: rgb(var(--foreground));">
                    <?= htmlspecialchars($title ?? 'لوحة التحكم') ?>
                </h2>
            </div>
            <div class="flex items-center gap-2">
                <!-- Search -->
                <div class="hidden md:flex items-center rounded-xl px-3 py-2 gap-2 w-44 lg:w-56 transition-all"
                     style="background: rgb(var(--muted)); border: 1.5px solid rgb(var(--border));">
                    <i class="fa-solid fa-search text-xs shrink-0" style="color: rgb(var(--muted-foreground));" aria-hidden="true"></i>
                    <input type="search" placeholder="بحث..." aria-label="بحث"
                           class="bg-transparent border-none outline-none text-sm flex-1 min-w-0"
                           style="color: rgb(var(--foreground));">
                </div>

                <!-- Notifications -->
                <button type="button"
                        class="w-9 h-9 flex items-center justify-center rounded-xl transition-colors duration-200 relative cursor-pointer"
                        style="color: rgb(var(--muted-foreground));"
                        onmouseover="this.style.background='rgb(var(--muted))'"
                        onmouseout="this.style.background=''"
                        aria-label="الإشعارات">
                    <i class="fa-regular fa-bell text-base" aria-hidden="true"></i>
                    <span class="absolute top-1.5 right-1.5 w-2 h-2 rounded-full bg-red-500 ring-2 ring-white" aria-hidden="true"></span>
                </button>

                <!-- Date -->
                <div class="hidden lg:flex items-center gap-2 text-sm font-semibold px-3 py-2 rounded-xl"
                     style="color: rgb(var(--muted-foreground)); background: rgb(var(--muted)); border: 1.5px solid rgb(var(--border));"
                     role="img" aria-label="التاريخ">
                    <i class="fa-regular fa-calendar text-xs" aria-hidden="true"></i>
                    <time datetime="<?= date('Y-m-d') ?>"><?= date('Y/m/d') ?></time>
                </div>
            </div>
        </header>

        <!-- Main Scrollable Area -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-6 lg:p-8 min-h-0"
              style="background: rgb(var(--background));">
