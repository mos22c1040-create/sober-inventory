<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\AuthHelper;
use App\Helpers\Security;
use App\Models\AppSetting;

class SettingsController extends Controller
{
    public function index(): void
    {
        AuthHelper::requireRole('admin');

        $settings = (array) include BASE_PATH . '/config/app_settings.php';

        $this->view('settings/index', [
            'title'     => 'إعدادات النظام',
            'settings'  => $settings,
            'csrfToken' => Security::generateCsrfToken(),
        ]);
    }

    /** GET /api/settings/data — JSON for mobile (admin only) */
    public function getApi(): void
    {
        AuthHelper::requireRole('admin');
        $settings = (array) include BASE_PATH . '/config/app_settings.php';
        $this->jsonResponse(['data' => $settings]);
    }

    public function store(): void
    {
        AuthHelper::requireRole('admin');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'طريقة الطلب غير مسموح بها.'], 405);
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        if (!Security::validateCsrfToken((string) ($input['csrf_token'] ?? ''))) {
            $this->jsonResponse(['error' => 'رمز الأمان منتهٍ.'], 403);
        }

        $appName         = trim(Security::sanitizeString((string) ($input['app_name']        ?? ''), 100));
        $currencySymbol  = trim(Security::sanitizeString((string) ($input['currency_symbol'] ?? ''), 10));
        $timezoneInput   = trim((string) ($input['timezone']         ?? ''));
        $sessionLifetime = max(300, min(86400, (int) ($input['session_lifetime'] ?? 3600)));

        if ($appName === '') {
            $appName = 'نظام المخزون';
        }
        if ($currencySymbol === '') {
            $currencySymbol = 'د.ع';
        }

        // Validate timezone — must be a known PHP timezone identifier
        $timezone = in_array($timezoneInput, timezone_identifiers_list(), true)
            ? $timezoneInput
            : 'Asia/Baghdad';

        $data = [
            'app_name'         => $appName,
            'currency_symbol'  => $currencySymbol,
            'timezone'         => $timezone,
            'session_lifetime' => $sessionLifetime,
        ];

        // Primary: persist to the database (survives container restarts on Railway).
        $savedToDb = AppSetting::setMany($data);

        // Fallback: also write settings.json for local/offline environments.
        $dir  = BASE_PATH . '/storage';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        @file_put_contents(
            $dir . '/settings.json',
            json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        );

        if (!$savedToDb) {
            // DB table missing — settings.json fallback was used; inform the user.
            $this->jsonResponse([
                'success' => true,
                'message' => 'تم حفظ الإعدادات (محلياً). لتثبيت الإعدادات على الخادم شغّل ملف patch_settings_table.pgsql.',
            ]);
        }

        $this->jsonResponse(['success' => true, 'message' => 'تم حفظ الإعدادات.']);
    }

    /**
     * GET /api/settings/check-uploads — التحقق من مجلد رفع الصور (للتحقق من Volume على Railway).
     * يتطلب تسجيل دخول كأدمن.
     */
    public function checkUploads(): void
    {
        AuthHelper::requireRole('admin');

        $productsDir = BASE_PATH . '/public/uploads/products';
        if (!is_dir(BASE_PATH . '/public/uploads')) {
            @mkdir(BASE_PATH . '/public/uploads', 0755, true);
        }
        if (!is_dir($productsDir)) {
            @mkdir($productsDir, 0755, true);
        }

        $exists = is_dir($productsDir);
        $realPath = $exists ? realpath($productsDir) : null;
        $writable = false;
        $message = '';

        if ($exists) {
            $testFile = $productsDir . '/.check_' . time();
            $written = @file_put_contents($testFile, 'ok');
            if ($written !== false && file_exists($testFile)) {
                $content = @file_get_contents($testFile);
                @unlink($testFile);
                $writable = ($content === 'ok');
                $message = $writable
                    ? 'مجلد الصور موجود والكتابة تعمل — Volume يعمل بشكل صحيح.'
                    : 'المجلد موجود لكن القراءة بعد الكتابة فشلت.';
            } else {
                $message = 'المجلد موجود لكن لا يمكن الكتابة فيه. تحقق من Volume Mount Path.';
            }
        } else {
            $message = 'مجلد uploads/products غير موجود ولا يمكن إنشاؤه.';
        }

        $this->jsonResponse([
            'success'  => $exists && $writable,
            'exists'   => $exists,
            'writable' => $writable,
            'path'     => $realPath ?: $productsDir,
            'message'  => $message,
        ]);
    }
}
