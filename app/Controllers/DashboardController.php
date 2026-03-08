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

        try {
            $todaySales = Sale::todayTotal();
            $todayCount = Sale::todayCount();
            $recentSales = Sale::all(10);
        } catch (PDOException $e) {
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
        ]);
    }
}
