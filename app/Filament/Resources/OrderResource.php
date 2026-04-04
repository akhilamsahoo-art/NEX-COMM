<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Models\Order;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;
    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?string $navigationGroup = 'Shop Management';

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check() && in_array(auth()->user()->role, ['super_admin', 'manager', 'seller']);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Group::make()->schema([

                // Main Information
                Forms\Components\Section::make('Main Information')->schema([
                    Forms\Components\Select::make('user_id')
                        ->relationship('user', 'name')
                        ->label('Customer')
                        ->disabled(fn () => auth()->user()->role === 'seller')
                        ->required(),

                    Forms\Components\Textarea::make('notes')
                        ->columnSpanFull(),
                ])->columns(2),

                // Order Items
                Forms\Components\Section::make('Order Items')->schema([
                    Forms\Components\Repeater::make('items')
                        ->relationship('items')
                        ->schema([
                            Forms\Components\Placeholder::make('product_image')
                                ->label('Image')
                                ->content(function ($record) {
                                    if (!$record || !$record->product || !$record->product->image) {
                                        return 'No Image';
                                    }

                                    return new \Illuminate\Support\HtmlString(
                                        '<img src="' . asset('storage/' . $record->product->image) . '" 
                                        style="width:50px;height:50px;border-radius:8px;object-fit:cover;" />'
                                    );
                                }),

                            Forms\Components\Select::make('product_id')
                                ->relationship('product', 'name')
                                ->label('Product')
                                ->disabled(),

                            Forms\Components\TextInput::make('quantity')
                                ->numeric()
                                ->disabled(),

                            Forms\Components\TextInput::make('price')
                                ->label('Unit Price')
                                ->numeric()
                                ->prefix('$')
                                ->readOnly()
                                ->dehydrated()
                                ->afterStateHydrated(function ($component, $state, $record) {
                                    if ($record) {
                                        $component->state($record->price);
                                    }
                                }),
                        ])
                        ->columns(4)
                        ->addable(false)
                        ->deletable(false)
                        ->reorderable(false),
                ]),

                // Workflow
                Forms\Components\Section::make('Order Workflow')->schema([

                    Forms\Components\Select::make('order_status')
                        ->label('Order Status')
                        ->options([
                            'in_cart' => 'In Cart',
                            'order_placed' => 'Order Placed',
                            'cancelled' => 'Cancelled',
                        ])
                        ->required()
                        ->disabled(fn () => auth()->user()->role === 'seller')
                        ->disableOptionWhen(function ($value, $record) {
                            if (!$record) return false;

                            $stages = [
                                'in_cart' => 1,
                                'order_placed' => 2,
                                'cancelled' => 3
                            ];

                            return $stages[$value] < $stages[$record->order_status];
                        }),

                    Forms\Components\Select::make('payment_status')
                        ->label('Payment Status')
                        ->options([
                            'pending' => 'Pending',
                            'cash_on_delivery' => 'Cash on Delivery',
                            'online_payment' => 'Online Payment',
                            'card_payment' => 'Pay with Card',
                            'paid' => 'Paid',
                            'failed' => 'Failed',
                        ])
                        ->required()
                        ->disabled(fn ($record) =>
                            auth()->user()->role === 'seller'
                            || !$record
                            || !in_array($record->order_status, ['order_placed'])
                        )
                        ->disableOptionWhen(function ($value, $record) {
                            if (!$record) return false;

                            $stages = [
                                'pending' => 1,
                                'cash_on_delivery' => 2,
                                'online_payment' => 3,
                                'card_payment' => 4,
                                'paid' => 5,
                                'failed' => 6,
                            ];

                            return $stages[$value] < $stages[$record->payment_status];
                        }),

                    Forms\Components\Select::make('shipment_status')
                        ->label('Shipment Status')
                        ->options([
                            'pending' => 'Pending',
                            'processed' => 'Processed',
                            'shipped' => 'Shipped',
                            'delivered' => 'Delivered',
                            'cancelled' => 'Cancelled',
                        ])
                        ->required()
                        ->disabled(fn ($record) =>
                        auth()->check() && auth()->user()->role === 'seller'
                            || !$record
                            || !in_array($record->payment_status, ['paid', 'cash_on_delivery', 'card_payment'])
                        )
                        ->disableOptionWhen(function ($value, $record) {
                            if (!$record) return false;

                            $stages = [
                                'pending' => 1,
                                'processed' => 2,
                                'shipped' => 3,
                                'delivered' => 4,
                                'cancelled' => 5,
                            ];

                            return $stages[$value] < $stages[$record->shipment_status];
                        }),

                ]),

                // Pricing
                Forms\Components\Section::make('Pricing Summary')->schema([

                    Forms\Components\TextInput::make('total_amount')
                        ->label('Grand Total')
                        ->numeric()
                        ->readOnly()
                        ->prefix('$'),

                    Forms\Components\Placeholder::make('seller_total')
                        ->label('Your Earnings')
                        ->content(function ($record) {
                            if (auth()->check() && auth()->user()->role === 'seller') return '-';

                            return $record->items
                                ->where('product.tenant_id', auth()->user()->tenant_id)
                                ->sum(fn ($item) => $item->price * $item->quantity);
                        })
                        ->visible(fn () => auth()->check() && auth()->user()->role === 'seller'),

                ]),

            ])->columns(3)
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                Tables\Columns\TextColumn::make('id')->label('ID'),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Customer')
                    ->searchable(),

                Tables\Columns\TextColumn::make('user.email')
                    ->label('Email-Id')
                    ->searchable(),

                Tables\Columns\TextColumn::make('order_status')->badge(),
                Tables\Columns\TextColumn::make('payment_status')->badge(),
                Tables\Columns\TextColumn::make('shipment_status')->badge(),

                Tables\Columns\TextColumn::make('total_amount')->money('USD'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('M d, Y'),
            ])

            ->filters([])

            ->actions([
                Tables\Actions\EditAction::make()
                ->visible(fn () => in_array(auth()->user()->role, ['super_admin', 'manager'])),
            ])

            ->bulkActions([]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['user', 'items.product']);

        if (!auth()->check()) {
            return $query;
        }

        $user = auth()->user();

        if ($user->role === 'seller') {
            $query->whereHas('items.product', function ($q) use ($user) {
                $q->where('tenant_id', $user->tenant_id);
            });
        }

        return $query;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}