<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->mutateFormDataUsing(function (array $data): array {
                    /** @var \App\Models\User $currentUser */
                    $currentUser = auth()->user();

                    // 1. Handle Roles & Tenants (Existing logic)
                    if ($currentUser->isSuperAdmin()) {
                        $data['role'] = User::ROLE_MANAGER;
                        // $data['tenant_id'] = $currentUser->tenant_id;
                        if (empty($data['tenant_id'])) {
                        $data['tenant_id'] = $currentUser->tenant_id;
                    }
                    }

                    // 2. AUTO-GENERATE PASSWORD
                    // This fixes the "Field password doesn't have a default value" error
                    if (empty($data['password'])) {
                        $plainPassword = Str::random(12);
                        $data['password'] = Hash::make($plainPassword);

                        // Optional: Send the password to the UI so you can see it once
                        Notification::make()
                            ->title('User Created Successfully')
                            ->body("Auto-generated password: **{$plainPassword}**")
                            ->success()
                            ->persistent() // Keeps it on screen so you can copy it
                            ->send();
                    }

                    return $data;
                }),
        ];
    }

    public function activateUser($id)
    {
        $user = User::find($id);
        if ($user) {
            $user->update(['is_active' => true]);
            Notification::make()->title('User Activated')->success()->send();
        }
    }

    public function deactivateUser($id)
    {
        $user = User::find($id);
        if ($user && auth()->id() !== $user->id) {
            $user->update(['is_active' => false]);
            Notification::make()->title('User Deactivated')->success()->send();
        }
    }
}