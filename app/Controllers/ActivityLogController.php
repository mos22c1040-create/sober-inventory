<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\AuthHelper;
use App\Models\ActivityLog;

class ActivityLogController extends Controller
{
    public function index(): void
    {
        AuthHelper::checkAuth();
        AuthHelper::requireRole('admin');

        $entries = ActivityLog::getRecent(200);

        $this->view('activity_log/index', [
            'title'  => 'سجل النشاط',
            'entries' => $entries,
        ]);
    }
}
