<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_admin',
        'email_verified_at',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_admin' => 'boolean',
        'last_login_at' => 'datetime',
    ];

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasAnyRole(['admin', 'product_manager', 'sales_staff'])
               && $this->hasVerifiedEmail();
    }

    public function isAdmin()
    {
        return $this->hasRole('admin');
    }

    public function isSuperAdmin()
    {
        return $this->hasRole('admin') && $this->is_admin === true;
    }

    public function canManageProducts()
    {
        return $this->hasAnyRole(['admin', 'product_manager']);
    }

    public function canManageOrders()
    {
        return $this->hasAnyRole(['admin', 'sales_staff']);
    }

    public function canViewReports()
    {
        return $this->hasRole('admin');
    }

    public function canManageUsers()
    {
        return $this->hasRole('admin');
    }
}
