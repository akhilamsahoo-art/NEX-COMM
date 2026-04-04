<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use App\Filament\Resources\OrderResource;
use App\Filament\Resources\UserResource;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\HtmlString;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;
    protected int | string | array $columnSpan = 'full';
    protected function animateNumber(int|float $number, string $prefix = '', string $suffix = ''): HtmlString
{
    return new HtmlString("
        <div x-data=\"{ 
            count: 0, 
            target: {$number},
            duration: 1000,
            start: null,
            step(timestamp) {
                if (!this.start) this.start = timestamp;
                const progress = Math.min((timestamp - this.start) / this.duration, 1);
                this.count = Math.floor(progress * this.target);
                if (progress < 1) {
                    window.requestAnimationFrame(this.step.bind(this));
                }
            }
        }\" 
        x-init=\"window.requestAnimationFrame(step.bind(\$data))\">
            <span>{$prefix}</span><span x-text=\"count.toLocaleString()\"></span><span>{$suffix}</span>
        </div>
    ");
}

protected function getStats(): array
{
    $user = auth()->user();
    $tenantId = $user->tenant_id;

    // Base queries
    $orderQuery = Order::query();
    $userQuery = User::query();

    // Apply tenant filter ONLY if tenant exists
    if ($tenantId) {
        $orderQuery->where('tenant_id', $tenantId);
        $userQuery->where('tenant_id', $tenantId);
    }

    // 1. Total Sales (Delivered only)
    $totalSales = (clone $orderQuery)
    ->where('payment_status', 'paid')
    ->sum('total_amount');

    // 2. Total Orders
    $totalOrders = (clone $orderQuery)->count();

    // 3. Total Users (excluding 1 admin)
    $rawUserCount = (clone $userQuery)->count();
    $displayUserCount = max($rawUserCount - 1, 0);

    if ($displayUserCount > 0) {
        $userDescription = 'Registered customers';
        $userColor = 'primary';
        $userIcon = 'heroicon-m-users';
    } else {
        $userDescription = 'Waiting for first customer';
        $userColor = 'gray';
        $userIcon = 'heroicon-m-user-minus';
    }

    return [
        // Total Sales
        Stat::make('Total Sales', $this->animateNumber($totalSales, '$'))
            ->description($totalSales > 0 ? 'Revenue is growing' : 'Awaiting first sale')
            ->descriptionIcon($totalSales > 0 ? 'heroicon-m-arrow-trending-up' : null)
            ->chart($totalSales > 0 ? [7, 3, 10, 2, 12, 4, 15] : [0, 0, 0])
            ->color($totalSales > 0 ? 'success' : 'gray')
            ->url(OrderResource::getUrl('index')),

        // Total Orders
        Stat::make('Total Orders', $this->animateNumber($totalOrders))
            ->description('Total transactions')
            ->descriptionIcon('heroicon-m-shopping-cart')
            ->chart($totalOrders > 0 ? [15, 4, 10, 2, 12, 4, 7] : [0, 0, 0])
            ->color($totalOrders > 0 ? 'info' : 'gray')
            ->url(OrderResource::getUrl('index')),

        // Customers
        Stat::make('Customers', $this->animateNumber($displayUserCount))
            ->description($userDescription)
            ->descriptionIcon($userIcon)
            ->chart($displayUserCount > 0 ? [1, 5, 2, 10, 3, 12, 8] : [0, 0, 0])
            ->color($displayUserCount > 0 ? 'primary' : 'gray')
            ->url(UserResource::getUrl('index')),
    ];
}
}