<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class TopCustomersTable extends BaseWidget
{
    protected static ?int $sort = 5;
    // Fits into your 3-column dashboard grid
    protected int | string | array $columnSpan = 1;

    protected static ?string $heading = 'Repeat Customers & Loyal Fans';

    public function table(Table $table): Table
{
    return $table
        ->query(
            \App\Models\User::query()
                // FIX: We do the heavy lifting HERE in one query, not row-by-row
                ->withCount(['orders as delivered_orders_count' => function ($query) {
                    $query->where('order_status', 'delivered');
                }])
                ->withSum(['orders as delivered_total_spend' => function ($query) {
                    $query->where('order_status', 'delivered');
                }], 'total_amount')
                // Only show users who actually have delivered orders
                ->has('orders', '>', 0, 'and', function ($query) {
                    $query->where('order_status', 'delivered');
                })
                ->orderBy('delivered_orders_count', 'desc')
        )
        ->columns([
            Tables\Columns\TextColumn::make('name')
                ->label('Loyal Customer')
                // Using the ALIAS we created in the query (delivered_orders_count)
                ->description(fn ($record): string => 
                    $record->delivered_orders_count >= 5 ? '⭐ VIP: Trusted Buyer' : 'Repeat Buyer'
                ),

            Tables\Columns\TextColumn::make('delivered_orders_count')
                ->label('Deliveries')
                ->badge()
                ->color(fn (string $state): string => [
                    '1' => 'gray',
                    '2' => 'gray',
                    '3' => 'info',
                ][$state] ?? 'success'),

            Tables\Columns\TextColumn::make('delivered_total_spend')
                ->label('Total Value')
                ->money('USD')
                ->sortable(),
        ]);
}
}