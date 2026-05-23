<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PendingOrder extends Model
{
    protected $fillable = [
    'user_id',
    'cart_data',
    ];
}
