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

// protected function getStats(): array
// {
//     $user = auth()->user();
    
//     $orderQuery = Order::query();
//     $userQuery = User::query();

//     if ($user->role === 'seller') {
//         $orderQuery->whereHas('items.product', function ($q) use ($user) {
//             $q->where('tenant_id', $user->tenant_id);
//         });
        
//         $userQuery->whereHas('orders.items.product', function ($q) use ($user) {
//             $q->where('tenant_id', $user->tenant_id);
//         });
//     } 

//     $totalSales = (clone $orderQuery)->where('payment_status', 'paid')->sum('total_amount');
//     $totalOrders = (clone $orderQuery)->where('order_status', '!=', 'in_cart')->count();
//     $displayUserCount = (clone $userQuery)->distinct()->count();

//     return [
//         // 💰 Total Sales - Success Green with Trending Up icon
//         Stat::make('Total Sales', $this->animateNumber($totalSales, '$'))
//             ->description($totalSales > 0 ? 'Total revenue generated' : 'Awaiting first payment')
//             ->descriptionIcon($totalSales > 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-minus')
//             ->chart($totalSales > 0 ? [7, 3, 10, 2, 12, 4, 18] : [0, 0, 0]) // Sparkline chart
//             ->color($totalSales > 0 ? 'success' : 'gray'),

//         // 📦 Total Orders - Info Blue with Shopping Cart icon
//         Stat::make('Total Orders', $this->animateNumber($totalOrders))
//             ->description('Transactions excluding carts')
//             ->descriptionIcon('heroicon-m-shopping-bag')
//             ->chart($totalOrders > 0 ? [3, 5, 2, 8, 4, 10, 7] : [0, 0, 0])
//             ->color($totalOrders > 0 ? 'info' : 'gray'),

//         // 👥 Customers - Primary Indigo with Users icon
//         Stat::make('Unique Customers', $this->animateNumber($displayUserCount))
//             ->description($displayUserCount > 0 ? 'Customer base reached' : 'Waiting for buyers')
//             ->descriptionIcon('heroicon-m-users')
//             ->chart($displayUserCount > 0 ? [1, 2, 4, 3, 6, 8, 12] : [0, 0, 0])
//             ->color($displayUserCount > 0 ? 'primary' : 'gray'),
//     ];
// }
protected function getStats(): array
{
    $user = auth()->user();
    
    $orderQuery = Order::query();
    $userQuery = User::query();

    // 1. Logic for Managers and Sellers (Tenant Scoped)
    if (in_array($user->role, ['manager', 'seller'])) {
        
        // Scope orders to products belonging to the user's tenant
        $orderQuery->whereHas('items.product', function ($q) use ($user) {
            $q->where('tenant_id', $user->tenant_id);
        });
        
        // Scope unique customers to those who have ordered from this tenant
        $userQuery->whereHas('orders.items.product', function ($q) use ($user) {
            $q->where('tenant_id', $user->tenant_id);
        });

        // Additional constraint for Sellers only: hide "In Cart" from the Order Count
        if ($user->role === 'seller') {
            $orderQuery->where('order_status', '!=', 'in_cart');
        }
    } 
    
    // 2. Logic for Super Admin (Global)
    // If the role is 'super_admin', we don't apply any 'whereHas' or 'tenant_id' filters.

    // 3. Calculate Totals based on the filtered queries
    // Total Sales: Sum of paid orders within the scope
    $totalSales = (clone $orderQuery)->where('payment_status', 'paid')->sum('total_amount');
    
    // Total Orders: Count of actual orders (excluding 'in_cart' for everyone or just sellers based on your preference)
    $totalOrders = (clone $orderQuery)->where('order_status', '!=', 'in_cart')->count();
    
    // Unique Customers: Count of distinct users within the scope
    $displayUserCount = (clone $userQuery)->distinct()->count();

    return [
        // 💰 Total Sales
        Stat::make('Total Sales', $this->animateNumber($totalSales, '$'))
            ->description($totalSales > 0 ? 'Total revenue generated' : 'Awaiting first payment')
            ->descriptionIcon($totalSales > 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-minus')
            ->chart($totalSales > 0 ? [7, 3, 10, 2, 12, 4, 18] : [0, 0, 0])
            ->color($totalSales > 0 ? 'success' : 'gray'),

        // 📦 Total Orders
        Stat::make('Total Orders', $this->animateNumber($totalOrders))
            ->description('Transactions excluding carts')
            ->descriptionIcon('heroicon-m-shopping-bag')
            ->chart($totalOrders > 0 ? [3, 5, 2, 8, 4, 10, 7] : [0, 0, 0])
            ->color($totalOrders > 0 ? 'info' : 'gray'),

        // 👥 Customers
        Stat::make('Unique Customers', $this->animateNumber($displayUserCount))
            ->description($displayUserCount > 0 ? 'Customer base reached' : 'Waiting for buyers')
            ->descriptionIcon('heroicon-m-users')
            ->chart($displayUserCount > 0 ? [1, 2, 4, 3, 6, 8, 12] : [0, 0, 0])
            ->color($displayUserCount > 0 ? 'primary' : 'gray'),
    ];
}
}