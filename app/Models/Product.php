<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

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

    public function reviews(): HasMany
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
        static::addGlobalScope('tenant_isolation', function ($query) {
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

            // Managers see all products in their tenant
            if ($user->role === 'manager') {
                $query->where('tenant_id', $user->tenant_id);
            }
        });

        /**
         * Creating logic: Auto-assign ownership and generate slug
         */
        static::creating(function ($product) {
            if (auth()->check()) {
                /** @var \App\Models\User $user */
                $user = auth()->user();

                // Always assign the tenant of the creator
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
}