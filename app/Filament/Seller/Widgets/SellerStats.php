<?php

namespace App\Filament\Seller\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Order;
use App\Models\Product;

class SellerStats extends BaseWidget
{
    protected static ?int $sort = 1;
    protected function getStats(): array
    {
        $tenantId = auth()->user()->tenant_id;

        $totalOrders = Order::where('tenant_id', $tenantId)->count();
        $totalProducts = Product::where('tenant_id', $tenantId)->count();
        $revenue = Order::where('tenant_id', $tenantId)->sum('total_price');

        return [

            // 🟦 Orders
            Stat::make('Total Orders', $totalOrders)
                ->description('All time orders')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('primary'),

            // 🟩 Products
            Stat::make('Total Products', $totalProducts)
                ->description('Active products')
                ->descriptionIcon('heroicon-m-cube')
                ->color('success'),

            // 🟨 Revenue
            Stat::make('Revenue', '$ ' . number_format($revenue, 2))
                // ->money('USD')
                ->description('Total earnings')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('warning'),
        ];
    }
}