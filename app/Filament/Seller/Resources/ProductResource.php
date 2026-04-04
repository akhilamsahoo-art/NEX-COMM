<?php

namespace App\Filament\Seller\Resources;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use App\Filament\Seller\Resources\ProductResource\Pages;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
{
    return $form->schema([

        // ✅ PRODUCT BASIC INFO
        Section::make('Product Info')
            ->schema([
                TextInput::make('name')
                    ->required()
                    ->columnSpan(1),

                TextInput::make('price')
                    ->numeric()
                    ->required()
                    ->columnSpan(1),

                TextInput::make('cost_price')
                    ->numeric()
                    ->label('Cost Price')
                    ->columnSpan(1),

                TextInput::make('quantity')
                    ->numeric()
                    ->required()
                    ->default(0)
                    ->columnSpan(1),

                Select::make('category_id')
                    ->relationship(
                        name: 'category',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn ($query) =>
                            $query->where('tenant_id', auth()->user()->tenant_id)
                    )
                    ->required()
                    ->columnSpanFull(),
            ])
            ->columns(2),

        // ✅ DESCRIPTION
       

        // ✅ EXTRA DETAILS
        Section::make('Extra Details')
            ->schema([
                Textarea::make('key_features')
                    ->label('Key Features')
                    ->rows(4)
                    ->columnSpan(1),

                Forms\Components\FileUpload::make('image')
                    ->image()
                    ->directory('products')
                    ->columnSpan(1),
            ])
            ->columns(2),

            Section::make('Description')
            ->schema([
                Textarea::make('description')
                ->maxLength(5000)
    ->rows(5)
    ->columnSpanFull()
    ->hintAction(
        Action::make('generateAiDescription')
            ->label(' AI Generate Description')
            ->icon('heroicon-m-sparkles')
            ->color('warning')
            ->action(function (Forms\Set $set, Forms\Get $get) {
                $name = $get('name');
                $features = $get('key_features');
                if (blank($name)) {
                    Notification::make()->title('Name required')->warning()->send();
                    return;
                }

                $aiService = app(\App\Services\AiFoundationService::class);
                $result = $aiService->generate('product_generator', [
                    'name' => $name,
                    'features' => $features ?? 'High quality premium product',
                ]);

                $set('description', $result);
            })
    
    )
            ])
            ->columns(1),

        // ✅ TENANT
        Hidden::make('tenant_id')
            ->default(auth()->user()->tenant_id),
    ]);
}

public static function mutateFormDataBeforeCreate(array $data): array
{
    if (auth()->check()) {
        $data['tenant_id'] = auth()->user()->tenant_id;
    }

    if (isset($data['name']) && empty($data['slug'])) {
        $data['slug'] = \Str::slug($data['name']);
    }

    return $data;
}

public static function mutateFormDataBeforeSave(array $data): array
{
    if (auth()->check() && !auth()->user()->isSuperAdmin()) {
        $data['tenant_id'] = auth()->user()->tenant_id;
    }

    if (isset($data['name'])) {
        $data['slug'] = \Str::slug($data['name']);
    }

    return $data;
}

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')
                ->searchable(),

            Tables\Columns\TextColumn::make('price')
                ->money('USD'),

            Tables\Columns\TextColumn::make('quantity') // ✅ NEW
                ->label('Stock'),

            Tables\Columns\TextColumn::make('category.name')
                ->label('Category'),

            Tables\Columns\ImageColumn::make('image') // ✅ NEW
                ->label('Image'),

            Tables\Columns\TextColumn::make('created_at')
                ->dateTime(),
        ]);
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
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}