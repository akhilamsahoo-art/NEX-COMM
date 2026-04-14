<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AddressesRelationManager extends RelationManager
{
    protected static string $relationship = 'addresses';

   public function form(Form $form): Form
{
    return $form
        ->schema([
            Forms\Components\TextInput::make('address_line_1')
                ->required()
                ->placeholder('123 Main St'),
            Forms\Components\TextInput::make('city')
                ->required(),
            Forms\Components\TextInput::make('state')
                ->required(),
            Forms\Components\TextInput::make('postal_code')
                ->required(),
            Forms\Components\Toggle::make('is_default')
                ->label('Default Address'),
        ]);
}

    public function table(Table $table): Table
{
    return $table
        ->recordTitleAttribute('address_line_1')
        ->columns([
            Tables\Columns\TextColumn::make('address_line_1'),
            Tables\Columns\TextColumn::make('city'),
            Tables\Columns\IconColumn::make('is_default')->boolean(),
        ])
        ->headerActions([
            Tables\Actions\CreateAction::make(),
        ])
        ->actions([
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
        ]);
}
}
