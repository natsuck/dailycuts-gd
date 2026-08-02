<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Collection;

class ProductPairingService
{
    public function forProduct(Product $product, int $limit = 8): Collection
    {
        $pairings = $product->pairings()
            ->with('variants')
            ->where('products.id', '!=', $product->id)
            ->get();

        return $pairings->isNotEmpty()
            ? $pairings->take($limit)
            : $this->fallback([$product->id], $limit);
    }

    public function forCart(Collection $cartItems, int $limit = 8): Collection
    {
        $cartProductIds = $cartItems
            ->pluck('product_id')
            ->filter()
            ->unique()
            ->values();

        if ($cartProductIds->isEmpty()) {
            return $this->fallback([], $limit);
        }

        $productIds = $cartItems
            ->pluck('product')
            ->filter()
            ->pluck('id')
            ->merge($cartProductIds)
            ->unique()
            ->values();

        $pairings = Product::whereIn('id', $productIds)
            ->with('pairings')
            ->get()
            ->flatMap(fn (Product $product) => $product->pairings)
            ->whereNotIn('id', $cartProductIds)
            ->unique('id')
            ->values();

        if ($pairings->isEmpty()) {
            return $this->fallback($cartProductIds->toArray(), $limit);
        }

        return $pairings->take($limit)
            ->each(fn (Product $pairing) => $pairing->load('variants'));
    }

    private function fallback(array $excludeIds, int $limit): Collection
    {
        return Product::with('variants')
            ->whereNotNull('product_image')
            ->when($excludeIds, function ($query, $ids) {
                $query->whereNotIn('id', $ids);
            })
            ->latest()
            ->take($limit)
            ->get();
    }
}
