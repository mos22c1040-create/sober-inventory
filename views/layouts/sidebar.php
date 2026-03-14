    <?php
    if (!isset($basePath)) {
        $basePath = rtrim((string)($_ENV['APP_SUBDIR'] ?? getenv('APP_SUBDIR') ?: ''), '/');
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
        'types'      => strpos($pathForNav, '/types') === 0,
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
    $sidebarLowStock = (class_exists('\App\Models\Product')) ? \App\Models\Product::countLowStock() : 0;
    ?>

    <!-- Mobile Backdrop -->
    <div id="sidebar-backdrop"
         class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[var(--z-sidebar)] transition-opacity duration-300 md:!hidden"
         style="display: none;"
         aria-hidden="true"></div>

    <!-- Sidebar -->
    <aside id="app-sidebar"
           class="fixed md:relative right-0 top-0 bottom-0 flex flex-col z-30 transition-transform duration-300 ease-out translate-x-full md:translate-x-0 shrink-0"
           style="width: var(--sidebar-width); background: rgb(var(--sidebar-bg)); border-inline-start: 1px solid rgb(var(--sidebar-border));">

        <!-- Logo / Brand -->
        <div class="h-16 flex items-center gap-3 px-4 shrink-0" style="border-bottom: 1px solid rgb(var(--sidebar-border));">
            <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0"
                 style="background: rgb(var(--primary)); box-shadow: 0 2px 12px rgb(79 70 229 / 0.45);">
                <i class="fa-solid fa-boxes-stacked text-white text-sm" aria-hidden="true"></i>
            </div>
            <div class="min-w-0 flex-1">
                <span class="font-bold text-white text-sm truncate block leading-tight tracking-tight">
                    <?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?>
                </span>
                <span class="text-[10px] font-semibold tracking-widest uppercase" style="color: rgb(var(--sidebar-muted));">POS System</span>
            </div>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 overflow-y-auto py-3 px-2.5 space-y-0.5" aria-label="القائمة الرئيسية">

            <!-- POS — Featured CTA -->
            <a href="<?= $basePathSafe ?>/pos"
               class="flex items-center gap-3 px-3 py-2.5 min-h-[44px] text-sm font-bold rounded-xl mb-2.5 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-emerald-400/50 focus:ring-offset-1 <?= $nav['pos'] ? 'sidebar-pos-link' : 'text-emerald-400 hover:bg-emerald-900/20 hover:text-emerald-300' ?>"
               title="نقطة البيع">
                <span class="w-5 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-cash-register" aria-hidden="true"></i>
                </span>
                <span class="flex-1">نقطة البيع</span>
                <span class="text-[9px] font-black px-1.5 py-0.5 rounded-md tracking-wide"
                      style="background: rgb(16 185 129 / 0.18); color: rgb(52 211 153);">POS</span>
            </a>

            <!-- Divider + Label -->
            <div class="pt-1 pb-0.5">
                <p class="px-3 py-1 text-[9.5px] font-bold tracking-widest uppercase" style="color: rgb(var(--sidebar-muted));">الرئيسية</p>
            </div>

            <a href="<?= $basePathSafe ?>/dashboard"
               class="flex items-center gap-3 px-3 py-2.5 min-h-[44px] text-sm font-medium rounded-xl transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-400/50 focus:ring-offset-1 relative <?= $nav['dashboard'] ? $activeClass : $inactiveClass ?>"
               title="لوحة التحكم">
                <span class="w-5 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-house-chimney" aria-hidden="true"></i>
                </span>
                لوحة التحكم
            </a>

            <a href="<?= $basePathSafe ?>/products"
               class="flex items-center gap-3 px-3 py-2.5 min-h-[44px] text-sm font-medium rounded-xl transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-400/50 focus:ring-offset-1 relative <?= $nav['products'] ? $activeClass : $inactiveClass ?>"
               title="المنتجات">
                <span class="w-5 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-tags" aria-hidden="true"></i>
                </span>
                <span class="flex-1">المنتجات</span>
                <?php if ($sidebarLowStock > 0): ?>
                <span class="min-w-[18px] h-[18px] px-1 flex items-center justify-center rounded-full text-[10px] font-black text-white bg-red-500"
                      title="<?= (int)$sidebarLowStock ?> منتج منخفض المخزون">
                    <?= $sidebarLowStock > 99 ? '99+' : (int)$sidebarLowStock ?>
                </span>
                <?php endif; ?>
            </a>

            <a href="<?= $basePathSafe ?>/categories"
               class="flex items-center gap-3 px-3 py-2.5 min-h-[44px] text-sm font-medium rounded-xl transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-400/50 focus:ring-offset-1 relative <?= $nav['categories'] ? $activeClass : $inactiveClass ?>"
               title="التصنيفات">
                <span class="w-5 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-layer-group" aria-hidden="true"></i>
                </span>
                التصنيفات
            </a>

            <a href="<?= $basePathSafe ?>/types"
               class="flex items-center gap-3 px-3 py-2.5 min-h-[44px] text-sm font-medium rounded-xl transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-400/50 focus:ring-offset-1 relative <?= $nav['types'] ? $activeClass : $inactiveClass ?>"
               title="الأنواع">
                <span class="w-5 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-shapes" aria-hidden="true"></i>
                </span>
                الأنواع
            </a>

            <a href="<?= $basePathSafe ?>/sales"
               class="flex items-center gap-3 px-3 py-2.5 min-h-[44px] text-sm font-medium rounded-xl transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-400/50 focus:ring-offset-1 relative <?= $nav['sales'] ? $activeClass : $inactiveClass ?>"
               title="المبيعات">
                <span class="w-5 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-file-invoice-dollar" aria-hidden="true"></i>
                </span>
                المبيعات
            </a>

            <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>

            <div class="pt-3 pb-0.5">
                <p class="px-3 py-1 text-[9.5px] font-bold tracking-widest uppercase" style="color: rgb(var(--sidebar-muted));">المشتريات</p>
            </div>

            <a href="<?= $basePathSafe ?>/purchases"
               class="flex items-center gap-3 px-3 py-2.5 min-h-[44px] text-sm font-medium rounded-xl transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-400/50 focus:ring-offset-1 relative <?= $nav['purchases'] ? $activeClass : $inactiveClass ?>"
               title="المشتريات">
                <span class="w-5 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-truck-ramp-box" aria-hidden="true"></i>
                </span>
                المشتريات
            </a>

            <a href="<?= $basePathSafe ?>/expenses"
               class="flex items-center gap-3 px-3 py-2.5 min-h-[44px] text-sm font-medium rounded-xl transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-400/50 focus:ring-offset-1 relative <?= $nav['expenses'] ? $activeClass : $inactiveClass ?>"
               title="المصروفات">
                <span class="w-5 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-money-bill-transfer" aria-hidden="true"></i>
                </span>
                المصروفات
            </a>

            <div class="pt-3 pb-0.5">
                <p class="px-3 py-1 text-[9.5px] font-bold tracking-widest uppercase" style="color: rgb(var(--sidebar-muted));">التحليلات</p>
            </div>

            <a href="<?= $basePathSafe ?>/reports"
               class="flex items-center gap-3 px-3 py-2.5 min-h-[44px] text-sm font-medium rounded-xl transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-400/50 focus:ring-offset-1 relative <?= $nav['reports'] ? $activeClass : $inactiveClass ?>"
               title="التقارير">
                <span class="w-5 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-chart-pie" aria-hidden="true"></i>
                </span>
                التقارير
            </a>

            <div class="pt-3 pb-0.5">
                <p class="px-3 py-1 text-[9.5px] font-bold tracking-widest uppercase" style="color: rgb(var(--sidebar-muted));">الإدارة</p>
            </div>

            <a href="<?= $basePathSafe ?>/users"
               class="flex items-center gap-3 px-3 py-2.5 min-h-[44px] text-sm font-medium rounded-xl transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-400/50 focus:ring-offset-1 relative <?= $nav['users'] ? $activeClass : $inactiveClass ?>"
               title="المستخدمون">
                <span class="w-5 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-users-gear" aria-hidden="true"></i>
                </span>
                المستخدمون
            </a>

            <a href="<?= $basePathSafe ?>/activity-log"
               class="flex items-center gap-3 px-3 py-2.5 min-h-[44px] text-sm font-medium rounded-xl transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-400/50 focus:ring-offset-1 relative <?= $nav['activity'] ? $activeClass : $inactiveClass ?>"
               title="سجل النشاط">
                <span class="w-5 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i>
                </span>
                سجل النشاط
            </a>

            <a href="<?= $basePathSafe ?>/settings"
               class="flex items-center gap-3 px-3 py-2.5 min-h-[44px] text-sm font-medium rounded-xl transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-400/50 focus:ring-offset-1 relative <?= $nav['settings'] ? $activeClass : $inactiveClass ?>"
               title="الإعدادات">
                <span class="w-5 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-gear" aria-hidden="true"></i>
                </span>
                الإعدادات
            </a>

            <?php endif; ?>
        </nav>

        <!-- User Footer -->
        <div class="px-3 py-3 shrink-0" style="border-top: 1px solid rgb(var(--sidebar-border));">
            <div class="flex items-center gap-2.5">
                <!-- Avatar -->
                <div class="w-8 h-8 rounded-xl flex items-center justify-center text-xs font-bold shrink-0 text-white"
                     style="background: rgb(var(--primary));">
                    <?= strtoupper(mb_substr($_SESSION['username'] ?? 'م', 0, 1, 'UTF-8')) ?>
                </div>
                <!-- User Info -->
                <a href="<?= $basePathSafe ?>/profile"
                   class="flex-1 min-w-0 focus:outline-none focus:ring-2 focus:ring-blue-400/50 rounded-lg">
                    <p class="text-xs font-bold text-white truncate leading-tight">
                        <?= htmlspecialchars($_SESSION['username'] ?? 'مستخدم', ENT_QUOTES, 'UTF-8') ?>
                    </p>
                    <p class="text-[10px] font-medium mt-0.5 truncate" style="color: rgb(var(--sidebar-muted));">
                        <?= ($_SESSION['role'] ?? '') === 'admin' ? 'مدير النظام' : (($_SESSION['role'] ?? '') === 'manager' ? 'مدير' : 'كاشير') ?>
                    </p>
                </a>
                <!-- Theme Toggle -->
                <button type="button" id="theme-toggle" title="تبديل المظهر"
                        class="w-8 h-8 rounded-xl flex items-center justify-center transition-all duration-200 hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-white/20"
                        style="color: rgb(var(--sidebar-muted));"
                        aria-label="تبديل المظهر">
                    <i class="fa-solid fa-moon text-xs" id="theme-icon" aria-hidden="true"></i>
                </button>
                <!-- Logout -->
                <form method="POST" action="<?= $basePathSafe ?>/api/logout">
                    <button type="submit"
                            class="w-8 h-8 rounded-xl flex items-center justify-center transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-red-400/50 group"
                            style="color: rgb(var(--sidebar-muted));"
                            onmouseover="this.style.background='rgb(239 68 68 / 0.12)'; this.style.color='rgb(252 165 165)'"
                            onmouseout="this.style.background=''; this.style.color='rgb(var(--sidebar-muted))'"
                            title="تسجيل الخروج"
                            aria-label="تسجيل الخروج">
                        <i class="fa-solid fa-arrow-right-from-bracket text-xs" aria-hidden="true"></i>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <!-- Main Content Wrapper -->
    <div class="flex-1 flex flex-col overflow-hidden relative min-w-0">

        <!-- Top Header -->
        <header class="h-16 glass flex items-center justify-between px-4 md:px-6 shrink-0 sticky top-0 z-[var(--z-header)]"
                style="border-bottom: 1px solid rgb(var(--border));">
            <div class="flex items-center gap-3">
                <!-- Mobile menu toggle -->
                <button type="button" id="sidebar-toggle"
                        class="w-9 h-9 md:hidden flex items-center justify-center rounded-xl transition-colors duration-200"
                        style="color: rgb(var(--muted-foreground));"
                        aria-label="فتح القائمة" aria-expanded="false">
                    <i class="fa-solid fa-bars-staggered text-sm" aria-hidden="true"></i>
                </button>
                <h2 class="text-base md:text-lg font-bold leading-tight truncate" style="color: rgb(var(--foreground));">
                    <?= htmlspecialchars($title ?? 'لوحة التحكم', ENT_QUOTES, 'UTF-8') ?>
                </h2>
            </div>

            <div class="flex items-center gap-2">
                <!-- Search -->
                <div class="hidden md:flex items-center rounded-xl px-3 py-2 gap-2 w-44 lg:w-56 transition-all focus-within:shadow-sm"
                     style="background: rgb(var(--muted)); border: 1.5px solid rgb(var(--border));">
                    <i class="fa-solid fa-magnifying-glass text-xs shrink-0" style="color: rgb(var(--muted-foreground));" aria-hidden="true"></i>
                    <input type="search" placeholder="بحث سريع..." aria-label="بحث"
                           class="bg-transparent border-none outline-none text-sm flex-1 min-w-0 font-medium"
                           style="color: rgb(var(--foreground));">
                </div>

                <!-- Notifications -->
                <button type="button"
                        class="w-9 h-9 flex items-center justify-center rounded-xl transition-colors duration-200 relative hover:bg-gray-100 dark:hover:bg-gray-800"
                        style="color: rgb(var(--muted-foreground));"
                        aria-label="الإشعارات">
                    <i class="fa-regular fa-bell text-sm" aria-hidden="true"></i>
                    <span class="absolute top-1.5 right-1.5 w-2 h-2 rounded-full bg-red-500 ring-2 ring-white" aria-hidden="true"></span>
                </button>

                <!-- Date Display -->
                <div class="hidden lg:flex items-center gap-2 text-xs font-semibold px-3 py-2 rounded-xl"
                     style="color: rgb(var(--muted-foreground)); background: rgb(var(--muted)); border: 1.5px solid rgb(var(--border));"
                     role="img" aria-label="التاريخ">
                    <i class="fa-regular fa-calendar text-xs" aria-hidden="true"></i>
                    <time datetime="<?= date('Y-m-d') ?>"><?= date('Y/m/d') ?></time>
                </div>
            </div>
        </header>

        <!-- Main Scrollable Area -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-6 lg:p-8 min-h-0 main-content-area"
              style="background: rgb(var(--background));">
