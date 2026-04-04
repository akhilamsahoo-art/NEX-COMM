<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    /**
     * Get all analytics data including Profit and Loss.
     */
    public function index()
    {
        // Cache the analytics data for 10 minutes
        $analyticsData = Cache::remember('analytics_data', 10, function () {
            
            // 1. Basic Stats
            $totalOrders = Order::count();
            $totalSales = Order::where('payment_status', 'paid')
            ->sum('total_amount');
            // 2. Profit & Loss Logic
            // We calculate the sum of (quantity sold * product cost_price)
            // This requires a 'cost_price' column in your 'products' table
            $totalCostOfGoods = DB::table('order_product')
                ->join('products', 'order_product.product_id', '=', 'products.id')
                ->selectRaw('SUM(order_product.quantity * products.cost_price) as total_cost')
                ->value('total_cost') ?? 0;

            $grossProfit = $totalSales - $totalCostOfGoods;
            
            // Calculate Profit Margin Percentage
            $profitMargin = $totalSales > 0 
                ? round(($grossProfit / $totalSales) * 100, 2) 
                : 0;

            // 3. Top Performers
            $popularProducts = Product::withCount('orders')
                ->orderBy('orders_count', 'desc')
                ->take(5)
                ->get();

            $topCustomers = User::withCount('orders')
                ->orderBy('orders_count', 'desc')
                ->take(5)
                ->get();

            // 4. Formatting the Response
            return [
                'summary' => [
                    'total_orders' => $totalOrders,
                    'total_revenue' => (float)$totalSales,
                    'total_cost' => (float)$totalCostOfGoods,
                    'gross_profit' => (float)$grossProfit,
                    'profit_margin_percentage' => $profitMargin . '%',
                    'status' => $grossProfit >= 0 ? 'Profit' : 'Loss'
                ],
                'popular_products' => $popularProducts,
                'top_customers' => $topCustomers,
            ];
        });

        return response()->json($analyticsData);
    }
}