<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Models\User;
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
        
        // Use withoutGlobalScopes to ensure Admin/Manager can see across the "Tenant Wall"
        $orderQuery = Order::query()->withoutGlobalScopes();
        $customerQuery = User::query()->where('role', 'customer')->withoutGlobalScopes();

        // 1. ADMIN LOGIC: Count all customers in the database
        // if ($user->role === 'super_admin') {
        //     // No filters applied - sees total database count
        // } 
        if ($user->role === 'super_admin') {
    $customerQuery = User::withoutGlobalScopes()
        ->where('role', 'customer');
}

        // 2. MANAGER LOGIC: Count only customers who ordered from their sellers
        elseif ($user->role === 'manager') {
            $managedSellerIds = User::where('manager_id', $user->id)->pluck('id')->toArray();

            $orderQuery->whereHas('items.product', function ($q) use ($managedSellerIds) {
                $q->whereIn('user_id', $managedSellerIds);
            });

            $customerQuery->whereHas('orders.items.product', function ($q) use ($managedSellerIds) {
                $q->whereIn('user_id', $managedSellerIds);
            });
        }

        // 3. SELLER LOGIC: Count only their own specific customers
        elseif ($user->role === 'seller') {
            $orderQuery->whereHas('items.product', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });

            $customerQuery->whereHas('orders.items.product', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }

        // --- Data Calculations ---
        $totalSales = (clone $orderQuery)->where('payment_status', 'paid')->sum('total_amount');
        $totalOrders = (clone $orderQuery)->where('order_status', '!=', 'in_cart')->count();
        $displayUserCount = $customerQuery->distinct()->count();

        // --- Old School Description Logic ---
        $custDescription = 'Total registered customers';
        if ($user->role === 'manager') {
            $custDescription = 'Customers of your sellers';
        } elseif ($user->role === 'seller') {
            $custDescription = 'Customers who bought from you';
        }

        return [
            // 💰 Total Sales Stat
            Stat::make('Total Sales', $this->animateNumber($totalSales, '$'))
                ->description($totalSales > 0 ? 'Total revenue generated' : 'Awaiting first payment')
                ->descriptionIcon($totalSales > 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-minus')
                ->chart($totalSales > 0 ? [7, 3, 10, 2, 12, 4, 18] : [0, 0, 0])
                ->color($totalSales > 0 ? 'success' : 'gray'),

            // 📦 Total Orders Stat
            Stat::make('Total Orders', $this->animateNumber($totalOrders))
                ->description('Transactions excluding carts')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->chart($totalOrders > 0 ? [3, 5, 2, 8, 4, 10, 7] : [0, 0, 0])
                ->color($totalOrders > 0 ? 'info' : 'gray'),

            // 👥 Unique Customers Stat
            Stat::make('Unique Customers', $this->animateNumber($displayUserCount))
                ->description($custDescription)
                ->descriptionIcon('heroicon-m-users')
                ->chart($displayUserCount > 0 ? [1, 2, 4, 3, 6, 8, 12] : [0, 0, 0])
                ->color($displayUserCount > 0 ? 'primary' : 'gray'),
        ];
    }
}