<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\AuthHelper;
use App\Helpers\FileCache;
use App\Models\Product;
use App\Models\Sale;
use PDOException;

class DashboardController extends Controller
{
    public function index(): void
    {
        AuthHelper::requireAuth();

        $todaySales = 0.0;
        $todayCount = 0;
        $productCount = 0;
        $lowStockCount = 0;
        $recentSales = [];
        $dailyTotals = [];

        $dayNames = ['الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'];

        try {
            $todaySales  = Sale::todayTotal();
            $todayCount  = Sale::todayCount();
            $recentSales = Sale::all(10);

            // Cache the 7-day daily totals for 5 minutes — avoids re-aggregating on every page load
            $rawDaily = FileCache::remember(
                'dashboard_daily_totals_7',
                static fn() => Sale::getDailyTotalsLastDays(7),
                300
            );

            $dailyTotals = [];
            for ($i = 6; $i >= 0; $i--) {
                $d             = date('Y-m-d', strtotime("-{$i} days"));
                $dailyTotals[] = [
                    'date'  => $d,
                    'total' => (float) ($rawDaily[$d] ?? 0.0),
                    'label' => $dayNames[(int) date('w', strtotime($d))],
                ];
            }
        } catch (PDOException $e) {
            $dailyTotals = [];
            for ($i = 6; $i >= 0; $i--) {
                $d             = date('Y-m-d', strtotime("-{$i} days"));
                $dailyTotals[] = ['date' => $d, 'total' => 0.0, 'label' => $dayNames[(int) date('w', strtotime($d))]];
            }
        }

        try {
            // Use COUNT query instead of fetching all rows
            $productCount  = FileCache::remember('dashboard_product_count', static fn() => Product::count(), 120);
            $lowStockCount = Product::countLowStock();
        } catch (PDOException $e) {
            // Table missing
        }

        $this->view('dashboard/index', [
            'title'        => 'نظرة عامة على لوحة التحكم',
            'todaySales'   => $todaySales,
            'todayCount'   => $todayCount,
            'productCount' => $productCount,
            'lowStockCount'=> $lowStockCount,
            'recentSales'  => $recentSales,
            'dailyTotals'  => $dailyTotals ?? [],
            'loadChartJs'  => true,
        ]);
    }
}
