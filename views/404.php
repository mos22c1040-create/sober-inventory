<?php
$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
$basePath = ($basePath === '' || $basePath === '\\' || $basePath === '/') ? '' : $basePath;
$bp = htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="<?= $bp ?>/favicon.svg">
    <title>الصفحة غير موجودة — 404</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="<?= $bp ?>/css/app.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style> body { font-family: 'Tajawal', sans-serif; } </style>
</head>
<body class="error-page">
    <div class="w-full max-w-lg rounded-xl overflow-hidden login-card animate-slide-up" style="background: rgb(var(--color-surface-elevated)); border: 1px solid rgb(var(--border)); box-shadow: var(--shadow-lg);">
        <div class="relative px-8 pt-10 pb-6 text-center">
            <span class="error-code block leading-none">404</span>
            <div class="w-20 h-20 mx-auto -mt-4 mb-6 rounded-xl flex items-center justify-center relative z-10" style="background: rgb(var(--muted)); color: rgb(var(--muted-foreground));">
                <i class="fa-solid fa-magnifying-glass text-3xl" aria-hidden="true"></i>
            </div>
            <h1 class="text-xl font-bold mb-2" style="color: rgb(var(--foreground));">الصفحة غير موجودة</h1>
            <p class="text-sm mb-8 max-w-sm mx-auto" style="color: rgb(var(--muted-foreground));">الصفحة التي تبحث عنها غير موجودة أو تم نقلها.</p>
            <a href="<?= $bp ?>/dashboard" class="inline-flex items-center gap-2 min-h-[48px] px-6 py-3 rounded-lg font-bold text-sm focus:ring-2 focus:ring-offset-2 cursor-pointer transition-colors duration-200" style="background: rgb(var(--primary)); color: rgb(var(--primary-foreground));">
                <i class="fa-solid fa-house"></i> العودة للوحة التحكم
            </a>
        </div>
    </div>
</body>
</html>
