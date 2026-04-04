<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;

    /**
     * Where to send the user after they click 'Create'
     */
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * Use this hook if you need to manipulate data 
     * before it is sent to the database.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Example: Force the user_id to be the currently logged-in admin 
        // if the field wasn't filled.
        // $data['user_id'] = auth()->id();

        return $data;
    }

    /**
     * Use this hook to perform actions AFTER the order is created
     * (e.g., Sending a confirmation email or generating an invoice PDF)
     */
    protected function afterCreate(): void
    {
        // $order = $this->record;
        // Notification::make()->title('Order Created')->success()->send();
    }
}