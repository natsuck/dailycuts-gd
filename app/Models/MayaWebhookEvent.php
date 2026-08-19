<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MayaWebhookEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_id',
        'event',
        'order_id',
        'request_reference_number',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
