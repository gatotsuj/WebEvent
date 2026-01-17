<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Customer extends Model
{
    //
    use HasFactory,Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'birth_date',
        'gender',
        'address',
        'city',
        'province',
        'postal_code',
        'password',
        'is_active',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'birth_date' => 'datetime',
        'is_active' => 'boolean',
        'last_login_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'orders')
            ->withPivot(['quantity', 'unit_price', 'total_price', 'status'])
            ->withTimestamps();
    }


    public function getFullNameAttribute()
    {
        return $this->name;
    }


    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
