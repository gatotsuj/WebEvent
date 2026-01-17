<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    //
    use HasFactory;

    protected  $fillable = [
        'category_id',
        'name',
        'slug',
        'description',
        'short_description',
        'featured_image',
        'gallery',
        'price',
        'discount_price',
        'stock',
        'event_date',
        'event_location',
        'event_time',
        'event_details',
        'status',
        'is_featured',
        'views'
    ];

    protected $casts = [
        'gallery' => 'array',
        'event_details' => 'array',
        'event_date' => 'datetime',
        'event_time' => 'datetime',
        'price' => 'decimal:2',
        'discount_price' => 'decimal:2',
        'is_featured' => 'boolean',
        'views' => 'integer',
        'stock' => 'integer'
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function  customers()
    {
        return $this->belongsTo(Customer::class,'orders')
            ->withPivot(['quantity','unit_price','total_price','status'])
            ->self::withoutTimestamps();
    }

    public function  getCurrentPriceAttribute()
    {
        return $this->discount_price ?? $this->price;
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', 'published')
            ->where('stock', '>', 0)
            ->where('event_date', '>', now());
    }
}
