<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    // These are the fields Laravel is allowed to 'mass-assign'
    protected $fillable = [
        'product_id',
        'user_id',
        'rating',
        'comment',
        'sentiment',    
        'ai_summary',   
        'key_features', 
    ];

    // Important for storing the AI tags correctly
    protected $casts = [
        'key_features' => 'array',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
//     protected static function booted()
// {
//     static::creating(function ($model) {
//         if (auth()->check()) {
//             $model->tenant_id = auth()->user()->tenant_id;
//         }
//     });

    // static::addGlobalScope('tenant', function ($query) {
    //     if (auth()->check() && auth()->user()->tenant_id && !auth()->user()->isSuperAdmin()) {
    //         $query->where('tenant_id', auth()->user()->tenant_id);
    //     }
    // });
// }
}