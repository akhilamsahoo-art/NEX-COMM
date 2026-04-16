<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'price',
        'category_id',
        'cost_price',
        'description',
        'image',
        'key_features',
        'ai_summary',
        'quantity',
        'tenant_id',
        'user_id',
    ];

    // =========================
    // Relationships
    // =========================

    /**
     * The User/Seller who owns the product.
     */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    // public function reviews(): HasMany
    // {
    //     return $this->hasMany(Review::class);
    // }
    // app/Models/Product.php
public function reviews(): \Illuminate\Database\Eloquent\Relations\HasMany
{
    return $this->hasMany(Review::class);
}

    public function orders(): BelongsToMany
    {
        return $this->belongsToMany(
            Order::class,
            'order_product',
            'product_id',
            'order_id'
        )->withPivot('quantity')->withTimestamps();

    }

    // =========================
    // Model Boot Logic
    // =========================

    protected static function booted()
    {
        /**
         * Global tenant + seller isolation
         * This ensures that even outside Filament, queries are scoped.
         */
        static::addGlobalScope('tenant_isolation', function (Builder $query) {
            if (!auth()->check()) {
                return;
            }

            /** @var \App\Models\User $user */
            $user = auth()->user();

            // Super admins see everything; no scope applied.
            if ($user->isSuperAdmin()) {
                return;
            }

            // Sellers only see products assigned to them
            if ($user->role === 'seller') {
                $query->where('user_id', $user->id);
            }

            // ✅ MODIFIED: Managers see products in their tenant OR products belonging to their managed sellers
            if ($user->role === 'manager') {
                $query->where(function (Builder $subQuery) use ($user) {
                    $subQuery->where('tenant_id', $user->tenant_id)
                             ->orWhereHas('seller', function (Builder $sellerQuery) use ($user) {
                                 $sellerQuery->where('manager_id', $user->id);
                             });
                });
            }

            if ($user->role === 'customer') {
        return;
    }
        });

        /**
         * Creating logic: Auto-assign ownership and generate slug
         */
        static::creating(function ($product) {
            if (auth()->check()) {
                /** @var \App\Models\User $user */
                $user = auth()->user();

                // ✅ MODIFIED: Only assign the creator's tenant_id if one wasn't manually passed 
                // (This allows the Resource to assign the Seller's tenant_id)
                if (empty($product->tenant_id)) {
                    $product->tenant_id = $user->tenant_id;
                }

                // If user is a seller, they are the owner
                if ($user->role === 'seller') {
                    $product->user_id = $user->id;
                }

                // If manager creates it and didn't select a seller, they become the owner
                if ($user->role === 'manager' && empty($product->user_id)) {
                    $product->user_id = $user->id;
                }
            }

            // Slug generation
            if (empty($product->slug) && !empty($product->name)) {
                $product->slug = Str::slug($product->name);
            }
        });
    }

   public function getImageAttribute($value)
{
    if (!$value) return null;

    // remove 'storage/' if already present
    $value = str_replace('storage/', '', $value);

    return url('storage/' . $value);
}
}