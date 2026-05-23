<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Product;
use App\Mail\ResellerInquiryMail;
use App\Services\OrderPricingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function home()
    {
        $count = Cart::where('user_id', Auth::id())->count();
        $products = Product::latest()->take(8)->get();

        return view('index', compact('products', 'count'));
    }

    public function index()
    {
        if (Auth::check() && Auth::user()->user_type === 'user') {
            $cartCount = Cart::where('user_id', Auth::id())->count();
            $products = Product::latest()->take(8)->get();

            return view('dashboard', compact('cartCount', 'products'));
        }

        if (Auth::check() && Auth::user()->user_type === 'admin') {
            return app(AdminController::class)->dashboard();
        }

        return redirect()->route('index');
    }

    public function contactUs()
    {
        return view('contact_us');
    }

    public function submitResellerInquiry(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $recipient = env('RESELLER_INQUIRY_EMAIL', env('MAIL_FROM_ADDRESS'));

        Mail::to($recipient)->send(new ResellerInquiryMail($validated));

        return redirect()
            ->route('contact_us')
            ->with('resellerMessage', 'Your reseller inquiry has been sent successfully.');
    }

    public function productDetails($id)
    {
        $product = Product::findOrFail($id);
        $related_products = Product::where('id', '!=', $id)
            ->latest()
            ->take(4)
            ->get();

        return view('product_details', compact('product', 'related_products'));
    }

    public function shop()
    {
        $products = Product::all();

        return view('shop', compact('products'));
    }

    public function addToCart(Request $request, $id)
    {
        $product = Product::findOrFail($id);
        $validated = $request->validate([
            'quantity' => ['nullable', 'integer', 'min:1'],
        ]);
        $quantityToAdd = (int) ($validated['quantity'] ?? 1);

        $cart = Cart::where('user_id', Auth::id())
            ->where('product_id', $id)
            ->first();

        $existingQuantity = $cart?->quantity ?? 0;

        if ($product->product_quantity < ($existingQuantity + $quantityToAdd)) {
            throw ValidationException::withMessages([
                'quantity' => 'Only '.$product->product_quantity.' item(s) are available for this product.',
            ]);
        }

        if ($cart) {
            $cart->quantity += $quantityToAdd;
        } else {
            $cart = new Cart([
                'user_id' => Auth::id(),
                'product_id' => $id,
                'quantity' => $quantityToAdd,
            ]);
        }

        $cart->save();

        return redirect()->back()->with('cartMessage', 'Added to cart.');
    }

    public function viewCart(OrderPricingService $pricing)
    {
        $cart = Cart::where('user_id', Auth::id())
            ->with('product')
            ->get();
        $totals = $pricing->totalsFromItems($cart);

        return view('viewcart', [
            'cart' => $cart,
            'total' => $totals['subtotal'],
            'shippingFee' => $totals['shippingFee'],
            'grandTotal' => $totals['grandTotal'],
        ]);
    }

    public function removeCart($id)
    {
        $cartItem = Cart::findOrFail($id);

        if ($cartItem->user_id == Auth::id()) {
            $cartItem->delete();
        }

        return redirect()->back()->with('cartMessage', 'Item removed from cart.');
    }

    public function updateCart(Request $request, $id, OrderPricingService $pricing)
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
        ]);
        $cart = Cart::with('product')->findOrFail($id);

        if ($cart->user_id != Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($validated['quantity'] > $cart->product->product_quantity) {
            return response()->json([
                'error' => 'Requested quantity exceeds available stock.',
            ], 422);
        }

        $cart->quantity = $validated['quantity'];
        $cart->save();

        $subtotal = $cart->product->product_price * $cart->quantity;
        $cartItems = Cart::where('user_id', Auth::id())
            ->with('product')
            ->get();
        $totals = $pricing->totalsFromItems($cartItems);

        return response()->json([
            'success' => true,
            'quantity' => $cart->quantity,
            'subtotal' => number_format($subtotal, 2),
            'cartTotal' => number_format($totals['subtotal'], 2),
            'shippingFee' => number_format($totals['shippingFee'], 2),
            'grandTotal' => number_format($totals['grandTotal'], 2),
        ]);
    }
}
