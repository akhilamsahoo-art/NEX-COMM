<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class TopCustomers extends BaseWidget
{
    protected static ?int $sort = 6;
    public function table(Table $table): Table
    {
        return $table
            ->query(
                \App\Models\User::query()->withCount('orders')->has('orders')->orderBy('orders_count', 'desc')->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Customer'),
                Tables\Columns\TextColumn::make('orders_count')->label('Total Orders'),
                Tables\Columns\TextColumn::make('email')->icon('heroicon-m-envelope'),
            ]);
    }
}
