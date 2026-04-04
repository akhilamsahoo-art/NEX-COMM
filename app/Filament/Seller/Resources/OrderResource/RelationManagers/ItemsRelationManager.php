<?php

namespace App\Filament\Seller\Resources\OrderResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';
    protected static ?string $recordTitleAttribute = 'product.name';

    // For Orders, we usually don't allow editing products here
    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('product.name')
                ->label('Product')
                ->disabled(), // readonly, can't change product in existing order

            Forms\Components\TextInput::make('quantity')
                ->label('Quantity')
                ->disabled(),

            Forms\Components\TextInput::make('price')
                ->label('Price')
                ->money('USD')
                ->disabled(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                // Only show items where product belongs to this seller
                $query->whereHas('product', function ($q) {
                    $q->where('tenant_id', auth()->user()->tenant_id);
                });
            })
            ->columns([
                Tables\Columns\TextColumn::make('product.name')
                    ->label('Product'),

                Tables\Columns\TextColumn::make('quantity')
                    ->label('Quantity'),

                Tables\Columns\TextColumn::make('price')
                    ->label('Price')
                    ->money('USD'),
            ])
            ->filters([
                //
            ])
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }
}