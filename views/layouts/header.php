<?php
if (!isset($basePath)) {
    $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
    $basePath = $basePath === '' || $basePath === '\\' ? '' : $basePath;
}
// عندما يكون basePath '/' نستخدم '' لتفادي مسارات مزدوجة مثل //css/app.css
if ($basePath === '/') {
    $basePath = '';
}
$basePathSafe = htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="app-base" content="<?= $basePathSafe ?>">
    <link rel="icon" type="image/svg+xml" href="<?= $basePathSafe ?>/favicon.svg">
    <title><?= htmlspecialchars($title ?? 'نظام المخزون') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Tajawal', 'system-ui', 'sans-serif'] },
                    borderRadius: { '2xl': '1.5rem' }
                }
            }
        };
    </script>
    <link rel="stylesheet" href="<?= $basePathSafe ?>/css/app.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <?php if (!empty($loadChartJs)): ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
    <?php endif; ?>
    <style>
        /* Design system: Industrial/Utilitarian — skills/frontend-design, ui-ux-pro-max */
        body { font-family: 'Tajawal', sans-serif; font-size: 16px; line-height: 1.6; color: rgb(var(--foreground)); background: rgb(var(--background)); }
        .touch-target { min-width: 44px; min-height: 44px; }
        .glass { background: rgba(255,255,255,.94); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); border-color: rgb(var(--border)); }
        .card-hover { transition: box-shadow var(--duration-normal) var(--ease-out); }
        .card-hover:hover { box-shadow: var(--shadow-card-hover); }
        .table-row-hover:hover { background: rgb(var(--muted)); }
        @media (max-width: 767px) {
            body.mobile-menu-open #app-sidebar { transform: translateX(0); }
            body.mobile-menu-open #sidebar-backdrop { display: block !important; }
            body.mobile-menu-open { overflow: hidden; }
        }
    </style>
</head>
<body class="flex h-screen overflow-hidden antialiased">
