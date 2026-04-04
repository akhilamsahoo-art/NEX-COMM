<?php

namespace App\Filament\Seller\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Order;
use Carbon\Carbon;

class SellerSalesChart extends ChartWidget
{
    protected static ?int $sort = 2;
    protected static ?string $heading = 'Sales (Last 7 Days)';

    protected function getData(): array
    {
        $tenantId = auth()->user()->tenant_id;

        $data = [];
        $labels = [];

        // Loop for last 7 days
        for ($i = 6; $i >= 0; $i--) {

            $date = Carbon::now()->subDays($i);

            $total = Order::where('tenant_id', $tenantId)
                ->whereDate('created_at', $date)
                ->sum('total_price');

            $data[] = (float) $total;
            $labels[] = $date->format('D'); // Mon, Tue, Wed...
        }

        return [
            'datasets' => [
                [
                    'label' => 'Sales ($)',
                    'data' => $data,
                    'borderWidth' => 2,
                    'tension' => 0.4, // smooth curve
                    'fill' => true,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}