<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'address',
        'barangay',
        'region',
        'city',
        'phone',
        'total',
        'status',
        'checkout_session_id',
        'checkout_session_url',
        'idempotency_key',
        'payment_status',
        'payment_method',
        'payment_intent_id',
        'lalamove_order_id',
        'lalamove_status',
        'quotation_id',
        'pickup_stop_id',
        'delivery_stop_id',
        'delivery_status',
        'tracking_url',
        'pickup_address',
        'pickup_lat',
        'pickup_lng',
        'delivery_lat',
        'delivery_lng',
        'shipping_fee',
        'discount',
        'coupon_code',
        'notes',
    ];

    protected $casts = [
        'total' => 'decimal:2',
        'shipping_fee' => 'decimal:2',
        'discount' => 'decimal:2',
    ];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function couponUsages()
    {
        return $this->hasMany(CouponUsage::class);
    }

    public function subtotal(): float
    {
        return round($this->items->sum(function ($item) {
            return $item->price * $item->quantity;
        }), 2);
    }
}
