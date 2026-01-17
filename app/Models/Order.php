<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    //
    use HasFactory;

    protected $fillable = [
        'order_number',
        'customer_id',
        'product_id',
        'quantity',
        'unit_price',
        'total_price',
        'discount_amount',
        'final_amount',
        'status',
        'payment_status',
        'payment_method',
        'payment_reference',
        'payment_date',
        'customer_details',
        'payment_details',
        'notes',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'final_amount' => 'decimal:2',
        'payment_date' => 'datetime',
        'customer_details' => 'array',
        'payment_details' => 'array',
        'quantity' => 'integer',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Boot method untuk generate order number otomatis
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            if (empty($order->order_number)) {
                $order->order_number = 'ORD-' . date('Ymd') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
            }
        });
    }


    public function scopePaid($query)
    {
        return $query->where('payment_status', 'paid');
    }


    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
