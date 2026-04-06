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
    
    $orderQuery = Order::query();
    $userQuery = User::query();

    if ($user->role === 'seller') {
        $orderQuery->whereHas('items.product', function ($q) use ($user) {
            $q->where('tenant_id', $user->tenant_id);
        });
        
        $userQuery->whereHas('orders.items.product', function ($q) use ($user) {
            $q->where('tenant_id', $user->tenant_id);
        });
    } 

    $totalSales = (clone $orderQuery)->where('payment_status', 'paid')->sum('total_amount');
    $totalOrders = (clone $orderQuery)->where('order_status', '!=', 'in_cart')->count();
    $displayUserCount = (clone $userQuery)->distinct()->count();

    return [
        // 💰 Total Sales - Success Green with Trending Up icon
        Stat::make('Total Sales', $this->animateNumber($totalSales, '$'))
            ->description($totalSales > 0 ? 'Total revenue generated' : 'Awaiting first payment')
            ->descriptionIcon($totalSales > 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-minus')
            ->chart($totalSales > 0 ? [7, 3, 10, 2, 12, 4, 18] : [0, 0, 0]) // Sparkline chart
            ->color($totalSales > 0 ? 'success' : 'gray'),

        // 📦 Total Orders - Info Blue with Shopping Cart icon
        Stat::make('Total Orders', $this->animateNumber($totalOrders))
            ->description('Transactions excluding carts')
            ->descriptionIcon('heroicon-m-shopping-bag')
            ->chart($totalOrders > 0 ? [3, 5, 2, 8, 4, 10, 7] : [0, 0, 0])
            ->color($totalOrders > 0 ? 'info' : 'gray'),

        // 👥 Customers - Primary Indigo with Users icon
        Stat::make('Unique Customers', $this->animateNumber($displayUserCount))
            ->description($displayUserCount > 0 ? 'Customer base reached' : 'Waiting for buyers')
            ->descriptionIcon('heroicon-m-users')
            ->chart($displayUserCount > 0 ? [1, 2, 4, 3, 6, 8, 12] : [0, 0, 0])
            ->color($displayUserCount > 0 ? 'primary' : 'gray'),
    ];
}
}