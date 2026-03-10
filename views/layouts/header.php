<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
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
    <link rel="stylesheet" href="/css/app.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Tajawal', sans-serif; font-size: 16px; line-height: 1.6; color: #1e293b; }
        .touch-target { min-width: 44px; min-height: 44px; }
        .glass { background: rgba(255,255,255,.92); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); }
        .card-hover { transition: box-shadow .25s, transform .25s; }
        .card-hover:hover { box-shadow: 0 20px 40px -12px rgba(0,0,0,.12); }
        .table-row-hover:hover { background-color: #f8fafc; }
        @media (max-width: 767px) {
            body.mobile-menu-open #app-sidebar { transform: translateX(0); }
            body.mobile-menu-open #sidebar-backdrop { display: block !important; }
            body.mobile-menu-open { overflow: hidden; }
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 flex h-screen overflow-hidden antialiased">
