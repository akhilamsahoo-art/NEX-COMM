<?php

namespace App\Filament\Resources\CartResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    // ❌ Disable form (no manual editing)
    public function form(\Filament\Forms\Form $form): \Filament\Forms\Form
    {
        return $form->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([

                // 📦 Product Name (from relation)
                Tables\Columns\TextColumn::make('product.name')
                    ->label('Product')
                    ->searchable(),

                // 🔢 Quantity
                Tables\Columns\TextColumn::make('quantity')
                    ->sortable(),

                // 💰 Price
                Tables\Columns\TextColumn::make('product.price')
                    ->label('Price')
                    ->prefix('$'),

                // 💰 Total per item
                Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->getStateUsing(fn ($record) =>
                        $record->quantity * $record->product->price
                    )
                    ->prefix('$'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Added On')
                    ->since()
                    ->dateTime('d M Y, h:i A')  // nice format
                    ->sortable(),
                    
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->getStateUsing(function ($record) {
                
                        $lastActivity = $record->updated_at ?? $record->created_at;
                
                        if ($lastActivity->lt(now()->subDays(8))) {
                            return 'Abandoned';
                        }
                
                        return 'InCart';
                    })
                    ->colors([
                        'warning' => 'InCart',
                        'danger' => 'Abandoned',
                    ]),
            ])

            ->filters([])

            // ❌ Disable create button
            ->headerActions([])

            // ❌ Disable row actions
            ->actions([])

            ->bulkActions([]);
    }
}