<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    /**
     * Header Actions
     * Removed DeleteAction and ViewAction as per your request.
     * The admin can only "Cancel" via the status dropdown in the form.
     */
    protected function getHeaderActions(): array
    {
        return [];
    }

    /**
     * Redirect back to the Order List after the admin clicks "Save changes".
     */
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    protected function authorizeAccess(): void
{
    if (auth()->user()->role !== 'seller') {
        abort(403, 'Only seller can edit orders');
    }
}

    /**
     * Custom notification message to confirm the update.
     */
    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Order Updated')
            ->body('The order status and details have been updated successfully.');
    }

    /**
     * Logic before saving.
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Logic for cancellation is handled by the 'status' field in the Resource form.
        return $data;
    }

    /**
     * Logic after saving.
     * Useful for triggering internal events when an order is cancelled.
     */
    protected function afterSave(): void
    {
        $order = $this->record;

        if ($order->status === 'cancelled') {
            // You could trigger stock restoration logic here if needed.
            Notification::make()
                ->warning()
                ->title('Order Cancelled')
                ->body('The order has been marked as cancelled.')
                ->send();
        }
    }
}