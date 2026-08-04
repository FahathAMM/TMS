<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\Product;
use App\Models\Customer;

class DashboardService
{
    public function getSummary(): array
    {
        $lowStockProducts = Product::where('is_active', true)
            ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
            ->count();

        $recentOrders = Order::with('customer')
            ->latest()
            ->limit(10)
            ->get();

        $ordersChart = Order::whereDate('created_at', '>=', now()->subDays(7))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as orders')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'overall' => [
                'total_customers' => Customer::count(),
                'total_orders'    => Order::count(),
                'total_products'  => Product::where('is_active', true)->count(),
            ],
            'low_stock_count' => $lowStockProducts,
            'recent_orders'   => $recentOrders,
            'sales_chart'     => $ordersChart,
            'tailoring' => [
                'orders_in_production' => Order::where('status', 'in_production')->count(),
                'ready_for_fitting'    => Order::where('status', 'ready_for_fitting')->count(),
                'low_stock_fabrics'    => $lowStockProducts,
                'revenue_today'        => (float) OrderPayment::whereDate('paid_at', now()->toDateString())->sum('amount'),
            ],
        ];
    }
}
