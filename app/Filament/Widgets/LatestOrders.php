<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestOrders extends BaseWidget
{
    protected static ?int $sort = 4;
    protected int | string | array $columnSpan = 1;

    protected static ?string $heading = 'Latest Orders';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                // We remove ->limit(5) so the paginator can fetch 10 or 25 if requested
                Order::query()
                ->where('tenant_id', auth()->user()->tenant_id)
                ->latest()
            )
            // 1. Set the initial view to 5 rows
            ->defaultPaginationPageOption(5)
            // 2. Allow the user to switch between 5, 10, or 25
            ->paginationPageOptions([5, 10, 25])
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('Order ID'),
                
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Customer'),
                    
                Tables\Columns\TextColumn::make('total_amount')
                    ->money('USD')
                    ->label('Amount'),

                Tables\Columns\TextColumn::make('order_status')
                    ->badge()
                    ->color(fn (string $state): string => [
                        'cart' => 'gray',
                        'placed' => 'warning',
                        'confirmed' => 'info',
                        'delivered' => 'success',
                        'cancelled' => 'danger',
                    ][$state] ?? 'gray'),
            ]);
    }
}