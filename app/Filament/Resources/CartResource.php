<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CartResource\Pages;
use App\Models\Cart;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CartResource extends Resource
{
    protected static ?string $model = Cart::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?string $navigationLabel = 'Cart Monitor';

    protected static ?string $pluralModelLabel = 'Carts';


    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    
    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                // 👤 User Name
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Employee')
                    ->searchable()
                    ->sortable(),

                // 📦 Total Items
                Tables\Columns\TextColumn::make('items_count')
                    ->counts('items')
                    ->label('Items'),

                // 💰 Total Price (requires accessor in model)
                Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->prefix('$')
                    ->sortable(),

                // 🕒 Created Time
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable(),

                // ⏱️ Time Ago
                Tables\Columns\TextColumn::make('last_activity')
                    ->label('Time Ago')
                    ->since()
                    ->sortable(),

                // 🚦 Status (Pending / Abandoned)
                Tables\Columns\BadgeColumn::make('status')
    ->label('Status')
    ->formatStateUsing(function ($state) {
        if ($state === 'Pending') {
            return 'In Cart'; // ✅ change label
        }
        return $state;
    })
    ->colors([
        'warning' => 'Pending',
        'danger' => 'Abandoned',
    ]),

            ])

            // ->filters([

            //     // 🔴 Abandoned Filter
            //     Tables\Filters\Filter::make('abandoned')
            //         ->query(fn (Builder $query) =>
            //             $query->where('created_at', '<', now()->subHours(24))
            //         ),

            // ])

            ->actions([
                Tables\Actions\ViewAction::make(),

                // ❌ Disable edit (we don’t edit carts manually)
                // Tables\Actions\EditAction::make(),
            ])

            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    // ✅ Only show carts not checked out
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
    
        if (!auth()->check()) {
            return $query; 
        }
    
        $user = auth()->user();
    
    //     if ($user->role === 'super_admin') {
    //         return $query;
    //     }
    
    //     return $query->where('tenant_id', $user->tenant_id);
    // }
    if ($user->role === User::ROLE_SUPER_ADMIN) {
    return $query;
}

return $query->where('tenant_id', $user->tenant_id);
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Resources\CartResource\RelationManagers\ItemsRelationManager::class,
        ];
    }
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCarts::route('/'),
            // ❌ Disable create (cart auto-created)
            // 'create' => Pages\CreateCart::route('/create'),
            'view' => Pages\ViewCart::route('/{record}'),
            'edit' => Pages\EditCart::route('/{record}/edit'),
        ];
    }
}