<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    /**
     * Handle tab redirect (IMPORTANT FIX)
     */
    public function mount(): void
    {
        parent::mount();

        // ✅ Redirect when "In Cart" tab is clicked
        if (request()->get('activeTab') === 'in_cart') {
            redirect()->route('filament.admin.resources.carts.index');
        }
    }

    /**
     * Actions in the top right of the list page.
     */
    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New Order')
                ->icon('heroicon-o-plus'),
        ];
    }

    /**
     * Tabs for filtering
     */
    public function getTabs(): array
{
    return [
        // 'all' => Tab::make('All Orders'),

        // // In Cart tab
        // 'in_cart' => Tab::make('In Cart')
        //     ->icon('heroicon-m-shopping-cart'),

        // // Order lifecycle tabs (based on order_status)
        // 'placed' => Tab::make('Placed')
        //     ->modifyQueryUsing(fn (Builder $query) => 
        //         $query->where('order_status', 'placed')
        //     )
        //     ->icon('heroicon-m-clock'),

        // 'processing' => Tab::make('Processing')
        //     ->modifyQueryUsing(fn (Builder $query) => 
        //         $query->where('order_status', 'processing')
        //     )
        //     ->icon('heroicon-m-arrow-path'),

        // 'shipped' => Tab::make('Shipped')
        //     ->modifyQueryUsing(fn (Builder $query) => 
        //         $query->where('order_status', 'shipped')
        //     )
        //     ->icon('heroicon-m-truck'),

        // 'delivered' => Tab::make('Delivered')
        //     ->modifyQueryUsing(fn (Builder $query) => 
        //         $query->where('order_status', 'delivered')
        //     )
        //     ->icon('heroicon-m-check-badge'),

        // 'cancelled' => Tab::make('Cancelled')
        //     ->modifyQueryUsing(fn (Builder $query) => 
        //         $query->where('order_status', 'cancelled')
        //     )
        //     ->icon('heroicon-m-x-circle'),
    ];
}
    // public function updatedActiveTab(): void
    // {
    //     if ($this->activeTab === 'in_cart') {
    //         $this->redirect(route('filament.admin.resources.carts.index'), navigate: true);
    //     }
    // }

    /**
     * Header widgets (optional)
     */
    protected function getHeaderWidgets(): array
    {
        return [
            // OrderResource\Widgets\OrderStats::class,
        ];
    }
}