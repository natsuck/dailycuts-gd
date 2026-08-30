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
        'notes',
        'idempotency_key',
    ];

    protected $casts = [
        'total' => 'decimal:2',
        'shipping_fee' => 'decimal:2',
        'discount' => 'decimal:2',
        'last_lalamove_webhook_at' => 'datetime',
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

    /**
     * Friendly labels for Lalamove courier statuses.
     *
     * Keys are uppercased so callers can normalize any casing from the API or
     * the webhook, which stores strtolower()'d values.
     *
     * @return array<string, array{short: string, text: string}>
     */
    public static function courierStatusMap(): array
    {
        return [
            'PENDING' => ['short' => 'Pending', 'text' => 'Your order is waiting for a courier.'],
            'PLACED' => ['short' => 'Placed', 'text' => 'Your order has been placed with the courier.'],
            'ASSIGNING_DRIVER' => ['short' => 'Finding Courier', 'text' => 'Looking for a courier...'],
            'DRIVER_ASSIGNED' => ['short' => 'Courier Assigned', 'text' => 'A courier has been assigned.'],
            'ON_GOING' => ['short' => 'On The Way', 'text' => 'Your order is on the way.'],
            'PICKED_UP' => ['short' => 'Picked Up', 'text' => 'Your order has been picked up by the courier.'],
            'COMPLETED' => ['short' => 'Delivered', 'text' => 'Your order has been delivered. Thank you!'],
            'CANCELED' => ['short' => 'Cancelled', 'text' => 'Delivery was cancelled.'],
            'CANCELLED' => ['short' => 'Cancelled', 'text' => 'Delivery was cancelled.'],
            'EXPIRED' => ['short' => 'Expired', 'text' => 'Delivery expired.'],
            'REJECTED' => ['short' => 'Rejected', 'text' => 'Delivery was rejected.'],
            'FAILED' => ['short' => 'Failed', 'text' => 'Delivery failed.'],
        ];
    }

    public function courierStatusKey(): ?string
    {
        $status = $this->lalamove_status ?? $this->delivery_status;

        return $status ? strtoupper((string) $status) : null;
    }

    public function courierStatusShort(): string
    {
        $key = $this->courierStatusKey();
        $map = static::courierStatusMap();

        return ($key && isset($map[$key])) ? $map[$key]['short'] : ($key ?? 'Pending');
    }

    public function courierStatusText(): string
    {
        $key = $this->courierStatusKey();
        $map = static::courierStatusMap();

        return ($key && isset($map[$key])) ? $map[$key]['text'] : 'Courier status: '.($key ?? 'N/A');
    }
}
