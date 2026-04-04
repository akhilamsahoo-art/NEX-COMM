<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use App\Filament\Resources\ProductResource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class PopularProductsTable extends BaseWidget
{
    protected static ?int $sort = 3;
    protected int|string|array $columnSpan = 1;
    protected static ?string $heading = 'Popular Products';
    protected static bool $isLazy = true; 

    public function table(Table $table): Table
    {
        return $table
            ->query(
                // Default query: get products with total_sold & average rating
                Product::query()
                    ->where('tenant_id', auth()->user()->tenant_id)
                    ->select(['products.id', 'products.name', 'products.price', 'products.image'])
                    ->withSum(['orderItems as total_sold' => function ($query) {
                        $query->whereHas('order', function ($q) {
                            $q->where('order_status', 'delivered');
                        });
                    }], 'quantity')
                    ->withAvg('reviews', 'rating')
                    // ->orderByDesc('total_sold')
                    ->orderByRaw('COALESCE(total_sold, 0) DESC')
                    ->having('total_sold', '>', 0)
                    // ->orderByDesc('reviews_avg_rating') 
                    ->limit(5)
            )
            ->recordUrl(fn (Product $record): string => 
                ProductResource::getUrl('view', ['record' => $record])
            )
            ->paginated(false)
            ->emptyStateHeading('No products match these criteria')
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->disk('public')
                    ->checkFileExistence(false) 
                    ->circular()
                    ->label(''),

                Tables\Columns\TextColumn::make('name')
                    ->label('Product')
                    ->searchable(),

                Tables\Columns\TextColumn::make('total_sold')
                    ->label('Units Sold')
                    ->badge()
                    ->default(0)
                    ->getStateUsing(fn ($record) => $record->total_sold ?? 0)
                    ->color(fn ($state) => $state > 0 ? 'success' : 'gray'),

                Tables\Columns\TextColumn::make('reviews_avg_rating')
                    ->label('Rating')
                    ->numeric(1)
                    ->icon('heroicon-m-star')
                    ->color('warning')
                    ->placeholder('0.0'),

                Tables\Columns\TextColumn::make('price')
                    ->label('Price')
                    ->money('USD'),
            ])
            ->filters([
                SelectFilter::make('sort_by')
                    ->label('Filter By')
                    ->options([
                        'most_sold' => 'Most Sold (Delivered)',
                        'good_reviews' => 'Highest Rated',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $value = $data['value'] ?? null;

                        switch ($value) {
                            case 'most_sold':
                                return $query
                                    ->whereHas('orders', function ($q) {
                                        $q->where('orders.order_status', 'delivered');
                                    })
                                    ->orderByDesc('total_sold')
                                    ->limit(5);
                            
                            case 'good_reviews':
                                return $query
                                    ->whereHas('reviews')
                                    ->orderByDesc('reviews_avg_rating')
                                    ->limit(5);

                            default:
                                return $query
                                    ->orderByDesc('reviews_avg_rating')
                                    ->limit(5);
                        }
                    }),
            ]);
    }
}