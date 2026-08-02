<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\InventoryService;
use App\Services\OrderPricingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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

        return view('checkout', [
            'cart' => $cart,
            'total' => $totals['subtotal'],
            'shippingFee' => $hasShippingEstimate ? $totals['shippingFee'] : null,
            'discount' => $totals['discount'],
            'freeShipping' => $totals['freeShipping'],
            'grandTotal' => $hasShippingEstimate ? $totals['grandTotal'] : round($totals['subtotal'] - $totals['discount'], 2),
            'coupon' => $coupon,
            'couponCode' => $coupon?->code,
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

    public function estimateShipping(Request $request, OrderPricingService $pricing)
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

        $fee = $pricing->shippingFee($city);
        $source = $pricing->getLastShippingSource();

        return response()->json([
            'shippingFee' => $fee,
            'source' => $source,
        ]);
    }

    public function placeOrder(Request $request, OrderPricingService $pricing, InventoryService $inventory)
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

        $totals = $pricing->totalsFromSubtotal($subtotal, $validated['city'], $coupon);
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
        $address = trim($validated['address'].' '.($validated['address2'] ?? ''));
        $fullAddress = trim($address.', '.$validated['barangay'].', '.$validated['city'].', '.$validated['region'].' '.$validated['postal']);

        $order = DB::transaction(function () use ($validated, $fullAddress, $totals, $cart, $inventory, $coupon) {
            $order = Order::create([
                'user_id' => Auth::id(),
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
            ]);

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
            ]);

            if ($coupon) {
                DB::transaction(function () use ($coupon, $order, $totals) {
                    Coupon::whereKey($coupon->id)->lockForUpdate()->increment('used_count');

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

        if ($orderId) {
            return redirect()->route('orders.show', $orderId)
                ->with('orderMessage', 'Payment received! We are processing your order.');
        }

        return redirect()->route('shop')
            ->with('orderMessage', 'Payment received! We are processing your order.');
    }

    public function checkoutCancel(Request $request, InventoryService $inventory)
    {
        if ($request->filled('order_id')) {
            $order = Order::with('items')
                ->whereKey($request->integer('order_id'))
                ->where('user_id', Auth::id())
                ->where('payment_status', 'unpaid')
                ->where('status', '!=', 'cancelled')
                ->first();

            if ($order) {
                DB::transaction(function () use ($order, $inventory) {
                    $inventory->release($order);
                    $order->update(['status' => 'cancelled']);
                });
            }
        }

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
