<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'tenant_id', 
        'user_id', // Assigned Seller
    ];

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    // ✅ FIX 1: Point to user_id, NOT tenant_id
    public function seller()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    protected static function booted()
    {
        // ✅ FIX 2: Correct Global Scope Logic
        static::addGlobalScope('tenant_and_seller', function ($query) {
            if (auth()->check()) {
                $user = auth()->user();

                // If Manager: see all categories in their store/tenant
                if ($user->role === 'manager') {
                    $query->where('tenant_id', $user->tenant_id);
                }

                // If Seller: ONLY see categories assigned to them personally
                if ($user->role === 'seller') {
                    $query->where('user_id', $user->id);
                }
            }
        });

        static::creating(function ($category) {
            if (auth()->check()) {
                $category->tenant_id = auth()->user()->tenant_id;
                
                // If a seller is creating it, automatically set the user_id
                if (auth()->user()->role === 'seller') {
                    $category->user_id = auth()->id();
                }
            }
        });
    }
}