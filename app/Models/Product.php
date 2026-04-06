<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
    ];

    // Relationships
    public function orders()
    {
        return $this->belongsToMany(Order::class, 'order_product', 'product_id', 'order_id')
                    ->withPivot('quantity')
                    ->withTimestamps();
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
    public function seller()
{
    return $this->belongsTo(\App\Models\User::class, 'tenant_id');
}

    // Global scope for tenant
    protected static function booted()
    {
        static::addGlobalScope('tenant', function ($query) {
            if (auth()->check() && auth()->user()->role === 'seller') {
                $query->where('tenant_id', auth()->user()->tenant_id);
            }
        });

        static::creating(function ($product) {
            if (auth()->check()) {
                $product->tenant_id = auth()->user()->tenant_id;
            }

            // Generate slug automatically
            if (empty($product->slug) && !empty($product->name)) {
                $product->slug = Str::slug($product->name);
            }
        });
    }
}