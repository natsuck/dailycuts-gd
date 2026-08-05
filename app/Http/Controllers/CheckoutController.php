<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\SavedAddress;
use App\Services\GeocodingService;
use App\Services\InventoryService;
use App\Services\OrderPricingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    public function checkout(OrderPricingService $pricing)
    {
        $cart = Cart::where('user_id', Auth::id())->with(['product', 'variant'])->get();
        $city = old('city');
        $coupon = $this->couponFromSession();
        $totals = $pricing->totalsFromItems($cart, $city, $coupon);
        $hasShippingEstimate = ! empty($city);

        // A fresh idempotency key per checkout page load. It round-trips via a
        // hidden field so a duplicate submission of the same form (double-click,
        // browser resubmit, or retry) reuses the original order instead of
        // creating a second one.
        $idempotencyKey = (string) Str::uuid();
        session(['checkout_idempotency_key' => $idempotencyKey]);

        return view('checkout', [
            'cart' => $cart,
            'total' => $totals['subtotal'],
            'shippingFee' => $hasShippingEstimate ? $totals['shippingFee'] : null,
            'discount' => $totals['discount'],
            'freeShipping' => $totals['freeShipping'],
            'grandTotal' => $hasShippingEstimate ? $totals['grandTotal'] : round($totals['subtotal'] - $totals['discount'], 2),
            'coupon' => $coupon,
            'couponCode' => $coupon?->code,
            'idempotencyKey' => $idempotencyKey,
        ]);
    }

    public function applyCoupon(Request $request, OrderPricingService $pricing)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50'],
        ], [
            'code.required' => 'Please enter a coupon code.',
        ]);

        $code = trim($validated['code']);
        $coupon = Coupon::whereRaw('LOWER(code) = ?', [strtolower($code)])->first();
        $subtotal = $pricing->subtotal(Cart::where('user_id', Auth::id())->with('product')->get());

        if (! $coupon) {
            return response()->json(['success' => false, 'message' => 'That coupon code does not exist.'], 422);
        }

        if (! $coupon->isValid()) {
            return response()->json(['success' => false, 'message' => 'That coupon code is invalid or expired.'], 422);
        }

        if ($subtotal < (float) $coupon->min_order) {
            return response()->json([
                'success' => false,
                'message' => 'This coupon requires a minimum order of '.number_format((float) $coupon->min_order, 2).'.',
            ], 422);
        }

        $totals = $pricing->totalsFromSubtotal($subtotal, null, $coupon);

        session(['coupon_code' => $coupon->code]);

        return response()->json([
            'success' => true,
            'message' => 'Coupon "'.$coupon->code.'" applied.',
            'couponCode' => $coupon->code,
            'discount' => $totals['discount'],
            'freeShipping' => $totals['freeShipping'],
        ]);
    }

    public function removeCoupon()
    {
        session()->forget('coupon_code');

        return response()->json([
            'success' => true,
            'discount' => 0,
            'freeShipping' => false,
        ]);
    }

    public function estimateShipping(Request $request, OrderPricingService $pricing, GeocodingService $geocoder)
    {
        $city = $request->input('city');
        $coupon = $this->couponFromSession();
        $subtotal = $pricing->subtotal(Cart::where('user_id', Auth::id())->with('product')->get());

        if ($coupon && $coupon->isFreeShipping() && $coupon->appliesTo($subtotal)) {
            return response()->json([
                'shippingFee' => 0,
                'source' => 'free_shipping',
            ]);
        }

        if (! $city) {
            return response()->json([
                'shippingFee' => config('shop.shipping.flat_fee', 150),
                'source' => 'flat_rate',
            ]);
        }

        $coords = $this->verifiedCoordinates($request->all())
            ?? $this->deliveryCoordinates($request->all(), $geocoder);

        if (! $pricing->lalamoveConfigured()) {
            return response()->json([
                'shippingFee' => config('shop.shipping.flat_fee', 150),
                'source' => 'flat_rate',
            ]);
        }

        // Request a live quotation so errors are surfaced clearly to the customer.
        $quote = $pricing->quotationForCity(
            $city,
            null,
            null,
            $coords['lat'] ?? null,
            $coords['lng'] ?? null,
            $coords ? $this->deliveryAddress($request->all()) : null,
        );

        if ($quote && isset($quote['priceBreakdown']['total'])) {
            return response()->json([
                'shippingFee' => (float) $quote['priceBreakdown']['total'],
                'source' => 'lalamove',
            ]);
        }

        $message = $pricing->lastQuotationError() ?? 'Unable to get a live delivery rate right now. Please try again.';

        return response()->json([
            'shippingFee' => null,
            'source' => 'error',
            'error' => $message,
        ]);
    }

    public function placeOrder(Request $request, OrderPricingService $pricing, InventoryService $inventory, GeocodingService $geocoder)
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255', 'regex:/^[A-Za-z\s\-\']+$/'],
            'last_name' => ['required', 'string', 'max:255', 'regex:/^[A-Za-z\s\-\']+$/'],
            'address' => ['required', 'string', 'max:255'],
            'address2' => ['nullable', 'string', 'max:255'],
            'barangay' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'region' => ['required', 'string', 'max:255'],
            'postal' => ['required', 'string', 'regex:/^\d{4}$/'],
            'phone' => ['required', 'string', 'regex:/^09\d{9}$/'],
            'email' => ['required', 'email', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'delivery_lat' => ['nullable', 'numeric'],
            'delivery_lng' => ['nullable', 'numeric'],
            'delivery_place_id' => ['nullable', 'string', 'max:255'],
            'formatted_address' => ['nullable', 'string', 'max:500'],
        ], [
            'first_name.regex' => 'First name may only contain letters, spaces, hyphens, and apostrophes.',
            'last_name.regex' => 'Last name may only contain letters, spaces, hyphens, and apostrophes.',
            'postal.regex' => 'Postal code must be exactly 4 digits.',
            'phone.regex' => 'Phone number must start with 09 and be exactly 11 digits (e.g. 09171234567).',
            'email.email' => 'Please enter a valid email address.',
        ]);

        $cart = Cart::where('user_id', Auth::id())->with('product')->get();

        if ($cart->isEmpty()) {
            return redirect()->route('shop')->with('cartMessage', 'Your cart is empty.');
        }

        foreach ($cart as $item) {
            if (! $item->product) {
                throw ValidationException::withMessages([
                    'cart' => 'One or more items in your cart are no longer available.',
                ]);
            }

            $availableStock = $item->variant ? $item->variant->quantity : $item->product->product_quantity;
            if ($item->quantity > $availableStock) {
                throw ValidationException::withMessages([
                    'cart' => $item->displayName().' no longer has enough stock for your requested quantity.',
                ]);
            }
        }

        $subtotal = $pricing->subtotal($cart);
        $coupon = $this->couponFromSession();

        if ($coupon && ! $coupon->appliesTo($subtotal)) {
            session()->forget('coupon_code');

            throw ValidationException::withMessages([
                'coupon' => 'The applied coupon is no longer valid for this order.',
            ]);
        }

        $address = trim($validated['address'].' '.($validated['address2'] ?? ''));
        $fullAddress = trim($address.', '.$validated['barangay'].', '.$validated['city'].', '.$validated['region'].' '.$validated['postal']);
        $coords = $this->verifiedCoordinates($validated)
            ?? $this->deliveryCoordinates($validated, $geocoder);

        // Request the Lalamove quotation (fixed warehouse pickup → customer delivery coords)
        // so the fee shown at checkout matches the quotation used for dispatch later.
        $deliveryAddress = $this->deliveryAddress($validated);
        $warehouse = $pricing->warehouse();
        $quote = $pricing->quotationForCity(
            $validated['city'],
            $pricing->itemPayload($cart),
            null,
            $coords['lat'] ?? null,
            $coords['lng'] ?? null,
            $deliveryAddress,
        );

        $liveShippingFee = $quote ? (float) data_get($quote, 'priceBreakdown.total') : null;
        $totals = $pricing->totals(
            $subtotal,
            $liveShippingFee ?? (float) config('shop.shipping.flat_fee', 150),
            $coupon,
        );

        $lineItems = $cart->map(function ($item) {
            $line = [
                'name' => $item->product->product_title.($item->variant ? ' ('.$item->variant->weight.')' : ''),
                'amount' => (int) round($item->unitPrice() * 100),
                'currency' => 'PHP',
                'quantity' => (int) $item->quantity,
            ];

            if ($item->product->product_image) {
                $imageUrl = secure_asset('products/'.$item->product->product_image);
                $line['images'] = [$imageUrl];
            }

            return $line;
        })->toArray();

        if ($totals['shippingFee'] > 0) {
            $lineItems[] = [
                'name' => 'Delivery Fee',
                'amount' => (int) round($totals['shippingFee'] * 100),
                'currency' => 'PHP',
                'quantity' => 1,
            ];
        }

        $totalAmount = (int) round($totals['grandTotal'] * 100);
        $deliveryPlaceId = $validated['delivery_place_id'] ?? null;

        // Reuse an existing order when the same checkout form is submitted more
        // than once (double-click, browser resubmit, or a retried request).
        $idempotencyKey = trim((string) ($request->input('idempotency_key') ?? session('checkout_idempotency_key', '')));

        $existingOrder = $idempotencyKey !== ''
            ? Order::where('user_id', Auth::id())->where('idempotency_key', $idempotencyKey)->first()
            : null;

        if ($existingOrder) {
            if ($existingOrder->payment_status === 'paid') {
                return redirect()->route('orders.show', $existingOrder->id)
                    ->with('orderMessage', 'You have already paid for this order.');
            }

            if ($existingOrder->checkout_session_url && $existingOrder->status !== 'cancelled') {
                return redirect()->away($existingOrder->checkout_session_url);
            }

            return redirect()->route('checkout')->withErrors([
                'checkout' => 'Your previous order for this cart is still being processed or was cancelled. Please try again.',
            ]);
        }

        $order = null;

        try {
            $order = DB::transaction(function () use ($validated, $fullAddress, $coords, $totals, $cart, $inventory, $coupon, $quote, $warehouse, $deliveryAddress, $deliveryPlaceId, $idempotencyKey) {
                $order = Order::create([
                    'user_id' => Auth::id(),
                    'idempotency_key' => $idempotencyKey !== '' ? $idempotencyKey : null,
                    'name' => $validated['first_name'].' '.$validated['last_name'],
                    'address' => $fullAddress,
                    'barangay' => $validated['barangay'],
                    'region' => $validated['region'],
                    'city' => $validated['city'],
                    'phone' => $validated['phone'],
                    'total' => $totals['grandTotal'],
                    'status' => 'pending',
                    'payment_status' => 'unpaid',
                    'shipping_fee' => $totals['shippingFee'],
                    'discount' => $totals['discount'],
                    'coupon_code' => $coupon?->code,
                    'notes' => $validated['notes'],
                    'quotation_id' => $quote['quotationId'] ?? null,
                    'pickup_stop_id' => data_get($quote, 'stops.0.stopId'),
                    'delivery_stop_id' => data_get($quote, 'stops.1.stopId'),
                    'delivery_status' => $quote ? 'pending' : 'quotation_failed',
                    'pickup_address' => $warehouse['address'] ?? null,
                    'pickup_lat' => $warehouse['lat'] ?? null,
                    'pickup_lng' => $warehouse['lng'] ?? null,
                    'delivery_lat' => $coords['lat'] ?? null,
                    'delivery_lng' => $coords['lng'] ?? null,
                ]);

                // Save the selected Google Places address to the customer's address book.
                if ($deliveryPlaceId) {
                    SavedAddress::updateOrCreate(
                        ['user_id' => Auth::id(), 'place_id' => $deliveryPlaceId],
                        [
                            'formatted_address' => $deliveryAddress,
                            'latitude' => $coords['lat'] ?? null,
                            'longitude' => $coords['lng'] ?? null,
                        ],
                    );
                }

                foreach ($cart as $item) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $item->product_id,
                        'variant_id' => $item->variant_id,
                        'quantity' => $item->quantity,
                        'price' => $item->unitPrice(),
                    ]);
                }

                $inventory->reserve($order, $cart);

                return $order;
            });
        } catch (UniqueConstraintViolationException $e) {
            // A concurrent duplicate submission of the same form won the race;
            // reuse its order instead of creating another one.
            $existing = Order::where('user_id', Auth::id())
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($existing?->checkout_session_url && $existing->status !== 'cancelled') {
                return redirect()->away($existing->checkout_session_url);
            }

            return redirect()->route('checkout')->withErrors([
                'checkout' => 'This order was already submitted. Please try again.',
            ]);
        }

        $secret = base64_encode(config('paymongo.secret').':');

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Basic '.$secret,
                    'Content-Type' => 'application/json',
                ])
                ->post('https://api.paymongo.com/v1/checkout_sessions', [
                    'data' => [
                        'attributes' => [
                            'amount' => $totalAmount,
                            'currency' => 'PHP',
                            'description' => 'Order #'.$order->id,
                            'metadata' => ['order_id' => $order->id],
                            'payment_method_types' => ['gcash', 'card'],
                            'success_url' => url('/checkout/success?order_id='.$order->id),
                            'cancel_url' => url('/checkout/cancel?order_id='.$order->id),
                            'billing' => [
                                'name' => $order->name,
                                'email' => $validated['email'],
                                'phone' => $validated['phone'],
                                'address' => [
                                    'line1' => trim($validated['address'].' '.($validated['address2'] ?? '')),
                                    'line2' => $validated['barangay'],
                                    'city' => $validated['city'],
                                    'state' => $validated['region'],
                                    'postal_code' => $validated['postal'],
                                    'country' => 'PH',
                                ],
                            ],
                            'line_items' => $lineItems,
                        ],
                    ],
                ]);

            $session = $response->json();

            if (! $response->successful() || ! isset($session['data']['id'], $session['data']['attributes']['checkout_url'])) {
                Log::error('PayMongo checkout session creation failed', [
                    'order_id' => $order->id,
                    'status' => $response->status(),
                    'response' => $this->redactPaymentResponse($session),
                ]);

                DB::transaction(function () use ($order, $inventory) {
                    $inventory->release($order);
                    OrderItem::where('order_id', $order->id)->delete();
                    $order->delete();
                });

                return redirect()->route('checkout')->withErrors([
                    'checkout' => 'Payment gateway returned an error. Please try again.',
                ]);
            }

            $order->update([
                'checkout_session_id' => $session['data']['id'],
                'checkout_session_url' => $session['data']['attributes']['checkout_url'],
            ]);

            if ($coupon) {
                DB::transaction(function () use ($coupon, $order, $totals) {
                    $lockedCoupon = Coupon::whereKey($coupon->id)->lockForUpdate()->first();

                    if ($lockedCoupon && $lockedCoupon->usage_limit !== null && $lockedCoupon->used_count >= $lockedCoupon->usage_limit) {
                        throw new \RuntimeException('Coupon usage limit reached.');
                    }

                    Coupon::whereKey($coupon->id)->increment('used_count');

                    CouponUsage::create([
                        'coupon_id' => $coupon->id,
                        'user_id' => Auth::id(),
                        'order_id' => $order->id,
                        'discount_amount' => $totals['discount'],
                    ]);
                });

                session()->forget('coupon_code');
            }

            return redirect()->away($session['data']['attributes']['checkout_url']);

        } catch (\Exception $e) {
            Log::error('PayMongo API connection failed', [
                'order_id' => $order->id,
                'message' => $e->getMessage(),
            ]);

            DB::transaction(function () use ($order, $inventory) {
                $inventory->release($order);
                OrderItem::where('order_id', $order->id)->delete();
                $order->delete();
            });

            return redirect()->route('checkout')->withErrors([
                'checkout' => 'Could not connect to payment gateway. Please try again.',
            ]);
        }
    }

    public function checkoutSuccess(Request $request)
    {
        $orderId = $request->integer('order_id');

        if ($orderId && Order::whereKey($orderId)->where('user_id', Auth::id())->exists()) {
            return redirect()->route('orders.show', $orderId)
                ->with('orderMessage', 'Payment received! We are processing your order.');
        }

        return redirect()->route('shop')
            ->with('orderMessage', 'Payment received! We are processing your order.');
    }

    public function checkoutCancel(Request $request)
    {
        // Deliberately non-destructive: the browser redirect from the payment
        // gateway cannot carry a CSRF token. Abandoned unpaid orders have their
        // stock released via the payment.failed / checkout_session.expired webhook.
        return redirect()->route('shop')
            ->with('orderMessage', 'Payment was cancelled. Please try again.');
    }

    protected function couponFromSession(): ?Coupon
    {
        $code = session('coupon_code');

        if (! $code) {
            return null;
        }

        return Coupon::where('code', $code)->first();
    }

    protected function deliveryCoordinates(array $data, GeocodingService $geocoder): ?array
    {
        return $geocoder->geocode($geocoder->fullAddress($this->addressParts($data)));
    }

    protected function validatedCoordinates(array $data): ?array
    {
        $lat = $data['delivery_lat'] ?? null;
        $lng = $data['delivery_lng'] ?? null;

        if ($lat === null || $lng === null || $lat === '' || $lng === '') {
            return null;
        }

        $lat = (float) $lat;
        $lng = (float) $lng;

        if (! is_finite($lat) || ! is_finite($lng)) {
            return null;
        }

        if ($lat < -10 || $lat > 21 || $lng < 116 || $lng > 127) {
            return null;
        }

        return ['lat' => $lat, 'lng' => $lng];
    }

    /**
     * Only trust client-supplied coordinates when a reverse geocode of them
     * matches the submitted city or formatted address. Otherwise return null
     * so the caller falls back to geocoding the typed address.
     */
    protected function verifiedCoordinates(array $data): ?array
    {
        $coords = $this->validatedCoordinates($data);

        if ($coords === null) {
            return null;
        }

        $city = strtolower(trim($data['city'] ?? ''));
        $formatted = strtolower(trim($data['formatted_address'] ?? ''));

        $reverse = app(GeocodingService::class)->reverseGeocode($coords['lat'], $coords['lng']);

        if (! $reverse) {
            return null;
        }

        $haystack = strtolower(implode(' ', array_filter(array_map('strval', $reverse))));

        if ($city !== '' && str_contains($haystack, $city)) {
            return $coords;
        }

        if ($formatted !== '' && $this->addressesOverlap($formatted, $haystack)) {
            return $coords;
        }

        return null;
    }

    protected function addressesOverlap(string $a, string $b): bool
    {
        $tokens = preg_split('/[^a-z0-9]+/', $a, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        foreach ($tokens as $token) {
            if (strlen($token) >= 4 && str_contains($b, $token)) {
                return true;
            }
        }

        return false;
    }

    protected function fullAddress(array $data): string
    {
        return app(GeocodingService::class)->fullAddress($this->addressParts($data));
    }

    /**
     * Prefer the Google Places formatted address when available, otherwise
     * fall back to the concatenated checkout fields.
     */
    protected function deliveryAddress(array $data): string
    {
        $formatted = trim($data['formatted_address'] ?? '');

        if ($formatted !== '') {
            return $formatted;
        }

        return $this->fullAddress($data);
    }

    protected function addressParts(array $data): array
    {
        return [
            'address' => trim(($data['address'] ?? '').' '.($data['address2'] ?? '')),
            'barangay' => $data['barangay'] ?? '',
            'city' => $data['city'] ?? '',
            'region' => $data['region'] ?? '',
            'postal' => $data['postal'] ?? '',
        ];
    }

    protected function redactPaymentResponse(mixed $session): mixed
    {
        if (! is_array($session)) {
            return $session;
        }

        unset($session['data']['attributes']['billing']);
        unset($session['data']['attributes']['payment_methods']);

        return $session;
    }
}
