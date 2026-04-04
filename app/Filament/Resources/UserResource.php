<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Builder;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationGroup = null;

    // ✅ NAVIGATION CONTROL
    public static function shouldRegisterNavigation(): bool
    {
        if (!auth()->check()) {
            return false;
        }

        if (auth()->user()->role === 'admin') {
            return true;
        }

        return false;
    }

    // ✅ ACCESS CONTROL
    public static function canViewAny(): bool
    {
        if (!auth()->check()) {
            return false;
        }

        if (auth()->user()->role === 'admin') {
            return true;
        }

        return false;
    }

    public static function canCreate(): bool
    {
        if (!auth()->check()) {
            return false;
        }

        if (auth()->user()->role === 'admin') {
            return true;
        }

        return false;
    }

    public static function canEdit($record): bool
    {
        if (!auth()->check()) {
            return false;
        }

        if (auth()->user()->role === 'admin') {
            return true;
        }

        return false;
    }

    public static function canDelete($record): bool
    {
        if (!auth()->check()) {
            return false;
        }

        if (auth()->user()->role === 'admin') {
            return true;
        }

        return false;
    }

    // ✅ QUERY LOGIC (MULTI-TENANT SAFE)
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (!auth()->check()) {
            return $query;
        }

        $user = auth()->user();

        // Super admin sees all users
        if ($user->role === 'super_admin') {
            return $query;
        }

        // Admin sees only their tenant users
        return $query->where('tenant_id', $user->tenant_id);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('User Details')
                ->schema([

                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('email')
                        ->email()
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(255),

                    // ✅ ROLE FIELD
                    Forms\Components\Select::make('role')
                        ->options([
                            'super_admin' => 'Super Admin',
                            'admin' => 'Admin',
                            'manager' => 'Manager',
                            'seller' => 'Seller',
                            'customer' => 'Customer',
                        ])
                        ->required()
                        ->disabled(function () {
                            if (!auth()->check()) return true;

                            return auth()->user()->role !== 'super_admin';
                        }),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Account Status')
                        ->onColor('success')
                        ->offColor('danger')
                        ->helperText('Inactive users are restricted from placing orders.')
                        ->disabled(function ($record) {
                            if (!auth()->check()) return true;

                            return auth()->id() === $record->id;
                        })
                        ->default(true),

                    Forms\Components\TextInput::make('password')
                        ->password()
                        ->dehydrateStateUsing(function ($state) {
                            return Hash::make($state);
                        })
                        ->dehydrated(function ($state) {
                            return !empty($state);
                        })
                        ->label('Reset Password')
                        ->visible(function ($record) {
                            if (!auth()->check()) return false;

                            return auth()->id() === $record->id;
                        })
                        ->placeholder('Leave blank to keep current password'),

                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('role')
                    ->colors([
                        'danger' => 'super_admin',
                        'warning' => 'admin',
                        'info' => 'manager',
                        'success' => 'seller',
                        'gray' => 'customer',
                    ])
                    ->sortable(),

                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Status')
                    ->onColor('success')
                    ->offColor('danger')
                    ->disabled(function ($record) {
                        if (!auth()->check()) return true;

                        return auth()->id() === $record->id;
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('M d, Y')
                    ->sortable(),
            ])
            ->recordUrl(null)
            ->actions([

                Tables\Actions\ViewAction::make()
                    ->visible(function () {
                        if (!auth()->check()) return false;

                        return auth()->user()->role === 'admin';
                    }),

            ])
            ->bulkActions([
                // intentionally empty (security)
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}