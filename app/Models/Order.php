<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'total_amount',
        'total_price',
        'order_status',
        'payment_status',
        'shipment_status',
        'payment_method',
        'shipped_at',
        'tenant_id',
    ];

    protected $casts = [
        'shipped_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'order_product', 'order_id', 'product_id')
            ->withPivot('quantity') // CRITICAL: Allows counting actual units sold
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

    protected static function booted()
    {

//    static::creating(function ($model) {
//         // Only auto-assign if the tenant_id hasn't been manually set yet
//         if (auth()->check() && !$model->tenant_id) {
//             $model->tenant_id = auth()->user()->tenant_id;
//         }
    // });
        // Logic BEFORE the database saves
        static::updating(function ($order) {
            if ($order->isDirty('shipment_status') && $order->shipment_status === 'shipped') {
                $order->shipped_at = now();
            }

            if ($order->isDirty('payment_status') && $order->payment_status === 'paid') {
                $order->paid_at = now();
            }
        });
        // static::addGlobalScope('tenant', function ($query) {
        //     if (auth()->check() && auth()->user()->tenant_id && !auth()->user()->isSuperAdmin()) {
        //         $query->where('tenant_id', auth()->user()->tenant_id);
        //     }
        // });

        // Logic AFTER the database saves (Clears the "Buffering" Chart Cache)
        static::updated(function ($order) {
            if ($order->wasChanged('order_status') && $order->order_status === 'delivered'){
                // This forces the Profit Chart to show the new data instantly
                Cache::forget('profit_loss_chart_data');
            }
        });
    }
    public function tenant()
{
    return $this->belongsTo(\App\Models\Tenant::class);
}
}