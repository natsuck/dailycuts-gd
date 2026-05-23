<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_title',
        'product_description',
        'product_quantity',
        'reorder_level',
        'expiry_date',
        'product_price',
        'product_image',
        'product_category',
    ];

    protected $casts = [
        'product_quantity' => 'integer',
        'reorder_level' => 'integer',
        'expiry_date' => 'date',
        'product_price' => 'decimal:2',
    ];

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
}
