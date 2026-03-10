<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <title><?= htmlspecialchars($title ?? 'نظام المخزون') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --color-primary: 59 130 246;
            --color-surface: 248 250 252;
            --color-border: 226 232 240;
            --z-sidebar: 20;
            --z-header: 10;
            --z-modal: 50;
            --touch-min: 44px;
            --transition-fast: 150ms;
            --transition-normal: 250ms;
        }
        body {
            font-family: 'Tajawal', sans-serif;
            font-size: 16px;
            line-height: 1.6;
            color: #1e293b;
        }
        .glass {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
        }
        /* Focus (Accessibility) */
        a:focus-visible, button:focus-visible, input:focus-visible, select:focus-visible, [tabindex="0"]:focus-visible {
            outline: 2px solid rgb(var(--color-primary));
            outline-offset: 2px;
        }
        /* Touch targets */
        .touch-target { min-width: var(--touch-min); min-height: var(--touch-min); }
        /* Reduced motion */
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation-duration: 0.01ms !important; animation-iteration-count: 1 !important; transition-duration: 0.01ms !important; }
        }
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        /* Cards & tables */
        .card-hover { transition: box-shadow var(--transition-normal), transform var(--transition-normal); }
        .card-hover:hover { box-shadow: 0 10px 40px -12px rgba(0,0,0,.12); }
        @media (prefers-reduced-motion: reduce) { .card-hover:hover { transform: none; } }
        .table-row-hover:hover { background-color: #f8fafc; }
        .btn-primary { transition: background-color var(--transition-fast), transform 0.1s; }
        .btn-primary:active { transform: scale(0.98); }
        /* قائمة الموبايل */
        @media (max-width: 767px) {
            body.mobile-menu-open #app-sidebar { transform: translateX(0); }
            body.mobile-menu-open #sidebar-backdrop { display: block !important; }
            body.mobile-menu-open { overflow: hidden; }
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 flex h-screen overflow-hidden antialiased">
