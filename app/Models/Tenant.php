<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Tenant extends Model
{
    protected $fillable = ['name', 'slug','is_active', 'email', 'logo'];

    public function users()
    {
        return $this->hasMany(\App\Models\User::class);
    }
//     public function users()
// {
//     return $this->hasMany(\App\Models\User::class);
// }

public function products()
{
    return $this->hasMany(\App\Models\Product::class);
}

public function orders()
{
    return $this->hasMany(\App\Models\Order::class);
}
}