<?php

namespace App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
{
    /** @var \App\Models\User $user */
    $user = auth()->user();

    if ($user->isManager()) {
        $data['role'] = User::ROLE_SELLER;
        $data['manager_id'] = $user->id;
        $data['tenant_id'] = $user->tenant_id;
    }

    return $data;
}
}
