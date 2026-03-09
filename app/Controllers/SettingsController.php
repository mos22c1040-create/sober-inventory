<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\AuthHelper;
use App\Helpers\Security;

class SettingsController extends Controller
{
    public function index(): void
    {
        AuthHelper::checkAuth();
        AuthHelper::requireRole('admin');

        $settings = (array) include BASE_PATH . '/config/app_settings.php';

        $this->view('settings/index', [
            'title'     => 'إعدادات النظام',
            'settings'  => $settings,
            'csrfToken' => Security::generateCsrfToken(),
        ]);
    }

    public function store(): void
    {
        AuthHelper::checkAuth();
        AuthHelper::requireRole('admin');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'طريقة الطلب غير مسموح بها.'], 405);
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        if (!Security::validateCsrfToken((string) ($input['csrf_token'] ?? ''))) {
            $this->jsonResponse(['error' => 'رمز الأمان منتهٍ.'], 403);
        }

        $appName = trim(Security::sanitizeString((string) ($input['app_name'] ?? '')));
        $currencySymbol = trim(Security::sanitizeString((string) ($input['currency_symbol'] ?? '')));

        if ($appName === '') {
            $appName = 'نظام المخزون';
        }
        if ($currencySymbol === '') {
            $currencySymbol = 'د.ع';
        }

        $data = [
            'app_name'        => $appName,
            'currency_symbol' => $currencySymbol,
        ];

        $dir = BASE_PATH . '/storage';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $file = $dir . '/settings.json';
        if (@file_put_contents($file, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) === false) {
            $this->jsonResponse(['error' => 'فشل حفظ الإعدادات.'], 500);
        }

        $this->jsonResponse(['success' => true, 'message' => 'تم حفظ الإعدادات.']);
    }
}
