    <!-- القائمة الجانبية -->
    <aside class="w-64 bg-[#0f172a] text-white flex-col hidden md:flex shadow-2xl z-20 transition-all duration-300">
        <div class="h-16 flex items-center justify-center border-b border-slate-800 px-4 shrink-0">
            <h1 class="text-xl font-bold tracking-wider text-blue-400 flex items-center shadow-sm">
                <i class="fa-solid fa-box-open ms-3 rounded-lg bg-blue-500/10 p-2 text-blue-500"></i>
                <span class="text-white">المخزون</span>
            </h1>
        </div>

        <nav class="flex-1 overflow-y-auto py-6 px-3 space-y-1">
            <p class="px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">القائمة الرئيسية</p>
            
            <a href="/dashboard" class="flex items-center px-4 py-3 text-sm font-medium rounded-xl bg-blue-600 text-white shadow-md shadow-blue-500/20 group transition-all">
                <i class="fa-solid fa-house w-5 text-center ms-3 text-white transition-transform group-hover:scale-110"></i>
                لوحة التحكم
            </a>
            
            <a href="/products" class="flex items-center px-4 py-3 text-sm font-medium rounded-xl text-slate-300 hover:bg-slate-800 hover:text-white group transition-colors mt-2">
                <i class="fa-solid fa-tags w-5 text-center ms-3 text-slate-400 group-hover:text-blue-400 transition-colors"></i>
                المنتجات
            </a>

            <a href="/categories" class="flex items-center px-4 py-3 text-sm font-medium rounded-xl text-slate-300 hover:bg-slate-800 hover:text-white group transition-colors mt-1">
                <i class="fa-solid fa-layer-group w-5 text-center ms-3 text-slate-400 group-hover:text-amber-400 transition-colors"></i>
                التصنيفات
            </a>

            <div class="my-4 border-t border-slate-800/50"></div>
            <p class="px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2 mt-4">المشتريات</p>

            <a href="/purchases" class="flex items-center px-4 py-3 text-sm font-medium rounded-xl text-slate-300 hover:bg-slate-800 hover:text-white group transition-colors mt-1">
                <i class="fa-solid fa-truck-ramp-box w-5 text-center ms-3 text-slate-400 group-hover:text-purple-400 transition-colors"></i>
                المشتريات
            </a>
            
            <div class="my-4 border-t border-slate-800/50"></div>
            <p class="px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2 mt-4">التحليلات</p>

            <a href="/reports" class="flex items-center px-4 py-3 text-sm font-medium rounded-xl text-slate-300 hover:bg-slate-800 hover:text-white group transition-colors">
                <i class="fa-solid fa-chart-pie w-5 text-center ms-3 text-slate-400 group-hover:text-pink-400 transition-colors"></i>
                التقارير
            </a>

            <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
            <div class="my-4 border-t border-slate-800/50"></div>
            <p class="px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2 mt-4">الإدارة</p>

            <a href="/users" class="flex items-center px-4 py-3 text-sm font-medium rounded-xl text-slate-300 hover:bg-slate-800 hover:text-white group transition-colors">
                <i class="fa-solid fa-users-gear w-5 text-center ms-3 text-slate-400 group-hover:text-teal-400 transition-colors"></i>
                المستخدمون
            </a>
            <?php endif; ?>
        </nav>

        <div class="p-4 border-t border-slate-800 bg-slate-900/50 shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-gradient-to-tr from-blue-500 to-purple-500 flex items-center justify-center text-sm font-bold shadow-lg">
                    <?= strtoupper(mb_substr($_SESSION['username'] ?? 'م', 0, 1, 'UTF-8')) ?>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-white truncate"><?= htmlspecialchars($_SESSION['username'] ?? 'مستخدم') ?></p>
                    <p class="text-xs text-slate-400 truncate"><?= ($_SESSION['role'] ?? '') === 'admin' ? 'مدير' : 'كاشير' ?></p>
                </div>
                <form method="POST" action="/api/logout">
                    <button type="submit" class="w-8 h-8 rounded-lg bg-slate-800 text-slate-400 hover:bg-red-500/10 hover:text-red-400 flex items-center justify-center transition-all" title="تسجيل الخروج">
                        <i class="fa-solid fa-arrow-right-from-bracket"></i>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <!-- Main Content Wrapper -->
    <div class="flex-1 flex flex-col overflow-hidden relative">
        <!-- Top Navbar -->
        <header class="h-16 glass border-b border-gray-200 flex items-center justify-between px-6 z-10 shrink-0">
            <div class="flex items-center gap-4">
                <button class="md:hidden text-gray-500 hover:text-blue-600 transition-colors">
                    <i class="fa-solid fa-bars text-xl"></i>
                </button>
                <h2 class="text-xl font-bold text-slate-800"><?= htmlspecialchars($title ?? 'لوحة التحكم') ?></h2>
            </div>
            
            <!-- Quick Actions & Notifications -->
            <div class="flex items-center gap-5">
                <!-- Search -->
                <div class="hidden md:flex items-center bg-gray-100 rounded-full px-3 py-1.5 border border-gray-200 focus-within:ring-2 focus-within:ring-blue-500/20 focus-within:border-blue-500 transition-all">
                    <i class="fa-solid fa-search text-gray-400 text-sm ml-1 mr-2"></i>
                    <input type="text" placeholder="بحث..." class="bg-transparent border-none outline-none text-sm w-48 text-gray-700">
                </div>

                <!-- Notification Bell -->
                <button class="text-gray-400 hover:text-blue-600 transition-colors relative mt-1">
                    <i class="fa-solid fa-bell text-xl"></i>
                    <span class="absolute top-0 right-0 -mt-1 -mr-1 flex h-3 w-3">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-red-500 border-2 border-white"></span>
                    </span>
                </button>
                
                <!-- Time/Date (Optional) -->
                <div class="hidden lg:block text-xs font-medium text-gray-500 bg-gray-100 px-3 py-1.5 rounded-lg border border-gray-200">
                    <i class="fa-regular fa-clock me-1"></i> <?= date('Y/m/d') ?>
                </div>
            </div>
        </header>

        <!-- Main Scrollable Area -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-[#f8fafc] p-4 md:p-6 lg:p-8">
