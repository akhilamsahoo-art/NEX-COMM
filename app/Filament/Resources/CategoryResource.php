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
use Illuminate\Database\Eloquent\Builder;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Shop Management';
    protected static ?int $navigationSort = 1;

    // ✅ Navigation control (FIXED)
    public static function shouldRegisterNavigation(): bool
    {
        if (!auth()->check()) {
            return false;
        }

        return in_array(auth()->user()->role, [
            User::ROLE_SUPER_ADMIN,
            User::ROLE_MANAGER, // ✅ IMPORTANT FIX
            User::ROLE_SELLER,
        ]);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(191),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),

                Tables\Columns\TextColumn::make('seller.name')
                    ->label('Seller')
                    ->visible(function () {
    /** @var \App\Models\User $user */
    $user = auth()->user();
    return $user && $user->isSuperAdmin();
}),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(true, true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(true, true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),

                Tables\Actions\DeleteAction::make()
                    ->visible(fn () => in_array(auth()->user()->role, [
                        User::ROLE_SUPER_ADMIN,
                        User::ROLE_MANAGER
                    ])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => in_array(auth()->user()->role, [
                            User::ROLE_SUPER_ADMIN,
                            User::ROLE_MANAGER
                        ])),
                ]),
            ]);
    }

    // ✅ Query (unchanged logic, just cleaner)
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (!auth()->check()) {
            return $query;
        }

        $user = auth()->user();

        // if ($user->isSuperAdmin()) {
        //     return $query;
        // }

        /** @var \App\Models\User $user */
$user = auth()->user();

if ($user->isSuperAdmin()) {
    return $query;
}

        return $query->where('tenant_id', $user->tenant_id);
    }

    public static function mutateFormDataBeforeCreate(array $data): array
    {
        if (auth()->check()) {
            $data['tenant_id'] = auth()->user()->tenant_id;
        }

        return $data;
    }

    // public static function mutateFormDataBeforeSave(array $data): array
    // {
    //     if (auth()->check() && auth()->user()->isSeller()) {
    //         $data['tenant_id'] = auth()->user()->tenant_id;
    //     }

    //     return $data;
    // }
    public static function mutateFormDataBeforeSave(array $data): array
{
    if (!auth()->check()) {
        return $data;
    }

    /** @var \App\Models\User $user */
    $user = auth()->user();

    if ($user->isSeller()) {
        $data['tenant_id'] = $user->tenant_id;
    }

    return $data;
}

    public static function getRelations(): array
    {
        return [];
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