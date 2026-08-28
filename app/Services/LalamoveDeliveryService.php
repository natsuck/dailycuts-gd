<?php

namespace App\Services;

use App\Models\Order;
use App\Services\GeocodingService;
use Illuminate\Support\Facades\Log;

class LalamoveDeliveryService
{
    private ?string $lastError = null;

    public function __construct(
        private OrderPricingService $pricing,
        private LalamoveService $lalamove,
        private GeocodingService $geocoder,
    ) {}

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    /**
     * Create the Lalamove order for a paid order.
     *
     * The quotation captured at checkout is preferred; if it has expired or was
     * already consumed, a fresh quotation is requested as a fallback.
     */
    public function dispatch(Order $order): bool
    {
        if ($order->lalamove_order_id) {
            return true;
        }

        $item = $this->pricing->itemPayload($order->items);
        $warehouse = $this->pricing->warehouse();
        $specialRequests = config('shop.shipping.lalamove_special_requests', ['THERMAL_BAG_1']);

        if ($order->quotation_id && $order->pickup_stop_id && $order->delivery_stop_id) {
            if ($this->createFromStoredQuotation($order, $warehouse)) {
                return true;
            }
        }

        // Fallback: request a fresh quotation using the saved delivery
        // coordinates, resolving them from the address when they are missing
        // (e.g. geocoding failed at checkout or a postal code slipped into the
        // city field). This keeps dispatch self-healing instead of silently
        // pinning an order that Lalamove could still fulfil.
        [$dropoffLat, $dropoffLng] = $this->deliveryCoords($order);

        $dropoffAddress = trim(implode(', ', array_filter([
            $order->address,
            $order->barangay,
            $order->city,
            $order->region,
        ])));

        $quotation = $this->pricing->quotationForCity(
            $order->city,
            $item,
            $specialRequests,
            $dropoffLat,
            $dropoffLng,
            $dropoffAddress !== '' ? $dropoffAddress : null,
        );

        if (! $quotation
            || empty($quotation['quotationId'])
            || ! isset($quotation['stops'][0]['stopId'], $quotation['stops'][1]['stopId'])) {
            $this->lastError = $this->pricing->lastQuotationError()
                ?? $this->lalamove->getLastError()
                ?? 'No valid Lalamove quotation for city "'.$order->city.'" (could not resolve delivery coordinates from the address)';

            return false;
        }

        $freshPrice = (float) data_get($quotation, 'priceBreakdown.total', 0);
        $checkoutPrice = (float) $order->shipping_fee;

        if ($checkoutPrice > 0 && $freshPrice > 0) {
            $diff = round($freshPrice - $checkoutPrice, 2);
            $pct = round(($diff / $checkoutPrice) * 100, 1);

            Log::warning('Lalamove fresh quotation price differs from checkout', [
                'order_id' => $order->id,
                'checkout_shipping_fee' => $checkoutPrice,
                'fresh_shipping_fee' => $freshPrice,
                'difference' => $diff,
                'difference_pct' => $pct,
            ]);
        }

        $sender = $this->senderFromStop($quotation['stops'][0]['stopId'], $warehouse);
        $recipients = [$this->recipientFromStop($quotation['stops'][1]['stopId'], $order)];

        $result = $this->lalamove->createOrder(
            $quotation['quotationId'],
            $sender,
            $recipients,
        );

        return $this->recordOrder($order, $result, $quotation['quotationId']);
    }

    protected function createFromStoredQuotation(Order $order, ?array $warehouse): bool
    {
        $sender = $this->senderFromStop($order->pickup_stop_id, $warehouse);
        $recipients = [$this->recipientFromStop($order->delivery_stop_id, $order)];

        $result = $this->lalamove->createOrder(
            $order->quotation_id,
            $sender,
            $recipients,
        );

        if (! $result || empty($result['orderId'])) {
            $this->lastError = $this->lalamove->getLastError() ?? 'Lalamove did not return an order id';

            Log::warning('Lalamove order creation failed using stored quotation', [
                'order_id' => $order->id,
                'quotation_id' => $order->quotation_id,
                'error' => $this->lastError,
            ]);

            return false;
        }

        return $this->recordOrder($order, $result, $order->quotation_id);
    }

    protected function senderFromStop(string $stopId, ?array $warehouse): array
    {
        return [
            'stopId' => $stopId,
            'name' => $warehouse['name'] ?? config('shop.store.name', 'Store'),
            'phone' => $this->lalamove->formatPhone(
                $warehouse['phone'] ?? config('shop.store.branch_phone', '+639000000000')
            ),
        ];
    }

    protected function recipientFromStop(string $stopId, Order $order): array
    {
        return [
            'stopId' => $stopId,
            'name' => $order->name,
            'phone' => $this->lalamove->formatPhone($order->phone),
        ];
    }

    /**
     * Resolve the delivery coordinates used for a fresh quotation.
     *
     * Returns the order's saved coordinates when present. Otherwise it tries to
     * geocode the full delivery address from the order, and persists the result
     * so a later retry does not have to geocode again.
     *
     * @return array{0: float|null, 1: float|null}
     */
    protected function deliveryCoords(Order $order): array
    {
        if ($order->delivery_lat !== null && $order->delivery_lng !== null) {
            return [(float) $order->delivery_lat, (float) $order->delivery_lng];
        }

        $address = trim(implode(', ', array_filter([
            $order->address,
            $order->barangay,
            $order->city,
            $order->region,
        ])));

        if ($address === '') {
            return [null, null];
        }

        $coords = $this->geocoder->geocode($address);

        if (! $coords) {
            return [null, null];
        }

        Log::info('Lalamove dispatch geocoded missing delivery coordinates', [
            'order_id' => $order->id,
            'lat' => $coords['lat'],
            'lng' => $coords['lng'],
        ]);

        $order->delivery_lat = $coords['lat'];
        $order->delivery_lng = $coords['lng'];
        $order->save();

        return [(float) $coords['lat'], (float) $coords['lng']];
    }

    protected function recordOrder(Order $order, ?array $result, ?string $quotationId): bool
    {
        if (! $result || empty($result['orderId'])) {
            $this->lastError = $this->lalamove->getLastError() ?? 'Lalamove did not return an order id';

            Log::warning('Lalamove dispatch failed', [
                'order_id' => $order->id,
                'error' => $this->lastError,
                'response' => $result,
            ]);

            return false;
        }

        $order->lalamove_order_id = $result['orderId'];
        $order->quotation_id = $quotationId ?: $order->quotation_id;
        $order->lalamove_status = $result['status'] ?? null;
        $order->delivery_status = $result['status'] ?? $order->delivery_status;
        $order->tracking_url = data_get($result, 'shareLink');
        $order->save();

        Log::info('Lalamove order created', [
            'order_id' => $order->id,
            'lalamove_order_id' => $order->lalamove_order_id,
            'status' => $order->lalamove_status,
            'tracking_url' => $order->tracking_url,
        ]);

        return true;
    }
}
