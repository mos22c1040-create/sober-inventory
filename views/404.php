<?php
$basePath = rtrim((string)($_ENV['APP_SUBDIR'] ?? getenv('APP_SUBDIR') ?: ''), '/');
$bp = htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="<?= $bp ?>/favicon.svg">
    <title>الصفحة غير موجودة — 404</title>
    <script src="https://cdn.tailwindcss.com?v=3"></script>
    <link rel="stylesheet" href="<?= $bp ?>/css/app.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Tajawal', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            background: linear-gradient(145deg, #06091a 0%, #0d1535 40%, #0e1f5c 70%, #1a2d8a 100%);
            position: relative;
            overflow: hidden;
        }

        /* Animated background orbs */
        body::before {
            content: '';
            position: fixed;
            top: -20%;
            left: -10%;
            width: 55%;
            height: 55%;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(79,70,229,0.15) 0%, transparent 70%);
            animation: orb1 8s ease-in-out infinite;
            pointer-events: none;
        }
        body::after {
            content: '';
            position: fixed;
            bottom: -20%;
            right: -10%;
            width: 50%;
            height: 50%;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(16,185,129,0.10) 0%, transparent 70%);
            animation: orb2 10s ease-in-out infinite;
            pointer-events: none;
        }

        @keyframes orb1 {
            0%, 100% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(3%, 4%) scale(1.1); }
        }
        @keyframes orb2 {
            0%, 100% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(-3%, -4%) scale(1.08); }
        }

        .error-card {
            background: rgba(16, 23, 42, 0.75);
            backdrop-filter: blur(28px) saturate(1.8);
            -webkit-backdrop-filter: blur(28px) saturate(1.8);
            border: 1px solid rgba(255,255,255,0.10);
            border-radius: 1.75rem;
            box-shadow:
                0 0 0 1px rgba(79,70,229,0.12),
                0 32px 80px -12px rgba(0,0,0,0.6),
                inset 0 1px 0 rgba(255,255,255,0.08);
            padding: 3rem 2.5rem;
            width: 100%;
            max-width: 440px;
            text-align: center;
            animation: cardIn 0.6s cubic-bezier(0.34,1.56,0.64,1) both;
            position: relative;
            overflow: hidden;
        }

        .error-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 2px;
            background: linear-gradient(90deg, transparent, rgba(79,70,229,0.8), rgba(129,120,246,1), rgba(79,70,229,0.8), transparent);
        }

        @keyframes cardIn {
            from { opacity: 0; transform: translateY(28px) scale(0.95); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }

        .error-number {
            font-size: 6rem;
            font-weight: 800;
            line-height: 1;
            letter-spacing: -0.06em;
            background: linear-gradient(135deg, rgba(129,120,246,0.4) 0%, rgba(79,70,229,0.7) 50%, rgba(99,102,241,0.4) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            user-select: none;
            margin-bottom: -0.5rem;
        }

        .icon-box {
            width: 5rem;
            height: 5rem;
            border-radius: 1.25rem;
            background: rgba(79,70,229,0.15);
            border: 1px solid rgba(79,70,229,0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-6px); }
        }

        .btn-home {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.75rem;
            background: linear-gradient(135deg, rgb(79,70,229), rgb(109,100,225));
            color: white;
            border-radius: 0.875rem;
            font-weight: 700;
            font-size: 0.9rem;
            text-decoration: none;
            transition: all 0.2s cubic-bezier(0.34,1.56,0.64,1);
            box-shadow: 0 6px 20px rgba(79,70,229,0.4);
        }
        .btn-home:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 28px rgba(79,70,229,0.55);
        }
        .btn-home:active { transform: scale(0.97); }
    </style>
</head>
<body>
    <div class="error-card">

        <div class="error-number">404</div>

        <div class="icon-box">
            <i class="fa-solid fa-magnifying-glass text-3xl" style="color: rgb(129,120,246);" aria-hidden="true"></i>
        </div>

        <h1 class="text-xl font-bold mb-3" style="color: rgba(241,245,249,0.95);">
            الصفحة غير موجودة
        </h1>
        <p class="text-sm mb-8 leading-relaxed" style="color: rgba(148,163,184,0.85);">
            الصفحة التي تبحث عنها غير موجودة أو ربما تم نقلها إلى مكان آخر.
        </p>

        <a href="<?= $bp ?>/dashboard" class="btn-home">
            <i class="fa-solid fa-house" aria-hidden="true"></i>
            العودة للوحة التحكم
        </a>

        <!-- Grid decoration -->
        <div style="position:absolute; inset:0; background-image: linear-gradient(rgba(255,255,255,0.015) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.015) 1px, transparent 1px); background-size: 40px 40px; pointer-events:none; border-radius:1.75rem;"></div>
    </div>
</body>
</html>
