<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Notifications\Notification;
use App\Models\User;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // ✅ "Active" button: Visible to Admin and Manager
            Actions\Action::make('set_active')
                ->label('Active')
                ->button()
                ->color(fn ($record) => $record->is_active ? 'success' : 'gray')
                ->disabled(fn ($record) => $record->is_active)
                ->visible(fn () => in_array(auth()->user()->role, [
                    User::ROLE_SUPER_ADMIN,
                    User::ROLE_MANAGER,
                ]))
                ->action(function ($record) {
                    $record->update(['is_active' => true]);
                    Notification::make()->title('User Activated')->success()->send();
                }),

            // ✅ "Inactive" button: Visible to Admin and Manager with your protection logic
            Actions\Action::make('set_inactive')
                ->label('Inactive')
                ->button()
                ->color(fn ($record) => !$record->is_active ? 'danger' : 'gray')
                ->disabled(fn ($record) => !$record->is_active)
                ->requiresConfirmation()
                ->visible(fn () => in_array(auth()->user()->role, [
                    User::ROLE_SUPER_ADMIN,
                    User::ROLE_MANAGER,
                ]))
                ->action(function ($record) {
                    // Protection: Don't let anyone deactivate themselves
                    if (auth()->id() === $record->id) {
                        Notification::make()->title('Error')->body('You cannot deactivate yourself.')->danger()->send();
                        return;
                    }
                    $record->update(['is_active' => false]);
                    Notification::make()->title('User Deactivated')->warning()->send();
                }),

            Actions\EditAction::make(),
        ];
    }
}