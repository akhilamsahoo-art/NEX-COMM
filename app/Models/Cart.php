<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB; // ✅ Imported correctly
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Cart extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'tenant_id', 'is_checked_out'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(CartItem::class, 'cart_id');
    }

    /**
     * Optimized Total Calculation
     */
    public function getTotalAttribute()
    {
        // Using the imported DB facade directly without the backslash
        return (float) $this->items()
            ->join('products', 'cart_items.product_id', '=', 'products.id')
            ->sum(DB::raw('cart_items.quantity * products.price'));
    }

    public function getLastActivityAttribute()
    {
        $date = $this->items()->latest('updated_at')->value('updated_at') 
            ?? $this->created_at;

        return $date ? Carbon::parse($date) : null;
    }

    public function getStatusAttribute()
    {
        $lastActivity = $this->last_activity ?? $this->created_at;

        if (!$lastActivity) {
            return 'Pending';
        }

        return Carbon::parse($lastActivity)->lt(now()->subDays(8))
            ? 'Abandoned'
            : 'Pending';
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}