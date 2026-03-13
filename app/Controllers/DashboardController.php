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

        $yesterdaySales = 0.0;
        $yesterdayCount  = 0;
        try {
            $todaySales     = Sale::todayTotal();
            $todayCount     = Sale::todayCount();
            $yesterdaySales = Sale::yesterdayTotal();
            $yesterdayCount = Sale::yesterdayCount();
            $recentSales    = Sale::all(10);

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

        $lowStockProducts = [];
        try {
            $productCount   = FileCache::remember('dashboard_product_count', static fn() => Product::count(), 120);
            $lowStockCount  = Product::countLowStock();
            $lowStockProducts = $lowStockCount > 0 ? Product::getLowStockProducts(15) : [];
        } catch (PDOException $e) {
            // Table missing
        }

        $this->view('dashboard/index', [
            'title'             => 'نظرة عامة على لوحة التحكم',
            'todaySales'        => $todaySales,
            'todayCount'        => $todayCount,
            'yesterdaySales'    => $yesterdaySales,
            'yesterdayCount'    => $yesterdayCount,
            'productCount'      => $productCount,
            'lowStockCount'     => $lowStockCount,
            'lowStockProducts'  => $lowStockProducts,
            'recentSales'       => $recentSales,
            'dailyTotals'       => $dailyTotals ?? [],
            'loadChartJs'       => true,
        ]);
    }

    /** GET /api/dashboard — JSON for mobile app. */
    public function indexApi(): void
    {
        AuthHelper::requireAuth();

        $todaySales  = 0.0;
        $todayCount  = 0;
        $productCount = 0;
        $lowStockCount = 0;
        $dailyTotals = [];

        $dayNames = ['الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'];

        try {
            $todaySales   = Sale::todayTotal();
            $todayCount   = Sale::todayCount();
            $productCount = Product::count();
            $lowStockCount = Product::countLowStock();
            $rawDaily     = FileCache::remember(
                'dashboard_daily_totals_7',
                static fn() => Sale::getDailyTotalsLastDays(7),
                300
            );
            for ($i = 6; $i >= 0; $i--) {
                $d = date('Y-m-d', strtotime("-{$i} days"));
                $dailyTotals[] = [
                    'date'  => $d,
                    'total' => (float) ($rawDaily[$d] ?? 0.0),
                    'label' => $dayNames[(int) date('w', strtotime($d))],
                ];
            }
        } catch (\Throwable $e) {
            // keep defaults
        }

        $this->jsonResponse([
            'today_sales'    => $todaySales,
            'today_count'    => $todayCount,
            'product_count'  => $productCount,
            'low_stock_count'=> $lowStockCount,
            'daily_totals'   => $dailyTotals,
        ]);
    }
}
