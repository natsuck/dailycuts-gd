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

    public function lalamoveConfigured(): bool
    {
        return $this->lalamove->isConfigured();
    }

    public function lastQuotationError(): ?string
    {
        return $this->lalamove->getLastError();
    }

    /**
     * The fixed pickup location used as the origin for every delivery.
     *
     * Admin-managed store location wins; config/.env warehouse is the fallback.
     */
    public function warehouse(): ?array
    {
        $location = \App\Models\StoreLocation::pickup()->active()->first();

        if ($location) {
            return [
                'name' => $location->name,
                'phone' => $location->phone,
                'address' => $location->fullAddress(),
                'lat' => $location->lat,
                'lng' => $location->lng,
            ];
        }

        $warehouse = config('shop.store.warehouse', []);

        if (isset($warehouse['lat'], $warehouse['lng'], $warehouse['address'])) {
            return $warehouse;
        }

        return config('shop.store.branches')[0] ?? null;
    }

    public function itemPayload(Collection $items, int $defaultQuantity = 1): array
    {
        $quantity = (int) $items->sum('quantity');

        if ($quantity < 1) {
            $quantity = $defaultQuantity;
        }

        return [
            'quantity' => (string) $quantity,
            'weight' => config('shop.shipping.lalamove_item_weight', 'LESS_THAN_20_KG'),
            'categories' => config('shop.shipping.lalamove_item_categories', ['FOOD_AND_BEVERAGES']),
            'handlingInstructions' => config('shop.shipping.lalamove_item_handling', ['KEEP_DRY']),
        ];
    }

    public function quotationForCity(?string $city, ?array $item = null, ?array $specialRequests = null, ?float $dropoffLat = null, ?float $dropoffLng = null, ?string $dropoffAddress = null): ?array
    {
        if (! $city || ! $this->lalamove->isConfigured()) {
            return null;
        }

        $pickup = $this->warehouse() ?? $this->nearestBranch($city);

        if (! $pickup) {
            return null;
        }

        if ($dropoffLat !== null && $dropoffLng !== null) {
            $dropoff = ['lat' => $dropoffLat, 'lng' => $dropoffLng];
        } else {
            $dropoff = $this->matchCityCoords($city);
        }

        if (! $dropoff) {
            return null;
        }

        return $this->lalamove->getQuotation(
            pickupLat: $pickup['lat'],
            pickupLng: $pickup['lng'],
            pickupAddress: $pickup['address'],
            dropoffLat: $dropoff['lat'],
            dropoffLng: $dropoff['lng'],
            dropoffAddress: $dropoffAddress ?? $city.', Philippines',
            serviceType: config('shop.shipping.lalamove_service_type', 'MOTORCYCLE'),
            item: $item ?? $this->itemPayload(collect()),
            specialRequests: $specialRequests ?? config('shop.shipping.lalamove_special_requests', ['THERMAL_BAG_1']),
        );
    }

    public function shippingFee(?string $city = null, ?float $dropoffLat = null, ?float $dropoffLng = null, ?string $dropoffAddress = null): float
    {
        $quote = $this->quotationForCity($city, null, null, $dropoffLat, $dropoffLng, $dropoffAddress);

        if ($quote && isset($quote['priceBreakdown']['total'])) {
            $this->lastShippingSource = 'lalamove';

            return (float) $quote['priceBreakdown']['total'];
        }

        $this->lastShippingSource = 'fallback_tier';

        return $this->fallbackShippingFee($dropoffLat, $dropoffLng, $city);
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

    /**
     * Build order totals from a known subtotal and shipping fee.
     * Used when the caller already has a live quotation and wants to avoid
     * issuing a second Lalamove request.
     */
    public function totals(float $subtotal, float $shippingFee, ?Coupon $coupon = null): array
    {
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
            'shippingFee' => round($shippingFee, 2),
            'freeShipping' => $freeShipping,
            'grandTotal' => round($subtotal - $discount + $shippingFee, 2),
        ];
    }

    public function totalsFromSubtotal(float $subtotal, ?string $city = null, ?Coupon $coupon = null): array
    {
        return $this->totals($subtotal, $this->shippingFee($city), $coupon);
    }

    public function nearestBranch(string $city): ?array
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

            $dist = $this->haversineDistance($branch['lat'], $branch['lng'], $cityCoords['lat'], $cityCoords['lng']);

            if ($dist < $minDist) {
                $minDist = $dist;
                $nearest = $branch;
            }
        }

        return $nearest ?? $branches[0] ?? null;
    }

    private function fallbackShippingFee(?float $dropoffLat = null, ?float $dropoffLng = null, ?string $city = null): float
    {
        $warehouse = $this->warehouse();

        if ($warehouse && $dropoffLat !== null && $dropoffLng !== null) {
            $distanceKm = $this->haversineDistance($warehouse['lat'], $warehouse['lng'], $dropoffLat, $dropoffLng);

            $tiers = config('shop.shipping.fallback_tiers', [
                ['max_km' => 5, 'fee' => 100],
                ['max_km' => 15, 'fee' => 150],
                ['max_km' => 30, 'fee' => 200],
                ['max_km' => PHP_FLOAT_MAX, 'fee' => 250],
            ]);

            foreach ($tiers as $tier) {
                if ($distanceKm <= $tier['max_km']) {
                    return (float) $tier['fee'];
                }
            }
        }

        return (float) config('shop.shipping.flat_fee', 150);
    }

    public function haversineDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadiusKm = 6371;

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
            * sin($dLng / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadiusKm * $c;
    }

    private function matchCityCoords(string $input): ?array
    {
        $normalized = $this->normalizeAccent(strtolower(trim($input)));
        $cities = config('shop.coverage_cities', []);

        foreach ($cities as $cityName => $coords) {
            if ($this->normalizeAccent(strtolower($cityName)) === $normalized) {
                return $coords;
            }
        }

        if (mb_strlen($normalized) >= 3) {
            foreach ($cities as $cityName => $coords) {
                $normalizedCity = $this->normalizeAccent(strtolower($cityName));

                if (str_contains($normalizedCity, $normalized) || str_contains($normalized, $normalizedCity)) {
                    return $coords;
                }
            }
        }

        return null;
    }

    private function normalizeAccent(string $string): string
    {
        $transliterated = function_exists('transliterator_transliterate')
            ? transliterator_transliterate('Any-Latin; Latin-ASCII;', $string)
            : false;

        if ($transliterated !== false) {
            return $transliterated;
        }

        $chars = [
            'À'=>'A','Á'=>'A','Â'=>'A','Ã'=>'A','Ä'=>'A','Å'=>'A',
            'Æ'=>'AE','Ç'=>'C','È'=>'E','É'=>'E','Ê'=>'E','Ë'=>'E',
            'Ì'=>'I','Í'=>'I','Î'=>'I','Ï'=>'I','Ð'=>'D','Ñ'=>'N',
            'Ò'=>'O','Ó'=>'O','Ô'=>'O','Õ'=>'O','Ö'=>'O','Ø'=>'O',
            'Ù'=>'U','Ú'=>'U','Û'=>'U','Ü'=>'U','Ý'=>'Y','Þ'=>'TH',
            'ß'=>'ss',
            'à'=>'a','á'=>'a','â'=>'a','ã'=>'a','ä'=>'a','å'=>'a',
            'æ'=>'ae','ç'=>'c','è'=>'e','é'=>'e','ê'=>'e','ë'=>'e',
            'ì'=>'i','í'=>'i','î'=>'i','ï'=>'i','ð'=>'d','ñ'=>'n',
            'ò'=>'o','ó'=>'o','ô'=>'o','õ'=>'o','ö'=>'o','ø'=>'o',
            'ù'=>'u','ú'=>'u','û'=>'u','ü'=>'u','ý'=>'y','þ'=>'th',
            'ÿ'=>'y',
        ];

        return strtr($string, $chars);
    }
}
