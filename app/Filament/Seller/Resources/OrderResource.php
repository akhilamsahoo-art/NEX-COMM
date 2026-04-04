<?php

namespace App\Filament\Seller\Resources;

use App\Filament\Seller\Resources\OrderResource\Pages;
use App\Filament\Seller\Resources\OrderResource\RelationManagers;
use App\Models\Order;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Illuminate\Database\Eloquent\Builder;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';
    protected static ?string $navigationLabel = 'Orders';
    protected static ?string $pluralModelLabel = 'Orders';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('Order ID')->sortable(),

                TextColumn::make('user.name')
                    ->label('Customer'),

                // 🔥 Show ONLY seller's total (important fix)
                TextColumn::make('seller_total')
                    ->label('Your Earnings')
                    ->money('INR')
                    ->getStateUsing(function ($record) {
                        return $record->items
                            ->where('product.tenant_id', auth()->user()->tenant_id)
                            ->sum(fn ($item) => $item->price * $item->quantity);
                    }),

                BadgeColumn::make('order_status')
                    ->colors([
                        'primary' => 'placed',
                        'danger' => 'cancelled',
                    ])
                    ->label('Order'),

                BadgeColumn::make('payment_status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'paid',
                        'danger' => 'failed',
                    ])
                    ->label('Payment'),

                BadgeColumn::make('shipment_status')
                    ->colors([
                        'warning' => 'processing',
                        'info' => 'shipped',
                        'success' => 'delivered',
                    ])
                    ->label('Shipment'),

                TextColumn::make('created_at')
                    ->dateTime(),
            ])

            ->filters([
                Tables\Filters\SelectFilter::make('payment_status')->options([
                    'pending' => 'Pending',
                    'paid' => 'Paid',
                    'failed' => 'Failed',
                ]),

                Tables\Filters\SelectFilter::make('shipment_status')->options([
                    'processing' => 'Processing',
                    'shipped' => 'Shipped',
                    'delivered' => 'Delivered',
                ]),
            ])

            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\Action::make('mark_paid')
                    ->label('Mark Paid')
                    ->color('success')
                    ->visible(fn ($record) => $record->payment_status === 'pending')
                    ->action(fn ($record) => $record->update([
                        'payment_status' => 'paid',
                    ])),

                Tables\Actions\Action::make('ship')
                    ->label('Ship')
                    ->color('info')
                    ->visible(fn ($record) => $record->shipment_status === 'processing')
                    ->action(fn ($record) => $record->update([
                        'shipment_status' => 'shipped',
                    ])),

                Tables\Actions\Action::make('deliver')
                    ->label('Deliver')
                    ->color('success')
                    ->visible(fn ($record) => $record->shipment_status === 'shipped')
                    ->action(fn ($record) => $record->update([
                        'shipment_status' => 'delivered',
                    ])),
            ])

            ->defaultSort('created_at', 'desc');
    }

    // 🔥 FIXED TENANT FILTER
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('items.product', function ($query) {
                $query->where('tenant_id', auth()->user()->tenant_id);
            })
            ->with(['items.product']); // ⚡ Prevent N+1 issue
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'view' => Pages\ViewOrder::route('/{record}'),
        ];
    }
}