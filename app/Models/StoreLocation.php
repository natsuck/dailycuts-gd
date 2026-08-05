<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoreLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'city',
        'phone',
        'lat',
        'lng',
        'is_active',
        'is_pickup',
        'sort_order',
    ];

    protected $casts = [
        'lat' => 'float',
        'lng' => 'float',
        'is_active' => 'boolean',
        'is_pickup' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopePickup(Builder $query): Builder
    {
        return $query->where('is_pickup', true);
    }

    public function fullAddress(): string
    {
        return $this->city ? trim($this->address.', '.$this->city, ', ') : $this->address;
    }

    /**
     * Promote the next active location (by sort order) to pickup.
     */
    public static function promoteNextPickup(): ?self
    {
        $next = static::active()->orderBy('sort_order')->orderBy('id')->first();

        if ($next) {
            $next->forceFill(['is_pickup' => true])->save();
        }

        return $next;
    }

    /**
     * The active pickup location, or the first active location as a fallback.
     */
    public static function activePickup(): ?self
    {
        return static::pickup()->active()->first() ?? static::active()->first();
    }
}
