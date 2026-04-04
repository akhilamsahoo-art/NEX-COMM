<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    // Make sure 'order_id' is included in the $fillable array
    protected $fillable = [
        'cart_id',
        'order_id',   // Add order_id here for mass assignment
        'product_id',
        'quantity',
        'price',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    public function order()
{
    return $this->belongsTo(Order::class);
}
// public function items()
// {
//     return $this->hasMany(OrderItem::class);
// }

// public function user()
// {
//     return $this->belongsTo(User::class);
// }



}