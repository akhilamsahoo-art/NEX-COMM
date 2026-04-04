<?php

namespace App\Filament\Widgets;

use App\Models\Order;
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
        // Use Cache to stop the "buffering" lag. 
        // We clear this automatically in Order.php when status changes to 'delivered'.
        return Cache::remember('profit_loss_chart_data', now()->addMinutes(30), function () {
            $sixMonthsAgo = Carbon::now()->subMonths(5)->startOfMonth();

            // Unified Query: Get Revenue and Cost in ONE go for better accuracy
            $data = DB::table('orders')
    ->join('order_items', 'orders.id', '=', 'order_items.order_id')
    ->join('products', 'order_items.product_id', '=', 'products.id')
    ->selectRaw('
        DATE_FORMAT(orders.created_at, "%b") as month,
        DATE_FORMAT(orders.created_at, "%Y-%m") as month_key,
        SUM(order_items.quantity * order_items.price) as total_revenue,
        SUM(order_items.quantity * products.cost_price) as total_cost
    ')
    ->where('orders.order_status', 'delivered')
    ->where('orders.created_at', '>=', $sixMonthsAgo)
    ->groupBy('month_key', 'month')
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