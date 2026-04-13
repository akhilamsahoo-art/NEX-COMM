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
use Illuminate\Support\Facades\DB;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Actions\Action;

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
            // This main grid splits the screen into a 2-column wide left side and 1-column wide right side
            Forms\Components\Grid::make(3)
                ->schema([
                    
                    // LEFT SIDE: Main info and items (Spans 2 columns)
                    Forms\Components\Group::make()
                        ->schema([
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
                        ])
                        ->columnSpan(['lg' => 2]),

                    // RIGHT SIDE: Workflow and Totals (Spans 1 column)
                    Forms\Components\Group::make()
                        ->schema([
                            // Workflow
                            Forms\Components\Section::make('Order Workflow')->schema([// ---------------- ORDER STATUS ----------------
    Forms\Components\Select::make('order_status')
        ->label('Order Status')
        ->options([
            'in_cart' => 'In Cart',
            'placed' => 'Order Placed',
            'cancelled' => 'Cancelled',
        ])
        ->visible(fn () => auth()->user()->role === 'seller')
        ->required()
        ->live() // 👈 Added: Allows other fields to react instantly
        ->disableOptionWhen(function ($value, $record) {
            if (!$record) return false;
            $stages = ['in_cart' => 1, 'placed' => 2, 'cancelled' => 3];
            return $stages[$value] < $stages[$record->order_status];
        }),

    Forms\Components\TextInput::make('order_status')
        ->label('Order Status')
        ->disabled()
        ->visible(fn () => auth()->user()->role !== 'seller'),

    // ---------------- PAYMENT STATUS ----------------
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
        ->visible(fn () => auth()->user()->role === 'seller')
        ->required()
        ->live() // 👈 Added: Makes the UI responsive
        ->disableOptionWhen(function ($value, $record) {
            if (!$record) return false;
            $stages = ['pending' => 1, 'cash_on_delivery' => 2, 'online_payment' => 3, 'card_payment' => 4, 'paid' => 5, 'failed' => 6];
            return $stages[$value] < $stages[$record->payment_status];
        }),

    Forms\Components\TextInput::make('payment_status')
        ->label('Payment Status')
        ->disabled()
        ->visible(fn () => auth()->user()->role !== 'seller'),

    // ---------------- SHIPMENT STATUS ----------------
    Forms\Components\Select::make('shipment_status')
        ->label('Shipment Status')
        ->options([
            'pending' => 'Pending',
            'processing' => 'Processing',
            'shipped' => 'Shipped',
            'delivered' => 'Delivered',
            'cancelled' => 'Cancelled',
        ])
        ->visible(fn () => auth()->user()->role === 'seller')
        ->required()
        ->disabled(false) // ✅ Correct: Always enabled
        ->dehydrated()
        ->live() // 👈 Added: Triggers immediate updates
        ->disableOptionWhen(function ($value, $record) {
            if (!$record) return false;
            $stages = ['pending' => 1, 'processing' => 2, 'shipped' => 3, 'delivered' => 4, 'cancelled' => 5];
            return $stages[$value] < $stages[$record->shipment_status];
        })
        ->afterStateUpdated(function ($state, callable $set) {
            // 🚀 Logic: If shipment is marked 'delivered', 
            // automatically update payment to 'paid'.
            if ($state === 'delivered') {
                $set('payment_status', 'paid');
            }
        }),

    Forms\Components\TextInput::make('shipment_status')
        ->label('Shipment Status')
        ->disabled()
        ->visible(fn () => auth()->user()->role !== 'seller'),]),

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
                                        if (auth()->check() && auth()->user()->role === 'seller') {
                                            $total = $record->items
                                                ->where('product.tenant_id', auth()->user()->tenant_id)
                                                ->sum(fn ($item) => $item->price * $item->quantity);
                                            return '$' . number_format($total, 2);
                                        }
                                        return '-';
                                    })
                                    ->visible(fn () => auth()->check() && auth()->user()->role === 'seller'),
                            ]),
                        ])
                        ->columnSpan(['lg' => 1]),
                ]),
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

            ->filters([
            // ✅ This adds the dropdown inside the filter/search area
            Tables\Filters\SelectFilter::make('order_status')
                ->label('Order Status')
                ->options([
                    'in_cart' => 'In Cart',
                    'placed' => 'Placed',
                    'processing' => 'Processing',
                    'shipped' => 'Shipped',
                    'delivered' => 'Delivered',
                    'cancelled' => 'Cancelled',
                ]),
            
            // Optional: You can also add a filter for Shipment or Payment status here
        ])
        ->filtersLayout(FiltersLayout::Dropdown)
        ->filtersTriggerAction(
            fn (Action $action) => $action
                ->button()
                ->label('Filter'),
        
        )
        ->defaultSort('created_at', 'desc')
        ->actions([
            Tables\Actions\ViewAction::make()
                ->visible(fn () => in_array(auth()->user()->role, ['super_admin', 'manager'])),
            Tables\Actions\EditAction::make()
                ->visible(fn () => auth()->user()->role === 'seller'),
        ])
        ->bulkActions([])

//             ->actions([
//     Tables\Actions\ViewAction::make()
//         ->visible(fn () => in_array(auth()->user()->role, ['super_admin', 'manager'])),

//     Tables\Actions\EditAction::make()
//         ->visible(fn () => auth()->user()->role === 'seller'),
// ])

            ->bulkActions([]);
    }


// public static function getEloquentQuery(): Builder
// {
//     $query = parent::getEloquentQuery()
//         ->with(['user', 'items.product']);

//     if (!auth()->check()) {
//         return $query->whereRaw('1 = 0');
//     }

//     $user = auth()->user();

//     if ($user->role === 'super_admin') {
//         return $query;
//     }

//     if ($user->role === 'manager') {
//         return $query->whereHas('items.product', function ($q) use ($user) {
//             $q->where('tenant_id', $user->tenant_id);
//         });
//     }

//     if ($user->role === 'seller') {

//     $query = $query->where('order_status', '!=', 'in_cart');

//     $query = $query->whereExists(function ($subQuery) use ($user) {

//         $subQuery->select(DB::raw(1))
//             ->from('order_items')
//             ->join('products', function ($join) {
//                 $join->on('products.id', '=', 'order_items.product_id');
//             })
//             ->whereColumn('order_items.order_id', 'orders.id')
//             ->where('products.tenant_id', '=', $user->tenant_id);
//     });

//     return $query;
// }

//     return $query->whereRaw('1 = 0');
// }

public static function getEloquentQuery(): Builder
{
    // 1. Start the query with necessary relationships
    $query = parent::getEloquentQuery()
        ->with(['user', 'items.product']);

    if (!auth()->check()) {
        return $query->whereRaw('1 = 0');
    }

    /** @var \App\Models\User $user */
    $user = auth()->user();

    // 2. SUPER ADMIN: Full Access
    if ($user->role === 'super_admin') {
        return $query;
    }

    // 3. MANAGER: Sees orders for their own tenant + managed sellers
    if ($user->role === 'manager') {
        return $query->where(function (Builder $sub) use ($user) {
            $sub->where('orders.tenant_id', $user->tenant_id)
                ->orWhereIn('orders.tenant_id', function ($q) use ($user) {
                    $q->select('tenant_id')
                      ->from('users')
                      ->where('manager_id', $user->id);
                });
        });
    }

    // 4. SELLER: Sees only completed/placed orders belonging to their specific tenant
    if ($user->role === 'seller') {
        // We keep your logic: Sellers should NEVER see "in_cart" orders (abandoned/active carts)
        $query->where('order_status', '!=', 'in_cart');

        // Robust check: Ensure the order actually belongs to the seller's tenant
        // We use the direct column for speed, but whereExists as a fallback/verification
        $query->where(function (Builder $sub) use ($user) {
            $sub->where('orders.tenant_id', $user->tenant_id)
                ->orWhereExists(function ($subQuery) use ($user) {
                    $subQuery->select(DB::raw(1))
                        ->from('order_items')
                        ->join('products', 'products.id', '=', 'order_items.product_id')
                        ->whereColumn('order_items.order_id', 'orders.id')
                        ->where('products.tenant_id', $user->tenant_id);
                });
        });

        return $query;
    }

    // Default: Return nothing if role doesn't match
    return $query->whereRaw('1 = 0');
}
    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'view' => Pages\ViewOrder::route('/{record}'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}