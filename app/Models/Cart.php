<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    protected $fillable = ['user_id','tenant_id','is_checked_out'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

   public function items()
{
    return $this->hasMany(CartItem::class, 'cart_id');
}
    public function getTotalAttribute()
{
    return $this->items->sum(function ($item) {
        return $item->quantity * $item->product->price;
    });
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
    return $this->belongsTo(\App\Models\Tenant::class);
}
}