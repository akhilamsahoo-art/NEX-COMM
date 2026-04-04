<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Cache;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    /**
     * Ensures the table frame renders immediately.
     */
    public function getIsTableLoaded(): bool
    {
        return true;
    }

    /**
     * ✅ UPDATED PAGINATION WITH CACHE AWARENESS
     * This version includes the 'perPage' limit in the cache key.
     */
    protected function paginateTableQuery(Builder $query): Paginator
{
    $perPage = $this->getTableRecordsPerPage();
    $page = request()->get('page', 1);

    $cacheKey = "prod_ids_page_{$page}_{$perPage}";

    $ids = Cache::remember($cacheKey, 300, function () use ($query, $perPage) {
        return $query->pluck('id')->toArray();
    });

    return \App\Models\Product::whereIn('id', $ids)
        ->paginate($perPage);
}
}