<?php

namespace App\Filament\Resources\CategoryResource\Pages;

use App\Filament\Resources\CategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCategory extends CreateRecord
{
    protected static string $resource = CategoryResource::class;

    protected static bool $canCreateAnother = false;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
{
    $user = auth()->user();

    $data['tenant_id'] = $user->tenant_id;

    if ($user->role === 'seller') {
        $data['user_id'] = $user->id;
    }

    if ($user->role === 'manager' && empty($data['user_id'])) {
        $data['user_id'] = $user->id;
    }

    return $data;
}
}
