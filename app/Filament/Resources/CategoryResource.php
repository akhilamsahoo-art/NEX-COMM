<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use App\Models\Category;
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

    // ✅ NEW: Navigation control
    public static function shouldRegisterNavigation(): bool
{
    if (!auth()->check()) {
        return false;
    }

    return in_array(auth()->user()->role, ['admin', 'seller']);
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

                // ✅ Optional: show tenant only for admin
                Tables\Columns\TextColumn::make('tenant_id')
                    ->label('Tenant')
                    ->visible(fn () => in_array(auth()->user()->role, ['admin', 'super_admin']),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true))
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),

                // ✅ Optional safety: seller cannot delete
                Tables\Actions\DeleteAction::make()
                ->visible(fn () => in_array(auth()->user()->role, ['super_admin', 'manager']))
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                    ->visible(fn () => in_array(auth()->user()->role, ['super_admin', 'manager'])),
                ]),
            ]);
    }

    // ✅ FINAL TENANT FILTER
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (!auth()->check()) {
            return $query;
        }

        $user = auth()->user();

        // Admin sees all
        if ($user->role === 'admin') {
            return $query;
        }

        // Seller sees only their categories
        return $query->where('tenant_id', $user->tenant_id);
    }

    // ✅ AUTO ASSIGN TENANT
    public static function mutateFormDataBeforeCreate(array $data): array
    {
        if (auth()->check()) {
            $data['tenant_id'] = auth()->user()->tenant_id;
        }

        return $data;
    }

    public static function mutateFormDataBeforeSave(array $data): array
    {
        if (auth()->check() && auth()->user()->role === 'seller') {
            $data['tenant_id'] = auth()->user()->tenant_id;
        }

        return $data;
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
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