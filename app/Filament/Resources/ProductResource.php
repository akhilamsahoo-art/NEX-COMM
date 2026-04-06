<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\User;
use App\Models\Product;
use App\Services\AiFoundationService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Actions\Action;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Shop Management';
    protected static ?int $navigationSort = 1;

    // ✅ NEW (navigation control)
   public static function shouldRegisterNavigation(): bool
{
    $user = auth()->user();

    if (!$user) {
        return false;
    }

    return in_array($user->role, [
        User::ROLE_SUPER_ADMIN,
        User::ROLE_MANAGER,
        User::ROLE_SELLER
    ]);
}   
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Product Details')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(191)
                            ->live(true),

                        TextInput::make('price')
                            ->required()
                            ->numeric()
                            ->prefix('$'),

                        // ✅ UPDATED (tenant-safe category selection)
                        Select::make('category_id')
                            ->label('Category')
                            ->relationship(
    'category',
    'name',
    function ($query) {
        $user = auth()->user();

        if ($user && $user->role !== User::ROLE_SUPER_ADMIN) {
            $query->where('tenant_id', $user->tenant_id);
        }
    }
)
                            ->preload()
                            ->searchable()
                            ->required()
                            ->native(false),

                        TextInput::make('quantity')
                            ->label('Available Quantity')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->helperText('Number of items available in stock'),

                        TextInput::make('key_features')
                            ->label('Key Features')
                            ->placeholder('e.g. 40h battery, waterproof, fast charging')
                            ->helperText('Add key features of the product.'),

                        Textarea::make('description')
                            ->rows(6)
                            ->columnSpanFull()
                            ->hintAction(
                                Action::make('generateAiDescription')
                                    ->label('✨ AI Generate Description')
                                    ->icon('heroicon-m-sparkles')
                                    ->color('warning')
                                    ->action(function (Forms\Set $set, Forms\Get $get) {
                                        $name = $get('name');
                                        $features = $get('key_features');
                                        if (blank($name)) {
                                            Notification::make()->title('Name required')->warning()->send();
                                            return;
                                        }
                                        $aiService = app(AiFoundationService::class);
                                        try {
                                            $result = $aiService->generate('product_generator', [
                                                'name' => $name,
                                                'features' => $features ?? 'High quality premium product',
                                            ]);
                                            $set('description', $result);
                                        } catch (\Exception $e) {
                                            Notification::make()->title('AI Error')->danger()->send();
                                        }
                                    })
                            ),

                        Forms\Components\Placeholder::make('total_sold')
                            ->label('Total Units Sold')
                            ->visible(fn ($record) => $record !== null)
                            ->content(function ($record) {
                                if (!$record) return 0;

                                return $record->orderItems()
                                    ->whereHas('order', fn ($q) => $q->where('order_status', 'delivered'))
                                    ->sum('quantity');
                            }),

                        FileUpload::make('image')
                            ->label('Product Image')
                            ->image()
                            ->disk('public')
                            ->directory('products')
                            ->visibility('public')
                            ->required()
                            ->columnSpanFull(),

                    ])->columns(2),

                Section::make('AI Review Insights')
                    ->hiddenOn('create')
                    ->schema([
                        Textarea::make('ai_summary')
                            ->label('Summarized Review Content')
                            ->placeholder('AI is processing reviews in the background...')
                            ->rows(4)
                            ->columnSpanFull()
                            ->live()
                            ->readOnly()
                            ->hintAction(
                                Action::make('summarizeReviews')
                                    ->label('✨ Force Re-Summarize')
                                    ->icon('heroicon-m-arrow-path')
                                    ->color('success')
                                    ->action(function (Forms\Set $set, $record) {
                                        if (!$record) {
                                            Notification::make()->title('Save product first')->warning()->send();
                                            return;
                                        }

                                        $reviews = $record->reviews()->pluck('comment')->filter()->implode('; ');

                                        if (empty($reviews)) {
                                            Notification::make()->title('No reviews found to summarize.')->warning()->send();
                                            return;
                                        }

                                        $aiService = app(AiFoundationService::class);
                                        try {
                                            $summary = $aiService->generate('review_summarizer', ['reviews' => $reviews]);
                                            $set('ai_summary', $summary);
                                            $record->update(['ai_summary' => $summary]);
                                            Notification::make()->title('Summary Refreshed!')->success()->send();
                                        } catch (\Exception $e) {
                                            Notification::make()->title('AI Error')->danger()->send();
                                        }
                                    })
                            ),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
{
    return $table
        ->columns([
            TextColumn::make('row_index')->label('#')->rowIndex(),

            ImageColumn::make('image')
                ->label('Thumbnail')
                ->circular()
                ->disk('public')
                ->visibility('public')
                ->checkFileExistence(false),

            TextColumn::make('name')
                ->searchable(),
                // ->sortable(),

            TextColumn::make('price')
                ->money('USD'),
                // ->sortable(),

            TextColumn::make('category.name')
                ->label('Category')
                ->badge(),
                // ->sortable(),

            // ✅ FIXED
        //    TextColumn::make('seller.name')
        //         ->label('Seller')
        //         ->searchable()
        //         ->sortable()
        //         ->visible(fn () => auth()->user()->role === 'admin'),
        TextColumn::make('seller.name')
    ->label('Seller')
    ->searchable()
    // ->sortable()
    ->visible(function () {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        return $user && $user->isSuperAdmin();
    }),

            TextColumn::make('created_at')
                ->dateTime()
                // ->sortable()
                ->toggleable(true, true),
        ])

        ->defaultPaginationPageOption(10)
        ->paginated([5, 10, 25, 50, 100, 'all'])

        ->filters([
            //
        ])

        ->actions([
            Tables\Actions\Action::make('viewReviews')
                ->label('Reviews')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->color('info')
                ->modalHeading(fn (Product $record) => "Reviews for " . $record->name)
                ->modalWidth('2xl')
                ->modalSubmitAction(false)
                ->modalContent(fn (Product $record) => view(
                    'filament.components.product-reviews-modal',
                    ['reviews' => $record->reviews()->with('user:id,name')->latest()->get()]
                )),

            Tables\Actions\EditAction::make(),

            // ✅ FIXED
           Tables\Actions\DeleteAction::make()
    ->visible(fn () => in_array(auth()->user()->role, [
        User::ROLE_SUPER_ADMIN,
        User::ROLE_MANAGER
    ]))
        ])

        ->bulkActions([
            Tables\Actions\BulkActionGroup::make([
               Tables\Actions\DeleteBulkAction::make()
    ->visible(fn () => in_array(auth()->user()->role, [
        User::ROLE_SUPER_ADMIN,
        User::ROLE_MANAGER
    ]))
            ]),
        ]);
}

    // ✅ FINAL TENANT FILTER (clean + safe)
    public static function getEloquentQuery(): Builder
{
    $query = parent::getEloquentQuery()->with(['category']);

    if (!auth()->check()) {
        return $query;
    }

    /** @var \App\Models\User $user */
    $user = auth()->user();

    // ✅ FIXED: return query, not boolean
    if ($user->isSuperAdmin()) {
        return $query;
    }

    return $query->where('tenant_id', $user->tenant_id);
}

    // ✅ AUTO TENANT ASSIGN
    public static function mutateFormDataBeforeCreate(array $data): array
    {
        if (auth()->check()) {
            $data['tenant_id'] = auth()->user()->tenant_id;
        }

        return $data;
    }

    public static function mutateFormDataBeforeSave(array $data): array
{
    if (!auth()->check()) {
        return $data;
    }

    /** @var \App\Models\User $user */
    $user = auth()->user();

    if (!$user->isSuperAdmin()) {
        $data['tenant_id'] = $user->tenant_id;
    }

    return $data;
}

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'view' => Pages\ViewProduct::route('/{record}'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}