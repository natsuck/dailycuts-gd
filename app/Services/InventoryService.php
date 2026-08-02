<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\InventoryHistory;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    public function reserve(Order $order, Collection $cartItems): void
    {
        $items = $cartItems->sortBy(fn (Cart $item) => [$item->product_id, $item->variant_id ?? 0]);

        foreach ($items as $item) {
            if ($item->variant_id) {
                $variant = ProductVariant::lockForUpdate()->find($item->variant_id);

                if (! $variant || $variant->quantity < $item->quantity) {
                    throw ValidationException::withMessages([
                        'cart' => $item->displayName().' no longer has enough stock for your requested quantity.',
                    ]);
                }

                $before = $variant->quantity;
                $variant->decrement('quantity', $item->quantity);
                $variant->refresh();

                $this->logHistory($order, $item, $before, $variant->quantity);
            } else {
                $product = Product::lockForUpdate()->find($item->product_id);

                if (! $product || $product->product_quantity < $item->quantity) {
                    throw ValidationException::withMessages([
                        'cart' => $item->displayName().' no longer has enough stock for your requested quantity.',
                    ]);
                }

                $before = $product->product_quantity;
                $product->decrement('product_quantity', $item->quantity);
                $product->refresh();

                $this->logHistory($order, $item, $before, $product->product_quantity);
            }
        }
    }

    public function release(Order $order): void
    {
        foreach ($order->items as $item) {
            if ($item->variant_id) {
                ProductVariant::whereKey($item->variant_id)->increment('quantity', $item->quantity);
            } else {
                Product::whereKey($item->product_id)->increment('product_quantity', $item->quantity);
            }
        }
    }

    protected function logHistory(Order $order, Cart $item, int $before, int $after): void
    {
        InventoryHistory::create([
            'product_id' => $item->product_id,
            'type' => 'sale',
            'quantity_change' => -$item->quantity,
            'quantity_before' => $before,
            'quantity_after' => $after,
            'reference_type' => Order::class,
            'reference_id' => $order->id,
            'notes' => "Order #{$order->id} stock reserved",
        ]);
    }
}
