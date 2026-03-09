<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\AuthHelper;
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

        try {
            $todaySales = Sale::todayTotal();
            $todayCount = Sale::todayCount();
            $recentSales = Sale::all(10);
            $rawDaily   = Sale::getDailyTotalsLastDays(7);
            $dailyTotals = [];
            $dayNames = ['الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'];
            for ($i = 6; $i >= 0; $i--) {
                $d = date('Y-m-d', strtotime("-$i days"));
                $total = $rawDaily[$d] ?? 0.0;
                $dailyTotals[] = [
                    'date' => $d,
                    'total' => $total,
                    'label' => $dayNames[(int) date('w', strtotime($d))],
                ];
            }
        } catch (PDOException $e) {
            $dayNames = ['الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'];
            $dailyTotals = [];
            for ($i = 6; $i >= 0; $i--) {
                $d = date('Y-m-d', strtotime("-$i days"));
                $dailyTotals[] = ['date' => $d, 'total' => 0.0, 'label' => $dayNames[(int) date('w', strtotime($d))]];
            }
            // Table or column missing (e.g. run storage/patch_sales_table.sql)
        }

        try {
            $productCount = count(Product::all(false));
            $lowStockCount = Product::countLowStock();
        } catch (PDOException $e) {
            // Table missing
        }

        $this->view('dashboard/index', [
            'title' => 'نظرة عامة على لوحة التحكم',
            'todaySales' => $todaySales,
            'todayCount' => $todayCount,
            'productCount' => $productCount,
            'lowStockCount' => $lowStockCount,
            'recentSales' => $recentSales,
            'dailyTotals' => $dailyTotals ?? [],
        ]);
    }
}
