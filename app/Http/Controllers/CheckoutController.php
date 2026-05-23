<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\OrderPricingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class CheckoutController extends Controller
{
    public function checkout(OrderPricingService $pricing)
    {
        $cart = Cart::where('user_id', Auth::id())->with('product')->get();
        $totals = $pricing->totalsFromItems($cart);

        return view('checkout', [
            'cart' => $cart,
            'total' => $totals['subtotal'],
            'shippingFee' => $totals['shippingFee'],
            'grandTotal' => $totals['grandTotal'],
        ]);
    }

    public function placeOrder(Request $request, OrderPricingService $pricing)
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'address2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'postal' => ['required', 'string', 'max:20'],
            'phone' => ['required', 'digits:11'],
            'email' => ['required', 'email', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
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

            if ($item->quantity > $item->product->product_quantity) {
                throw ValidationException::withMessages([
                    'cart' => $item->product->product_title.' no longer has enough stock for your requested quantity.',
                ]);
            }
        }

        $totals = $pricing->totalsFromItems($cart);
        $lineItems = $cart->map(function ($item) {
            return [
                'name' => $item->product->product_title,
                'amount' => (int) round($item->product->product_price * 100),
                'currency' => 'PHP',
                'quantity' => (int) $item->quantity,
                'images' => [asset('products/'.$item->product->product_image)],
            ];
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

        $order = DB::transaction(function () use ($validated, $address, $totals, $cart) {
            $order = Order::create([
                'user_id' => Auth::id(),
                'name' => $validated['first_name'].' '.$validated['last_name'],
                'address' => trim($address.', '.$validated['city'].' '.$validated['postal']),
                'phone' => $validated['phone'],
                'total' => $totals['grandTotal'],
                'status' => 'pending',
                'payment_status' => 'unpaid',
                'notes' => $validated['notes'],
            ]);

            foreach ($cart as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'price' => $item->product->product_price,
                ]);
            }

            return $order;
        });

        $secret = base64_encode(env('PAYMONGO_SECRET').':');
        $response = Http::withHeaders([
            'Authorization' => 'Basic '.$secret,
            'Content-Type' => 'application/json',
        ])->post('https://api.paymongo.com/v1/checkout_sessions', [
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
                            'line1' => $validated['address'],
                            'line2' => $validated['address2'] ?? '',
                            'city' => $validated['city'],
                            'state' => 'Luzon',
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
            DB::transaction(function () use ($order) {
                OrderItem::where('order_id', $order->id)->delete();
                $order->delete();
            });

            return redirect()->route('checkout')->withErrors([
                'checkout' => 'We could not start the payment session. Please try again.',
            ]);
        }

        $order->update([
            'checkout_session_id' => $session['data']['id'],
        ]);

        return redirect()->away($session['data']['attributes']['checkout_url']);
    }

    public function checkoutSuccess(Request $request)
    {
        return redirect()->route('shop')
            ->with('orderMessage', 'Payment received! We are processing your order.');
    }

    public function checkoutCancel(Request $request)
    {
        if ($request->filled('order_id')) {
            $order = Order::whereKey($request->integer('order_id'))
                ->where('user_id', Auth::id())
                ->where('payment_status', 'unpaid')
                ->first();

            if ($order) {
                $order->update(['status' => 'cancelled']);
            }
        }

        return redirect()->route('shop')
            ->with('orderMessage', 'Payment was cancelled. Please try again.');
    }
}
