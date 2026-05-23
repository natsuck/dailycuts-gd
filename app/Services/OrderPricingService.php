<?php

namespace App\Services;

use Illuminate\Support\Collection;

class OrderPricingService
{
    public function shippingFee(): float
    {
        return (float) config('shop.shipping.flat_fee', 150);
    }

    public function subtotal(Collection $items): float
    {
        return round($items->sum(function ($item) {
            $price = (float) data_get($item, 'product.product_price', 0);
            $quantity = (int) data_get($item, 'quantity', 1);

            return $price * $quantity;
        }), 2);
    }

    public function totalsFromItems(Collection $items): array
    {
        return $this->totalsFromSubtotal($this->subtotal($items));
    }

    public function totalsFromSubtotal(float $subtotal): array
    {
        $shippingFee = $this->shippingFee();

        return [
            'subtotal' => round($subtotal, 2),
            'shippingFee' => $shippingFee,
            'grandTotal' => round($subtotal + $shippingFee, 2),
        ];
    }
}
