<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    /**
     * This handles the data BEFORE the record is created.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();

        // 1. Always ensure the tenant_id is set to the current user's tenant
        if ($user->tenant_id) {
            $data['tenant_id'] = $user->tenant_id;
        }

        // 2. Logic for SELLER:
        // If a seller is creating the product, force the user_id to be their own ID.
        if ($user->role === 'seller') {
            $data['user_id'] = $user->id;
        }

        // 3. Logic for MANAGER:
        // If the manager didn't pick a seller from the dropdown, 
        // default the user_id to the manager themselves.
        if ($user->role === 'manager') {
            if (empty($data['user_id'])) {
                $data['user_id'] = $user->id;
            }
        }

        return $data;
    }

    // This removes the "Create & Create Another" button
    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction(),
            $this->getCancelFormAction(),
        ];
    }

    // Redirect to the list page after creating
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}