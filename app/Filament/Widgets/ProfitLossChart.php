<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class ProfitLossChart extends ChartWidget
{
    protected static ?int $sort = 2;
    protected static ?string $heading = 'Monthly Profit vs Cost';
    protected int | string | array $columnSpan = 2; 
    protected static ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $user = auth()->user();

        // Unique cache key per tenant/admin
        $cacheKey = 'profit_loss_chart_data_' . ($user->tenant_id ?? 'admin');

        // Note: During development, you can change 30 to 0 to disable caching
        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($user) {
            $sixMonthsAgo = Carbon::now()->subMonths(5)->startOfMonth();

            $query = DB::table('orders')
                ->join('order_items', 'orders.id', '=', 'order_items.order_id')
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->selectRaw('
                    DATE_FORMAT(orders.created_at, "%b") as month,
                    DATE_FORMAT(orders.created_at, "%Y-%m") as month_key,
                    SUM(COALESCE(order_items.quantity, 0) * COALESCE(order_items.price, 0)) as total_revenue,
                    SUM(COALESCE(order_items.quantity, 0) * COALESCE(products.cost_price, 0)) as total_cost
                ')
                /** * ✅ SYNC WITH DASHBOARD: 
                 * If your Total Sales widget shows $0, it likely only counts 'delivered'.
                 * Change this list to match exactly what your store considers "Successful Sales".
                 */
                ->whereIn('orders.order_status', ['delivered']) 
                ->where('orders.created_at', '>=', $sixMonthsAgo);

            // Seller isolation
            if ($user->role === 'seller') {
                $query->where('products.tenant_id', $user->tenant_id);
            }

            $data = $query->groupBy('month_key', 'month')
                ->orderBy('month_key')
                ->get()
                ->keyBy('month');

            $labels = [];
            $netProfitData = [];
            $totalCostData = [];

            for ($i = 5; $i >= 0; $i--) {
                $monthLabel = Carbon::now()->subMonths($i)->format('M');
                $labels[] = $monthLabel;

                $monthData = $data->get($monthLabel);
                
                // Ensure we have numbers, even if no data exists for the month
                $rev = $monthData ? (float) $monthData->total_revenue : 0;
                $cost = $monthData ? (float) $monthData->total_cost : 0;

                $totalCostData[] = $cost;
                $netProfitData[] = $rev - $cost;
            }

            return [
                'datasets' => [
                    [
                        'label' => 'Net Profit',
                        'data' => $netProfitData,
                        'borderColor' => '#10b981', // Green
                        'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                        'fill' => 'start',
                        'tension' => 0.4,
                    ],
                    [
                        'label' => 'Total Cost (COGS)',
                        'data' => $totalCostData,
                        'borderColor' => '#ef4444', // Red
                        'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                        'fill' => false,
                        'tension' => 0.4,
                    ],
                ],
                'labels' => $labels,
            ];
        });
    }

    protected function getType(): string
    {
        return 'line';
    }
}