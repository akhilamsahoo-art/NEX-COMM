<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ReviewsRelationManager extends RelationManager
{
    protected static string $relationship = 'reviews';

    /** * ✅ THE FIX: We bypass Filament's relationship constraints 
     * and manually fetch reviews for the current product.
     */
    // protected function getEloquentQuery(): Builder
    // {
    //     // 1. Get the current product ID from the owner record
    //     $productId = $this->getOwnerRecord()->id;

    //     // 2. Query the Review model directly to bypass any "Owner" or "Tenant" mismatch
    //     // We use 'withoutGlobalScopes' to ensure the Manager isn't blocked by the Product model's isolation.
    //     return \App\Models\Review::query()
    //         ->where('product_id', $productId)
    //         ->withoutGlobalScopes();
    // }

    protected function getEloquentQuery(): Builder
{
     /** @var \App\Models\User $user */
    $user = auth()->user();
    $productId = $this->getOwnerRecord()->id;

    // Direct query to the Review model bypassing scopes
    $query = \App\Models\Review::query()
        ->where('product_id', $productId)
        ->withoutGlobalScopes();

    if (!$user || $user->isSuperAdmin()) {
        return $query;
    }

    // If Manager, check if the product's seller is managed by them
    if ($user->role === \App\Models\User::ROLE_MANAGER) {
        return $query->whereHas('product.seller', function ($q) use ($user) {
            $q->where('manager_id', $user->id);
        });
    }

    return $query;
}

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('comment')
                ->required()
                ->maxLength(255),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('comment')
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Customer'),
                Tables\Columns\TextColumn::make('rating')
                    ->badge()
                    ->color(fn ($state) => $state >= 4 ? 'success' : 'warning'),
                Tables\Columns\TextColumn::make('comment')
                    ->limit(50),
            ])
            ->filters([])
            ->headerActions([])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}