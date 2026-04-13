<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
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

    // ✅ Navigation control
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
        return $form->schema([
            Section::make('Product Details')->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(191)
                    ->live(true),

                TextInput::make('price')
                    ->required()
                    ->numeric()
                    ->prefix('$'),

                // ✅ Tenant-safe category selection
                // Select::make('category_id')
                //     ->label('Category')
                //     ->relationship('category', 'name', function ($query) {
                //         $user = auth()->user();
                //         if ($user && $user->role !== User::ROLE_SUPER_ADMIN) {
                //             $query->where('tenant_id', $user->tenant_id);
                //         }
                //     })
                //     ->preload()
                //     ->searchable()
                //     ->required()
                //     ->native(false),
                Select::make('category_id')
    ->label('Category')
    ->relationship('category', 'name', function ($query) {
        $user = auth()->user();

        // 1. Super Admins see everything
        if ($user->role === User::ROLE_SUPER_ADMIN) {
            return $query;
        }

        /** @var \App\Models\User $user */
        return $query->withoutGlobalScopes() // 👈 IMPORTANT: Bypass the tenant wall
            ->where(function ($q) use ($user) {
                // Rules for what the user can see:
                $q->where('tenant_id', $user->tenant_id) // 1. Their own categories
                  ->orWhereNull('tenant_id');            // 2. Global categories (tenant_id is null)
                
                // 3. If they are a Seller, let them see their Manager's categories
                if ($user->role === 'seller' && $user->manager_id) {
                    $manager = \App\Models\User::find($user->manager_id);
                    if ($manager) {
                        $q->orWhere('tenant_id', $manager->tenant_id);
                    }
                }

                // 4. If they are a Manager, let them see their own store's categories
                // (Already covered by tenant_id, but good for clarity)
            });
    })
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
                
                Select::make('user_id')
    // ->label('Assign Seller')
    // ->options(function () {
    //     $user = auth()->user();
    //     $sellerOptions = [];

    //     // Fetching the sellers manually
    //     $sellers = \App\Models\User::where('role', 'seller')
    //         ->where('tenant_id', $user->tenant_id)
    //         ->get();

    //     // Old school loop to build the options array
    //     foreach ($sellers as $seller) {
    //         $sellerOptions[$seller->id] = $seller->name;
    //     }

    //     return $sellerOptions;
    ->label('Assign Seller')
->options(function () {
    $user = auth()->user();
    $sellerOptions = [];

    // 1. Start the query for sellers
    $query = \App\Models\User::where('role', 'seller');

    // 2. If the logged-in user is a Manager, only show sellers assigned to THEM
    if ($user->role === 'manager') {
        $query->where('manager_id', $user->id);
    } 
    // 3. If Super Admin, you can leave it as is to see all sellers in the system
    
    $sellers = $query->get();

    // 4. Old school loop to build the options array
    foreach ($sellers as $seller) {
        $sellerOptions[$seller->id] = $seller->name;
    }

    return $sellerOptions;
    })
    ->required()
    ->searchable()
    ->preload()
    ->hidden(function () {
        return auth()->user()->role === 'seller';
    })
    ->visible(function () {
        $role = auth()->user()->role;
        return $role === 'manager' || $role === 'super_admin';
    }),

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
                    ->visible(function ($record) {
                        return $record !== null;
                    })
                    ->content(function ($record) {
                        if (!$record) {
                            return 0;
                        }

                        return $record->orderItems()
                            ->whereHas('order', function ($q) {
                                $q->where('order_status', 'delivered');
                            })
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
    public static function getRelations(): array
{
    return [
        RelationManagers\ReviewsRelationManager::class,
    ];
}

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('row_index')->label('#')->rowIndex(),

            ImageColumn::make('image')
                ->label('Thumbnail')
                ->circular()
                ->disk('public')
                ->visibility('public')
                ->checkFileExistence(false),

            TextColumn::make('name')->searchable(),

            TextColumn::make('price')->money('USD'),

            TextColumn::make('category.name')->label('Category')->badge(),

           Tables\Columns\TextColumn::make('seller.name')
            ->label('Seller')
            ->searchable()
            ->badge()
            ->color('info')
            ->visible(function () {
                $user = auth()->user();
                // Check if user is logged in and is either Super Admin or Manager
                return $user && ($user->role === 'super_admin' || $user->role === 'manager');
            }),

        Tables\Columns\TextColumn::make('created_at')
            ->dateTime()
            ->toggleable(isToggledHiddenByDefault: true),
    ])

            // TextColumn::make('created_at')
            //     ->dateTime()
            //     ->toggleable(true, true),

        ->defaultPaginationPageOption(10)
          ->paginated([5, 10, 25, 50, 100, 'all'])
          ->actions([
              Tables\Actions\Action::make('viewReviews')
                  ->label('Reviews')
                  ->icon('heroicon-o-chat-bubble-left-right')
                  ->color('info')
                  ->modalHeading(function ($record) {
                      return "Reviews for " . $record->name;
                  })
                  ->modalWidth('2xl')
                  ->modalSubmitAction(false)
                  ->modalContent(function ($record) {
                      return view('filament.components.product-reviews-modal', [
                          'reviews' => $record->reviews()->with('user:id,name')->latest()->get(),
                      ]);
                  }),

              Tables\Actions\EditAction::make(),

              Tables\Actions\DeleteAction::make()
                  ->visible(function () {
                      $user = auth()->user();
                      return $user && in_array($user->role, [User::ROLE_SUPER_ADMIN, User::ROLE_MANAGER]);
                  }),
          ])
          ->bulkActions([
              Tables\Actions\BulkActionGroup::make([
                  Tables\Actions\DeleteBulkAction::make()
                      ->visible(function () {
                          $user = auth()->user();
                          return $user && in_array($user->role, [User::ROLE_SUPER_ADMIN, User::ROLE_MANAGER]);
                      }),
              ]),
          ]);
    }

    // ✅ Final tenant filter
//     public static function getEloquentQuery(): Builder
// {
//     $query = parent::getEloquentQuery()->with(['category', 'seller']);

//     if (!auth()->check()) {
//         return $query;
//     }

//     /** @var \App\Models\User $user */
//     $user = auth()->user();

//     // 1. Super Admin: ABSOLUTE VIEW
//     // If they are a super admin, we return the query immediately 
//     // without ANY where clauses.
//     if ($user->role === User::ROLE_SUPER_ADMIN || $user->isSuperAdmin()) {
//         return $query;
//     }

//     // 2. Manager: Scope to their specific Store
//     if ($user->role === User::ROLE_MANAGER) {
//         return $query->where('tenant_id', $user->tenant_id);
//     }

//     // 3. Seller: Scope to their specific items
//     if ($user->role === User::ROLE_SELLER) {
//         return $query->where('user_id', $user->id);
//     }

//     // If a user has no recognized role, show nothing for safety
//     return $query->whereRaw('1 = 0');
// }


public static function getEloquentQuery(): Builder
{
    /** @var \App\Models\User $user */
    $user = auth()->user();

    // 1. We MUST use withoutGlobalScopes() to stop Filament/Laravel from forcing 
    // the Manager into their own tenant (Tenant 1) when looking at Seller products (Tenant 2).
    $query = parent::getEloquentQuery()
        ->withoutGlobalScopes()
        ->with(['category', 'seller']);

    if (!$user || $user->isSuperAdmin()) {
        return $query;
    }

    // 2. Manager Logic: Bridge the gap to Sellers
    if ($user->role === User::ROLE_MANAGER) {
        return $query->whereHas('seller', function (Builder $q) use ($user) {
            $q->where('manager_id', $user->id);
        });
    }

    // 3. Seller Logic: Only see what they own
    if ($user->role === User::ROLE_SELLER) {
        // We explicitly use the user_id and the tenant_id to be safe
        return $query->where('user_id', $user->id)
                     ->where('tenant_id', $user->tenant_id);
    }

    return $query->whereRaw('1 = 0');
}

    // ✅ Auto tenant assign
//     public static function mutateFormDataBeforeCreate(array $data): array
// {
//     $user = auth()->user();

//     // Only assign tenant_id if the user actually belongs to one
//     if ($user->tenant_id) {
//         $data['tenant_id'] = $user->tenant_id;
//     }

//     if ($user->role === User::ROLE_SELLER) {
//         $data['user_id'] = $user->id;
//     }

//     // If Manager/Admin didn't pick a seller, default to themselves
//     if (empty($data['user_id'])) {
//         $data['user_id'] = $user->id;
//     }

//     return $data;
// }
public static function mutateFormDataBeforeCreate(array $data): array
{
    /** @var \App\Models\User $user */
    $user = auth()->user();

    // 1. If a specific Seller was selected in the dropdown (Manager/Admin action)
    if (!empty($data['user_id'])) {
        $targetSeller = \App\Models\User::find($data['user_id']);
        
        if ($targetSeller) {
            // CRITICAL: Set the product's tenant to match the Seller's tenant
            // Even if the Manager is in Tenant 1, the product must go to the Seller's Tenant
            $data['tenant_id'] = $targetSeller->tenant_id;
        }
    } 
    // 2. If no seller was selected (Usually when a Seller creates their own product)
    else {
        $data['user_id'] = $user->id;
        $data['tenant_id'] = $user->tenant_id;
    }

    // 3. Final Safety: Ensure tenant_id is never NULL if the user has one
    if (empty($data['tenant_id']) && $user->tenant_id) {
        $data['tenant_id'] = $user->tenant_id;
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