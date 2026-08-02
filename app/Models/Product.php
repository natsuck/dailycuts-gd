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
        'product_type',
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

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function wishlistedBy()
    {
        return $this->hasMany(WishlistedItem::class);
    }

    public function inventoryHistories()
    {
        return $this->hasMany(InventoryHistory::class);
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class)->orderBy('weight');
    }

    public function pairings()
    {
        return $this->belongsToMany(Product::class, 'product_pairings', 'product_id', 'paired_product_id')
            ->withTimestamps();
    }

    public function typeLabel(): string
    {
        return match ($this->product_type) {
            'frozen' => 'Frozen',
            'pantry' => 'Pantry Staples',
            'produce' => 'Fresh Produce',
            default => 'Fresh',
        };
    }

    public function hasVariants(): bool
    {
        return $this->variants()->count() > 0;
    }

    public function averageRating(): float
    {
        return round($this->reviews()->avg('rating') ?? 0, 1);
    }

    public function reviewCount(): int
    {
        return $this->reviews()->count();
    }

    public function inStock(): bool
    {
        return $this->product_quantity > 0;
    }

    public function isLowStock(): bool
    {
        if (! $this->reorder_level) {
            return false;
        }

        return $this->product_quantity <= $this->reorder_level;
    }

    public function isExpiringSoon(int $days = 3): bool
    {
        if (! $this->expiry_date) {
            return false;
        }

        return $this->expiry_date->lte(now()->addDays($days));
    }

    public function recordInventoryChange(string $type, int $change, ?string $notes = null, ?string $refType = null, ?int $refId = null): InventoryHistory
    {
        $before = $this->product_quantity;

        if ($type === 'sale') {
            $this->decrement('product_quantity', abs($change));
        } elseif ($type === 'restock') {
            $this->increment('product_quantity', abs($change));
        } elseif ($type === 'adjustment') {
            $this->product_quantity = $change;
            $this->save();
        }

        $this->refresh();

        return InventoryHistory::create([
            'product_id' => $this->id,
            'type' => $type,
            'quantity_change' => $change,
            'quantity_before' => $before,
            'quantity_after' => $this->product_quantity,
            'reference_type' => $refType,
            'reference_id' => $refId,
            'notes' => $notes,
        ]);
    }
}
