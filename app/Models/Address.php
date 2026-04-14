<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Address extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     * These must match the columns in your migration.
     */
    protected $fillable = [
        'user_id',
        'address_line_1',
        'city',
        'state',
        'postal_code',
        'country',
        'is_default',
    ];

    /**
     * Get the user that owns the address.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the orders associated with this specific address.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}