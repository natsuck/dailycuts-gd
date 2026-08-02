<?php

namespace App\Services;

use App\Models\Coupon;
use Illuminate\Support\Collection;

class OrderPricingService
{
    private LalamoveService $lalamove;

    private string $lastShippingSource = 'flat_rate';

    public function __construct(LalamoveService $lalamove)
    {
        $this->lalamove = $lalamove;
    }

    public function getLastShippingSource(): string
    {
        return $this->lastShippingSource;
    }

    public function shippingFee(?string $city = null): float
    {
        if ($city && $this->lalamove->isConfigured()) {
            $pickup = $this->nearestBranch($city);

            $matchedCityCoords = $this->matchCityCoords($city);

            if ($pickup && $matchedCityCoords) {
                $quote = $this->lalamove->getQuotation(
                    pickupLat: $pickup['lat'],
                    pickupLng: $pickup['lng'],
                    pickupAddress: $pickup['address'],
                    dropoffLat: $matchedCityCoords['lat'],
                    dropoffLng: $matchedCityCoords['lng'],
                    dropoffAddress: $city.', Philippines',
                    serviceType: config('shop.shipping.lalamove_service_type', 'MOTORCYCLE'),
                );

                if ($quote && isset($quote['priceBreakdown']['total'])) {
                    $this->lastShippingSource = 'lalamove';

                    return (float) $quote['priceBreakdown']['total'] / 100;
                }
            }
        }

        $this->lastShippingSource = 'flat_rate';

        return (float) config('shop.shipping.flat_fee', 150);
    }

    public function subtotal(Collection $items): float
    {
        return round($items->sum(function ($item) {
            if (method_exists($item, 'unitPrice')) {
                $price = $item->unitPrice();
            } else {
                $price = (float) data_get($item, 'product.product_price', 0);
            }
            $quantity = (int) data_get($item, 'quantity', 1);

            return $price * $quantity;
        }), 2);
    }

    public function totalsFromItems(Collection $items, ?string $city = null, ?Coupon $coupon = null): array
    {
        return $this->totalsFromSubtotal($this->subtotal($items), $city, $coupon);
    }

    public function totalsFromSubtotal(float $subtotal, ?string $city = null, ?Coupon $coupon = null): array
    {
        $shippingFee = $this->shippingFee($city);
        $discount = 0.0;
        $freeShipping = false;

        if ($coupon && $coupon->appliesTo($subtotal)) {
            if ($coupon->isFreeShipping()) {
                $shippingFee = 0;
                $freeShipping = true;
            } else {
                $discount = $coupon->calculateDiscount($subtotal);
            }
        }

        return [
            'subtotal' => round($subtotal, 2),
            'discount' => round($discount, 2),
            'shippingFee' => $shippingFee,
            'freeShipping' => $freeShipping,
            'grandTotal' => round($subtotal - $discount + $shippingFee, 2),
        ];
    }

    private function nearestBranch(string $city): ?array
    {
        $branches = config('shop.store.branches', []);

        $cityCoords = $this->matchCityCoords($city);

        if (! $cityCoords || $branches === []) {
            return $branches[0] ?? null;
        }

        $nearest = null;
        $minDist = PHP_FLOAT_MAX;

        foreach ($branches as $branch) {
            if (! isset($branch['lat'], $branch['lng'])) {
                continue;
            }

            $dist = sqrt(
                pow($branch['lat'] - $cityCoords['lat'], 2) +
                pow($branch['lng'] - $cityCoords['lng'], 2)
            );

            if ($dist < $minDist) {
                $minDist = $dist;
                $nearest = $branch;
            }
        }

        return $nearest ?? $branches[0] ?? null;
    }

    private function matchCityCoords(string $input): ?array
    {
        $normalized = strtolower(trim($input));
        $cities = config('shop.metro_manila_cities', []);

        foreach ($cities as $cityName => $coords) {
            if (strtolower($cityName) === $normalized) {
                return $coords;
            }
        }

        foreach ($cities as $cityName => $coords) {
            if (str_contains(strtolower($cityName), $normalized) || str_contains($normalized, strtolower($cityName))) {
                return $coords;
            }
        }

        return null;
    }
}
