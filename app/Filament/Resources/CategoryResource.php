<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use App\Models\Category;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Shop Management';
    protected static ?int $navigationSort = 1;

    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();
        return $user && in_array($user->role, [
            User::ROLE_SUPER_ADMIN,
            User::ROLE_MANAGER,
            User::ROLE_SELLER,
        ]);
    }

    public static function mutateFormDataBeforeCreate(array $data): array
{
    // If a seller is chosen, find their tenant_id and assign it to the category
    if (!empty($data['user_id'])) {
        $seller = User::find($data['user_id']);
        if ($seller) {
            $data['tenant_id'] = $seller->tenant_id;
        }
    }
    
    return $data;
}

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Category Details')
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(191),

                    // Select::make('user_id')
                    //     ->label('Assign Seller')
                    //     ->options(function () {
                    //         $user = auth()->user();

                    //         $sellers = User::where('role', 'seller')
                    //             ->where('tenant_id', $user->tenant_id)
                    //             ->get();

                    //         $options = [];
                    //         foreach ($sellers as $seller) {
                    //             $options[$seller->id] = $seller->name;
                    //         }

                    //         return $options;
                    //     })
                    Select::make('user_id')
    ->label('Assign Seller')
    ->options(function () {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        // If Super Admin, show all sellers
        if ($user->isSuperAdmin()) {
            return User::where('role', 'seller')->pluck('name', 'id');
        }

        // If Manager, show only sellers assigned to THEM via manager_id
        if ($user->role === 'manager') {
            return User::where('role', 'seller')
                ->where('manager_id', $user->id) // 👈 Use manager_id here!
                ->pluck('name', 'id');
        }

        return [];
    })
                        ->required()
                        ->hidden(fn () => auth()->user()->role === 'seller')
                        ->visible(fn () => in_array(auth()->user()->role, ['manager', 'super_admin']))
                        ->searchable()
                        ->preload(),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')
                ->searchable()
                ->sortable(),

            Tables\Columns\TextColumn::make('seller.name')
                ->label('Seller')
                ->badge()
                ->color('info')
                ->visible(function () {
                    $user = auth()->user();
                    return $user && in_array($user->role, ['super_admin', 'manager']);
                }),

            Tables\Columns\TextColumn::make('created_at')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ])
        ->actions([
            Tables\Actions\EditAction::make(),

            Tables\Actions\DeleteAction::make()
                ->visible(fn () => in_array(auth()->user()->role, ['super_admin', 'manager'])),
        ])
        ->bulkActions([
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make()
                    ->visible(fn () => in_array(auth()->user()->role, ['super_admin', 'manager'])),
            ]),
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        /** @var \App\Models\User $user */
        $user = auth()->user();

        if (!$user) return $query;

        if ($user->isSuperAdmin()) {
            return $query;
        }

        if ($user->role === 'manager') {
        //     return $query->where('tenant_id', $user->tenant_id);
        // }
        return $query->whereHas('seller', function ($q) use ($user) {
            $q->where('manager_id', $user->id);
        })->orWhere('tenant_id', $user->tenant_id); // Also show manager's own categories
    }

        if ($user->role === 'seller') {
            return $query->where('user_id', $user->id);
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }
}