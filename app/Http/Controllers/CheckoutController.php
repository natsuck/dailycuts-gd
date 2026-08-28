<?php

namespace App\Http\Controllers;

use App\Jobs\DispatchLalamoveDelivery;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\AddressNormalizer;
use App\Services\GeocodingService;
use App\Services\InventoryService;
use App\Services\MayaReconciliationService;
use App\Services\OrderPricingService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

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

        $coords = $this->deliveryCoordinates($request->all(), $geocoder);

        if (! $pricing->lalamoveConfigured()) {
            return response()->json([
                'shippingFee' => config('shop.shipping.flat_fee', 150),
                'source' => 'flat_rate',
            ]);
        }

        // Request a live quotation so errors are surfaced clearly to the customer.
        $cart = Cart::where('user_id', Auth::id())->with('product')->get();
        $quote = $pricing->quotationForCity(
            $city,
            $pricing->itemPayload($cart),
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
            'city' => ['required', 'string', 'max:255', 'regex:/[A-Za-zÀ-ÿ]/'],
            'province' => ['nullable', 'string', 'max:255'],
            'region' => ['required', 'string', 'max:255'],
            'postal' => ['required', 'string', 'regex:/^\d{4}$/'],
            'phone' => ['required', 'string', 'regex:/^09\d{9}$/'],
            'email' => ['required', 'email', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'first_name.regex' => 'First name may only contain letters, spaces, hyphens, and apostrophes.',
            'last_name.regex' => 'Last name may only contain letters, spaces, hyphens, and apostrophes.',
            'city.regex' => 'Please enter a valid city name, not a postal code.',
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

        // Browser address autofill or manual typing often repeats the street or
        // appends the city/region; collapse those before persisting so orders,
        // saved addresses, and delivery dispatch get one clean street line.
        $validated['address'] = app(AddressNormalizer::class)->line(
            $validated['address'],
            $validated['city'] ?? '',
            $validated['region'] ?? '',
        );

        $address = trim($validated['address'].' '.($validated['address2'] ?? ''));
        $fullAddress = trim($address.', '.$validated['barangay'].', '.$validated['city'].', '.$validated['region'].' '.$validated['postal']);
        $coords = $this->deliveryCoordinates($validated, $geocoder);

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
            $unitPrice = round((float) $item->unitPrice(), 2);

            return [
                'name' => $item->product->product_title.($item->variant ? ' ('.$item->variant->weight.')' : ''),
                'quantity' => (string) $item->quantity,
                // Maya's validator requires numeric values, not strings.
                'amount' => ['value' => $unitPrice],
                'totalAmount' => ['value' => round($unitPrice * $item->quantity, 2)],
            ];
        })->values()->toArray();

        if ($totals['shippingFee'] > 0) {
            $shippingFee = round((float) $totals['shippingFee'], 2);
            $lineItems[] = [
                'name' => 'Delivery Fee',
                'quantity' => '1',
                'amount' => ['value' => $shippingFee],
                'totalAmount' => ['value' => $shippingFee],
            ];
        }

        $totalAmount = round((float) $totals['grandTotal'], 2);

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
            $order = DB::transaction(function () use ($validated, $fullAddress, $coords, $totals, $cart, $inventory, $coupon, $quote, $warehouse, $deliveryAddress, $idempotencyKey) {
                $order = new Order();
                $order->user_id = Auth::id();
                $order->idempotency_key = $idempotencyKey !== '' ? $idempotencyKey : null;
                $order->name = $validated['first_name'].' '.$validated['last_name'];
                $order->address = $fullAddress;
                $order->barangay = $validated['barangay'];
                $order->region = $validated['region'];
                $order->city = $validated['city'];
                $order->phone = $validated['phone'];
                $order->total = $totals['grandTotal'];
                $order->status = 'pending';
                $order->payment_status = 'unpaid';
                $order->shipping_fee = $totals['shippingFee'];
                $order->discount = $totals['discount'];
                $order->coupon_code = $coupon?->code;
                $order->notes = $validated['notes'];
                $order->quotation_id = $quote['quotationId'] ?? null;
                $order->pickup_stop_id = data_get($quote, 'stops.0.stopId');
                $order->delivery_stop_id = data_get($quote, 'stops.1.stopId');
                $order->delivery_status = $quote ? 'pending' : 'quotation_failed';
                $order->pickup_address = $warehouse['address'] ?? null;
                $order->pickup_lat = $warehouse['lat'] ?? null;
                $order->pickup_lng = $warehouse['lng'] ?? null;
                $order->delivery_lat = $coords['lat'] ?? null;
                $order->delivery_lng = $coords['lng'] ?? null;
                $order->save();

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

        $basicAuth = base64_encode(config('maya.public_key').':');

        $attempts = max(1, (int) config('maya.checkout_retries', 3));
        $retryDelayMs = max(0, (int) config('maya.checkout_retry_delay_ms', 500));

        $response = null;
        $session = null;
        $requestReference = null;
        $transientFailure = false;

        try {
            for ($attempt = 1; $attempt <= $attempts; $attempt++) {
                // Each attempt needs a fresh request reference number; Maya
                // rejects reused ones.
                $requestReference = (string) Str::uuid();

                try {
                    $response = Http::timeout(30)
                        ->withHeaders([
                            'Authorization' => 'Basic '.$basicAuth,
                            'Content-Type' => 'application/json',
                            'Accept' => 'application/json',
                        ])
                        ->post(config('maya.base_url').'/checkout/v1/checkouts', [
                            'totalAmount' => [
                                'value' => $totalAmount,
                                'currency' => 'PHP',
                                'details' => [
                                    // The OpenAPI spec declares the details fields
                                    // as two-decimal numeric strings (e.g. "100.00").
                                    'subtotal' => number_format(round((float) $totals['subtotal'], 2), 2, '.', ''),
                                    'discount' => number_format(round((float) $totals['discount'], 2), 2, '.', ''),
                                    'shippingFee' => number_format(round((float) $totals['shippingFee'], 2), 2, '.', ''),
                                ],
                            ],
                            'buyer' => [
                                'firstName' => $validated['first_name'],
                                'lastName' => $validated['last_name'],
                                'contact' => [
                                    'phone' => $this->phoneForMaya($validated['phone']),
                                    'email' => $validated['email'],
                                ],
                                'shippingAddress' => [
                                    'firstName' => $validated['first_name'],
                                    'lastName' => $validated['last_name'],
                                    'phone' => $this->phoneForMaya($validated['phone']),
                                    'email' => $validated['email'],
                                    'line1' => trim($validated['address'].' '.($validated['address2'] ?? '')),
                                    'line2' => $validated['barangay'],
                                    'city' => $validated['city'],
                                    'state' => $validated['region'],
                                    'zipCode' => $validated['postal'],
                                    'countryCode' => 'PH',
                                ],
                                'billingAddress' => [
                                    'line1' => trim($validated['address'].' '.($validated['address2'] ?? '')),
                                    'line2' => $validated['barangay'],
                                    'city' => $validated['city'],
                                    'state' => $validated['region'],
                                    'zipCode' => $validated['postal'],
                                    'countryCode' => 'PH',
                                ],
                            ],
                            'items' => $lineItems,
                            'redirectUrl' => [
                                'success' => url('/checkout/success?order_id='.$order->id),
                                'failure' => url('/checkout/failure?order_id='.$order->id),
                                'cancel' => url('/checkout/cancel?order_id='.$order->id),
                            ],
                            'requestReferenceNumber' => $requestReference,
                        ]);
                } catch (\Exception $e) {
                    Log::warning('Maya checkout connection failed; retrying', [
                        'order_id' => $order->id,
                        'attempt' => $attempt,
                        'message' => $e->getMessage(),
                    ]);

                    $transientFailure = true;

                    if ($attempt < $attempts) {
                        usleep($retryDelayMs * 1000);

                        continue;
                    }

                    break;
                }

                $session = $response->json();

                if ($response->successful() && isset($session['checkoutId'], $session['redirectUrl'])) {
                    break;
                }

                if ($response->status() === 429 || $response->serverError() || ! is_array($session)) {
                    $transientFailure = true;

                    if ($attempt < $attempts) {
                        Log::warning('Maya checkout creation retrying transient failure', [
                            'order_id' => $order->id,
                            'attempt' => $attempt,
                            'status' => $response->status(),
                        ]);

                        usleep($retryDelayMs * 1000);

                        continue;
                    }

                    break;
                }

                // Non-transient (4xx validation/auth) failures will not be
                // fixed by retrying.
                $transientFailure = false;
                break;
            }

            if (! $response || ! isset($session['checkoutId'], $session['redirectUrl'])) {
                Log::error('Maya checkout creation failed', [
                    'order_id' => $order->id,
                    'status' => $response?->status(),
                    'response' => $session,
                ]);

                DB::transaction(function () use ($order, $inventory) {
                    $this->releaseCouponForOrder($order);
                    $inventory->release($order);
                    OrderItem::where('order_id', $order->id)->delete();
                    $order->delete();
                });

                return redirect()->route('checkout')->withErrors([
                    'checkout' => $transientFailure
                        ? 'Payment gateway is temporarily unavailable. Please try again in a few minutes.'
                        : 'Payment gateway returned an error. Please try again.',
                ]);
            }

            $order->checkout_session_id = $session['checkoutId'];
            $order->checkout_session_url = $session['redirectUrl'];
            $order->payment_request_reference = $requestReference;
            $order->save();

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

            return redirect()->away($session['redirectUrl']);

        } catch (\Exception $e) {
            Log::error('Maya API connection failed', [
                'order_id' => $order->id,
                'message' => $e->getMessage(),
            ]);

            DB::transaction(function () use ($order, $inventory) {
                $this->releaseCouponForOrder($order);
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
            $order = Order::whereKey($orderId)->where('user_id', Auth::id())->firstOrFail();

            // The webhook is authoritative, but the browser may arrive here
            // before it is delivered. Reconcile against Maya so the customer
            // is not told payment was received when it has not been yet.
            if ($order->payment_status !== 'paid' && $order->checkout_session_id) {
                if (app(MayaReconciliationService::class)->reconcile($order) === MayaReconciliationService::CONFIRMED) {
                    DispatchLalamoveDelivery::dispatch($order->id);
                }

                $order->refresh();
            }

            return redirect()->route('orders.show', $orderId)
                ->with('orderMessage', $order->payment_status === 'paid'
                    ? 'Payment received! We are processing your order.'
                    : 'Payment is being processed. We will confirm your order once payment is verified.');
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

    public function checkoutFailure(Request $request)
    {
        // Deliberately non-destructive: the browser redirect from the payment
        // gateway cannot carry a CSRF token. Abandoned unpaid orders have their
        // stock released via the PAYMENT_FAILED / PAYMENT_EXPIRED webhook.
        return redirect()->route('shop')
            ->with('orderMessage', 'Payment failed. Please try again.');
    }

    /**
     * Reconciliation against Maya's Get Checkout API lives in
     * MayaReconciliationService so the checkout success page, the scheduled
     * reconciler, and the expiry command all share identical logic.
     */
    protected function phoneForMaya(string $phone): string
    {
        return '+63'.substr(preg_replace('/[^0-9]/', '', $phone), -10);
    }

    protected function releaseCouponForOrder(Order $order): void
    {
        $usage = CouponUsage::where('order_id', $order->id)->first();

        if (! $usage) {
            return;
        }

        Coupon::whereKey($usage->coupon_id)->decrement('used_count');
        $usage->delete();
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

    protected function fullAddress(array $data): string
    {
        return app(GeocodingService::class)->fullAddress($this->addressParts($data));
    }

    protected function deliveryAddress(array $data): string
    {
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
}
