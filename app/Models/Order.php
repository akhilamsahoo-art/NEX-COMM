<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Builder;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'address_id',
        'total_amount',
        'total_price',
        'order_status',
        'payment_status',
        'shipment_status',
        'payment_method',
        'shipped_at',
        'tenant_id',
        'paid_at',
    ];

    protected $casts = [
        'shipped_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    // =========================
    // Relationships
    // =========================

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'order_product', 'order_id', 'product_id')
            ->withPivot('quantity')
            ->withTimestamps();
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function cart()
    {
        return $this->hasOne(\App\Models\Cart::class, 'user_id', 'user_id')
                    ->where('is_checked_out', false);
    }

    public function tenant()
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    public function address()
{
    return $this->belongsTo(Address::class);
}

    // =========================
    // Model Boot Logic
    // =========================

    protected static function booted()
    {
        /**
         * Global Order Isolation
         */
        static::addGlobalScope('order_isolation', function (Builder $query) {
            if (!auth()->check()) {
                return;
            }

            /** @var \App\Models\User $user */
            $user = auth()->user();

            // 1. Super Admin: No restriction
            if ($user->isSuperAdmin()) {
                return;
            }

            // 2. Seller: Only see orders for their tenant
            if ($user->role === 'seller') {
                $query->where('orders.tenant_id', $user->tenant_id);
            }

            // 3. Manager: See orders for their tenant OR their managed sellers' tenants
            if ($user->role === 'manager') {
                $query->where(function (Builder $sub) use ($user) {
                    $sub->where('orders.tenant_id', $user->tenant_id)
                        ->orWhereIn('orders.tenant_id', function ($q) use ($user) {
                            $q->select('tenant_id')
                              ->from('users')
                              ->where('manager_id', $user->id);
                        });
                });
            }
        });

        // Auto-assign tenant_id on creation if not manually set
        static::creating(function ($order) {
            if (auth()->check() && empty($order->tenant_id)) {
                $order->tenant_id = auth()->user()->tenant_id;
            }
        });

        static::updating(function ($order) {
            if ($order->isDirty('shipment_status') && $order->shipment_status === 'shipped') {
                $order->shipped_at = now();
            }

            if ($order->isDirty('payment_status') && $order->payment_status === 'paid') {
                $order->paid_at = now();
            }
        });

        static::updated(function ($order) {
            if ($order->wasChanged('order_status') && $order->order_status === 'delivered'){
                Cache::forget('profit_loss_chart_data');
            }
        });
    }
}