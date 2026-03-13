<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\AuthHelper;
use App\Models\ActivityLog;

class ActivityLogController extends Controller
{
    public function index(): void
    {
        AuthHelper::requireRole('admin');

        $entries = ActivityLog::getRecent(200);

        $this->view('activity_log/index', [
            'title'  => 'سجل النشاط',
            'entries' => $entries,
        ]);
    }

    /** GET /api/activity-log/list — JSON for mobile (admin only) */
    public function indexApi(): void
    {
        AuthHelper::requireRole('admin');
        $limit = max(1, min(200, (int) ($_GET['limit'] ?? 100)));
        $this->jsonResponse(['data' => ActivityLog::getRecent($limit)]);
    }
}
