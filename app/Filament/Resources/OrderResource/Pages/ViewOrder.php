<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use Filament\Resources\Pages\ViewRecord;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function authorizeAccess(): void
    {
        if (auth()->user()->role === 'seller') {
            abort(403);
        }
    }
}