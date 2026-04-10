<?php

namespace App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Illuminate\Support\Str;
use Filament\Notifications\Notification;
use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

//     protected function mutateFormDataBeforeCreate(array $data): array
// {
//     /** @var \App\Models\User $user */
//     $user = auth()->user();

//     if ($user->isManager()) {
//         $data['role'] = User::ROLE_SELLER;
//         $data['manager_id'] = $user->id;
//         $data['tenant_id'] = $user->tenant_id;
//     }

//     return $data;
// }

protected function mutateFormDataBeforeCreate(array $data): array
{
    /** @var \App\Models\User $user */
    $user = auth()->user();

    // 🔐 Generate random password
    $plainPassword = Str::random(10);

    $data['password'] = bcrypt($plainPassword);

    if ($user->isSuperAdmin()) {
        $data['role'] = User::ROLE_MANAGER;
        $data['tenant_id'] = $user->tenant_id;
    }

    if ($user->isManager()) {
        $data['role'] = User::ROLE_SELLER;
        $data['manager_id'] = $user->id;
        $data['tenant_id'] = $user->tenant_id;
    }

    // store temp password to show later
    session()->flash('generated_password', $plainPassword);

    return $data;
}

protected function afterCreate(): void
{
    $password = session('generated_password');

    Notification::make()
        ->title('User Created Successfully')
        ->body("Email: {$this->record->email}\nPassword: {$password}")
        ->success()
        ->send();
}
}
