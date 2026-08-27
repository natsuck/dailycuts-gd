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
        'product_image',
        'product_category',
        'product_type',
        'fresh_storage',
        'fresh_prep',
        'fresh_delivery',
        'fresh_best_for',
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

    public function categories()
    {
        return $this->belongsToMany(Category::class)->withTimestamps();
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

    /**
     * Per-product freshness notes (Storage, Prep, Delivery, Best For).
     *
     * Falls back to a generic default for any field left empty so existing
     * products render fully until the admin supplies their own values.
     */
    public function freshnessNotes(): array
    {
        $defaults = [
            'Storage' => 'Keep chilled',
            'Prep' => 'Cook thoroughly',
            'Delivery' => 'Temperature-controlled',
            'Best For' => 'Daily meals',
        ];

        $notes = [
            'Storage' => $this->fresh_storage,
            'Prep' => $this->fresh_prep,
            'Delivery' => $this->fresh_delivery,
            'Best For' => $this->fresh_best_for,
        ];

        foreach ($notes as $key => $value) {
            $notes[$key] = ($value !== null && trim($value) !== '')
                ? $value
                : $defaults[$key];
        }

        return $notes;
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
        // Lock the product row so concurrent adjustments serialize instead of
        // racing on the current quantity (callers should wrap this in a
        // transaction so the lock is held until commit).
        $locked = static::whereKey($this->id)->lockForUpdate()->firstOrFail();
        $before = $locked->product_quantity;

        if ($type === 'sale') {
            $locked->decrement('product_quantity', abs($change));
        } elseif ($type === 'restock') {
            $locked->increment('product_quantity', abs($change));
        } elseif ($type === 'adjustment') {
            $locked->product_quantity = $change;
            $locked->save();
        }

        $locked->refresh();
        $this->product_quantity = $locked->product_quantity;

        return InventoryHistory::create([
            'product_id' => $locked->id,
            'type' => $type,
            'quantity_change' => $change,
            'quantity_before' => $before,
            'quantity_after' => $locked->product_quantity,
            'reference_type' => $refType,
            'reference_id' => $refId,
            'notes' => $notes,
        ]);
    }
}
