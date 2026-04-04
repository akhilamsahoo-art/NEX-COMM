<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
    // protected $listeners = [
    //     'activateUser' => 'activateUser',
    //     'deactivateUser' => 'deactivateUser',
    // ];
    public function activateUser($id)
    {
        $user = \App\Models\User::find($id);
    
        if ($user) {
            $user->update(['is_active' => true]);
    
            // ✅ ADD HERE
            Notification::make()
                ->title('User Activated')
                ->success()
                ->send();
        }
    }
public function deactivateUser($id)
{
    $user = \App\Models\User::find($id);

    if ($user && auth()->id() !== $user->id) {
        $user->update(['is_active' => false]);

        // ✅ ADD HERE
        Notification::make()
            ->title('User Deactivated')
            ->success()
            ->send();
    }
}
}
