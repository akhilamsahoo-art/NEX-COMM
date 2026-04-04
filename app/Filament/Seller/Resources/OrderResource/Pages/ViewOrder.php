<?php

namespace App\Filament\Seller\Resources\OrderResource\Pages;

use App\Filament\Seller\Resources\OrderResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([

                // 🔹 ORDER OVERVIEW
                Section::make('Order Overview')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('id')->label('Order ID'),
                        TextEntry::make('user.name')->label('Customer'),
                        TextEntry::make('total_price')->money('USD'),

                        TextEntry::make('order_status')
                            ->badge()
                            ->color(function ($state) {
                                if ($state === 'cart') return 'gray';
                                if ($state === 'placed') return 'warning';
                                if ($state === 'confirmed') return 'info';
                                if ($state === 'delivered') return 'success';
                                return 'gray';
                            }),

                        TextEntry::make('created_at')->dateTime(),
                    ]),

                // 🔹 PAYMENT INFO
                Section::make('Payment Info')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('payment_method')->label('Method'),

                        TextEntry::make('payment_status')
                            ->badge()
                            ->color(function ($state) {
                                if ($state === 'pending') return 'warning';
                                if ($state === 'paid') return 'success';
                                if ($state === 'failed') return 'danger';
                                return 'gray';
                            }),

                        TextEntry::make('paid_at')
                            ->dateTime()
                            ->placeholder('Not Paid'),
                    ]),

                // 🔹 SHIPMENT INFO
                Section::make('Shipment Info')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('shipment_status')
                            ->badge()
                            ->color(function ($state) {
                                if ($state === 'pending') return 'gray';
                                if ($state === 'processing') return 'warning';
                                if ($state === 'shipped') return 'info';
                                if ($state === 'delivered') return 'success';
                                return 'gray';
                            }),

                        TextEntry::make('shipped_at')
                            ->dateTime()
                            ->placeholder('Not Shipped'),
                    ]),
                    

                // 🔥 ORDER TIMELINE
                Section::make('Order Timeline')
                    ->schema([
                        TextEntry::make('timeline')
                            ->label('')
                            ->formatStateUsing(function ($record) {
                                return "🛒 Cart → 📦 Placed → 💳 " . strtoupper($record->payment_status)
                                    . " → 🚚 " . strtoupper($record->shipment_status)
                                    . " → ✅ " . strtoupper($record->order_status);
                            }),
                    ]),

                // 🔹 CUSTOMER INFO
                Section::make('Customer Info')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('user.name')->label('Name'),
                        TextEntry::make('user.email')->label('Email'),
                    ]),
            ]);
    }
    protected function getHeaderActions(): array
{
    return [
        \Filament\Actions\Action::make('markPaid')
            ->label('Mark as Paid')
            ->visible(fn ($record) => $record->payment_status !== 'paid')
            ->action(fn ($record) => $record->update([
                'payment_status' => 'paid',
                'paid_at' => now(),
            ])),

        \Filament\Actions\Action::make('markShipped')
            ->label('Mark as Shipped')
            ->visible(fn ($record) => $record->shipment_status !== 'shipped')
            ->action(fn ($record) => $record->update([
                'shipment_status' => 'shipped',
                'shipped_at' => now(),
            ])),
    ];
}
}