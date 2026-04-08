<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use Filament\Tables\Columns\TextColumn;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Builder;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationGroup = null;

  public static function mutateFormDataBeforeCreate(array $data): array
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        if ($user->isSuperAdmin()) {
            $data['role'] = User::ROLE_MANAGER; // force manager role
            $data['tenant_id'] = $user->tenant_id; // assign tenant automatically
        }

        return $data;
    }


    // ✅ NAVIGATION CONTROL (EXISTING LOGIC KEPT)
    public static function shouldRegisterNavigation(): bool
    {
        if (!auth()->check()) {
            return false;
        }

        // return auth()->user()->role === User::ROLE_SUPER_ADMIN;
        return in_array(auth()->user()->role, [
    User::ROLE_SUPER_ADMIN,
    User::ROLE_MANAGER,
]);
    }

    // ✅ ACCESS CONTROL (EXISTING LOGIC KEPT)
    public static function canViewAny(): bool
    {
        if (!auth()->check()) {
            return false;
        }

        // return auth()->user()->role === User::ROLE_SUPER_ADMIN;
        return in_array(auth()->user()->role, [
            User::ROLE_SUPER_ADMIN,
            User::ROLE_MANAGER,
        ]);
    }

    // public static function canCreate(): bool
    // {
    //     if (!auth()->check()) {
    //         return false;
    //     }

    //     // return auth()->user()->role === User::ROLE_SUPER_ADMIN;
    //     return in_array(auth()->user()->role, [
    //         User::ROLE_SUPER_ADMIN,
    //         User::ROLE_MANAGER,
    //     ]);
    // }


    public static function canCreate(): bool
{
    if (!auth()->check()) {
        return false;
    }

    // ✅ Only super admins can create new users (which will be managers)
    return auth()->user()->role === User::ROLE_SUPER_ADMIN;
} 
    public static function canEdit($record): bool
    {
        if (!auth()->check()) {
            return false;
        }

        // return auth()->user()->role === User::ROLE_SUPER_ADMIN;
        return in_array(auth()->user()->role, [
            User::ROLE_SUPER_ADMIN,
            User::ROLE_MANAGER,
        ]);
    }

    public static function canDelete($record): bool
    {
        if (!auth()->check()) {
            return false;
        }

        return auth()->user()->role === User::ROLE_SUPER_ADMIN;
    }

    // ✅ QUERY LOGIC (EXISTING LOGIC KEPT)
    public static function getEloquentQuery(): Builder
{
    $query = parent::getEloquentQuery();

    if (!auth()->check()) {
        return $query;
    }

     /** @var \App\Models\User $user */
    $user = auth()->user();

    if ($user->isSuperAdmin()) {
        return $query;
    }

    if ($user->isManager()) {
        return $query
            ->where('tenant_id', $user->tenant_id)
            ->where('manager_id', $user->id);
    }

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
                        ->unique(null, null, null, true)
                        ->maxLength(255),

                    Forms\Components\Select::make('role')
                        ->options([
                            User::ROLE_SUPER_ADMIN => 'Super Admin',
                            'admin' => 'Admin',
                            User::ROLE_MANAGER => 'Manager',
                            User::ROLE_SELLER => 'Seller',
                            User::ROLE_CUSTOMER => 'Customer',
                        ])
                        ->required()
                        ->disabled(function () {
                            if (!auth()->check()) return true;

                            return auth()->user()->role !== User::ROLE_SUPER_ADMIN;
                        }),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Account Status')
                        ->onColor('success')
                        ->offColor('danger')
                        ->helperText('Inactive users are restricted from placing orders.')
                        ->disabled(function ($record) {
                            if (!auth()->check() || !$record) return true;

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
                            if (!auth()->check() || !$record) return false;

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
                    ->searchable(),
                    // ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                    // ->sortable(),

                Tables\Columns\BadgeColumn::make('role')
                    ->colors([
                        'danger' => 'super_admin',
                        'warning' => 'admin',
                        'info' => 'manager',
                        'success' => 'seller',
                        'gray' => 'customer',
                    ]),
                    // ->sortable(),

                TextColumn::make('is_active')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Active' : 'Inactive')
                    ->color(fn (bool $state): string => $state ? 'success' : 'danger')
                    ->icon(fn (bool $state): string => $state ? 'heroicon-m-check-circle' : 'heroicon-m-x-circle'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('M d, Y'),
                    // ->sortable(),
            ])
            ->recordUrl(null)
            ->actions([
                // ✅ NEW BUTTON LOGIC ADDED HERE
                Action::make('set_active')
                    ->label('Active')
                    ->button()
                    ->size('sm')
                    ->color(fn ($record) => $record->is_active ? 'success' : 'gray')
                    ->disabled(fn ($record) => $record->is_active) // Shaded if already active
                    ->action(function ($record) {
                        $record->update(['is_active' => true]);
                        Notification::make()->title('User Activated')->success()->send();
                    }),

                Action::make('set_inactive')
                    ->label('Inactive')
                    ->button()
                    ->size('sm')
                    ->color(fn ($record) => !$record->is_active ? 'danger' : 'gray')
                    ->disabled(fn ($record) => !$record->is_active) // Shaded if already inactive
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        // Protection logic: Don't let admin deactivate themselves
                        if (auth()->id() === $record->id) {
                            Notification::make()->title('Error')->body('You cannot deactivate yourself.')->danger()->send();
                            return;
                        }
                        $record->update(['is_active' => false]);
                        Notification::make()->title('User Deactivated')->warning()->send();
                    }),

                    Tables\Actions\EditAction::make()
        ->visible(fn () => in_array(auth()->user()->role, [
            User::ROLE_MANAGER,
            User::ROLE_SELLER,
        ])),

                Tables\Actions\ViewAction::make()
                    ->visible(fn () => auth()->check() && auth()->user()->role === User::ROLE_SUPER_ADMIN),
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