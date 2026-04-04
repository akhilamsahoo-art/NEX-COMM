<?php

namespace App\Models;

use Filament\Panel;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Role Constants (clean + safe usage)
     */
    const ROLE_SUPER_ADMIN = 'super_admin';
    const ROLE_MANAGER = 'manager';
    const ROLE_SELLER = 'seller';
    const ROLE_CUSTOMER = 'customer';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar_url',
        // 'is_admin',
        'tenant_id',
        'role',
    ];

    /**
     * Hidden attributes
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Casts
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function tenant()
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    /**
     * Filament Avatar
     */
    public function getFilamentAvatarUrl(): ?string
    {
        return $this->avatar_url
            ? Storage::disk('public')->url($this->avatar_url)
            : null;
    }

    /**
     * Filament Panel Access
     */
    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() === 'admin') {
            return in_array($this->role, [
                self::ROLE_SUPER_ADMIN,
                self::ROLE_MANAGER,
                self::ROLE_SELLER,
            ]);
        }
    
        // if ($panel->getId() === 'seller') {
        //     return $this->role === self::ROLE_SELLER;
        // }
    
        return false;
        // dd($panel->getId(), $this->role);
    }
    /**
     * Role Helpers
     */
    public function isSuperAdmin()
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }

    public function isSeller()
    {
        return $this->role === self::ROLE_SELLER;
    }

    public function isManager()
    {
        return $this->role === self::ROLE_MANAGER;
    }
}