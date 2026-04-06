<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'tenant_id', // ✅ ADD THIS
    ];

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function tenant()
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }
    public function seller()
{
    return $this->belongsTo(\App\Models\User::class, 'tenant_id');
}

    protected static function booted()
{
    static::addGlobalScope('tenant', function ($query) {
        if (auth()->check() && auth()->user()->role === 'seller') {
            $query->where('tenant_id', auth()->user()->tenant_id);
        }
    });

    static::creating(function ($category) {
        if (auth()->check()) {
            $category->tenant_id = auth()->user()->tenant_id;
        }
    });
}
}