<?php

namespace App\Http\Controllers;

use App\Mail\ResellerInquiryMail;
use App\Models\Cart;
use App\Models\Category;
use App\Models\HeroSlide;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Review;
use App\Services\OrderPricingService;
use App\Services\ProductPairingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function home()
    {
        $count = Cart::where('user_id', Auth::id())->count();
        $products = Product::latest()->take(8)->get();
        $reviews = Review::with('user')->latest()->take(3)->get();

        $categoryImages = collect();
        Product::with('categories')
            ->whereNotNull('product_image')
            ->get()
            ->each(function ($product) use (&$categoryImages) {
                $names = $product->categories->isNotEmpty()
                    ? $product->categories->pluck('category')
                    : collect([$product->product_category]);

                foreach ($names->filter() as $name) {
                    $categoryImages->put($name, $categoryImages->get($name) ?? $product->product_image);
                }
            });

        $categoryCards = Category::orderBy('id')->pluck('category')
            ->filter()
            ->map(fn ($category) => [
                'name' => $category,
                'image' => $categoryImages->get($category),
                'route' => route('shop', ['category' => $category]),
            ])
            ->values();

        $bestSellerId = OrderItem::join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->where('orders.status', '!=', 'cancelled')
            ->whereNotNull('products.product_image')
            ->select('order_items.product_id', \DB::raw('SUM(order_items.quantity) as total_sold'))
            ->groupBy('order_items.product_id')
            ->orderByDesc('total_sold')
            ->first();

        $bestSellerCard = $bestSellerId
            ? [
                'name' => 'Best Sellers',
                'image' => Product::whereKey($bestSellerId->product_id)->value('product_image'),
                'route' => route('shop', ['category' => 'best_sellers']),
            ]
            : null;

        $categoryCards = $bestSellerCard
            ? collect([$bestSellerCard])->concat($categoryCards)
            : $categoryCards;

        $slides = $this->buildHeroSlides();

        return view('index', compact('products', 'count', 'reviews', 'categoryCards', 'slides'));
    }

    protected function buildHeroSlides(): array
    {
        $defaults = [
            [
                'img' => asset('frontend/images/2222.jpeg'),
                'mobileImg' => asset('frontend/images/mobile-hero.jpg'),
                'tag' => 'EXCLUSIVE DAILY CUTS',
                'heading' => 'Masterpieces of freshness, cut daily for your table.',
                'sub' => 'Premium beef, pork, and chicken from trusted local suppliers, packed clean and delivered with care.',
                'ctaText' => 'Shop Now',
                'ctaLink' => route('shop'),
            ],
            [
                'img' => asset('frontend/images/banners.png'),
                'mobileImg' => asset('frontend/images/mobile-hero.jpg'),
                'tag' => 'WEEKLY SPECIALS',
                'heading' => 'Premium cuts at unbeatable prices every week.',
                'sub' => 'Discover hand-selected deals on our finest meats. Fresh, affordable, and delivered to your door.',
                'ctaText' => 'View Deals',
                'ctaLink' => route('shop'),
            ],
            [
                'img' => asset('frontend/images/banners.png'),
                'mobileImg' => asset('frontend/images/mobile-hero.jpg'),
                'tag' => 'ORDER NOW',
                'heading' => 'Premium Frozen Meats Delivered Fresh Daily',
                'sub' => 'Samgyupsal Meat • Steak • Wagyu • Wholesale & Retail',
                'ctaText' => 'Order Now',
                'ctaLink' => route('shop'),
            ],
        ];

        if (! Schema::hasTable('hero_slides')) {
            return $defaults;
        }

        $slides = HeroSlide::active()
            ->orderBy('sort_order')
            ->orderByDesc('created_at')
            ->get();

        if ($slides->isEmpty()) {
            return $defaults;
        }

        return $slides->map(fn ($slide) => [
            'img' => asset($slide->image_path),
            'mobileImg' => $slide->mobile_image_path
                ? asset($slide->mobile_image_path)
                : asset('frontend/images/mobile-hero.jpg'),
            'tag' => $slide->tag,
            'heading' => $slide->heading,
            'sub' => $slide->subheading,
            'ctaText' => $slide->cta_text,
            'ctaLink' => $slide->cta_link ?: route('shop'),
        ])->values()->all();
    }

    public function index()
    {
        if (Auth::check() && Auth::user()->user_type === 'admin') {
            return app(AdminController::class)->dashboard();
        }

        return redirect()->route('index');
    }

    public function contactUs()
    {
        return view('contact_us');
    }

    public function storeLocations()
    {
        $locations = \App\Models\StoreLocation::active()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view('store_locations', [
            'locations' => $locations,
            'mapsKey' => (string) config('services.google_maps.key'),
        ]);
    }

    public function submitResellerInquiry(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'regex:/^[A-Za-z\s\-\.\']+$/'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50', 'regex:/^[0-9\+\-\s]+$/'],
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $recipient = (string) config('services.reseller.email')
            ?: (string) config('mail.from.address');

        try {
            Mail::to($recipient)->queue(new ResellerInquiryMail($validated));
        } catch (\Throwable $e) {
            report($e);
        }

        return redirect()
            ->route('contact_us')
            ->with('resellerMessage', 'Your reseller inquiry has been sent successfully.');
    }

    public function productDetails($id, ProductPairingService $pairings)
    {
        $product = Product::with(['wishlistedBy' => function ($q) {
            $q->where('user_id', Auth::id());
        }])->findOrFail($id);

        $reviews = $product->reviews()->with('user')->latest()->paginate(10);

        $related_products = Product::where('id', '!=', $id)
            ->latest()
            ->take(4)
            ->get();
        $pairingProducts = $pairings->forProduct($product);

        return view('product_details', compact('product', 'related_products', 'pairingProducts', 'reviews'));
    }

    public function shop(Request $request)
    {
        $query = Product::query();

        $search = $request->input('search');
        if ($search) {
            $escaped = str_replace(['%', '_'], ['\\%', '\\_'], $search);
            $query->where(function ($q) use ($escaped) {
                $q->where('product_title', 'LIKE', "%{$escaped}%")
                    ->orWhere('product_description', 'LIKE', "%{$escaped}%");
            });
        }

        $category = $request->input('category');
        $bestSellerIds = [];
        if ($category === 'best_sellers') {
            $bestSellerIds = OrderItem::select('product_id', \DB::raw('SUM(quantity) as total_sold'))
                ->join('orders', 'orders.id', '=', 'order_items.order_id')
                ->where('orders.status', '!=', 'cancelled')
                ->groupBy('product_id')
                ->orderByDesc('total_sold')
                ->pluck('product_id')
                ->toArray();

            if (! empty($bestSellerIds)) {
                $placeholders = implode(',', array_fill(0, count($bestSellerIds), '?'));
                $query->whereIn('products.id', $bestSellerIds)
                    ->orderByRaw("FIELD(products.id, $placeholders)", $bestSellerIds);
            }
        } elseif ($category) {
            $query->where(function ($q) use ($category) {
                $q->where('product_category', $category)
                    ->orWhereHas('categories', fn ($cq) => $cq->where('categories.category', $category));
            });
        }

        $sortBy = $request->input('sort', $category === 'best_sellers' ? 'best_sellers' : 'latest');
        if ($sortBy === 'best_sellers' && $category !== 'best_sellers') {
            $sortBy = 'latest';
        }
        $query = match ($sortBy) {
            'price_low' => $query->reorder('product_price', 'asc'),
            'price_high' => $query->reorder('product_price', 'desc'),
            'name' => $query->reorder('product_title', 'asc'),
            default => $query,
        };

        $products = $query->with('variants')->paginate(12)->withQueryString();
        $categories = Cache::remember('shop_categories', 3600, function () {
            return Category::orderBy('id')->pluck('category')->filter()->values();
        });

        return view('shop', compact('products', 'categories'));
    }

    public function addToCart(Request $request, $id)
    {
        $product = Product::findOrFail($id);
        $validated = $request->validate([
            'quantity' => ['nullable', 'integer', 'min:1', 'max:9999'],
            'variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
        ]);
        $quantityToAdd = (int) ($validated['quantity'] ?? 1);
        $variantId = $validated['variant_id'] ?? null;

        DB::transaction(function () use ($product, $variantId, $quantityToAdd) {
            $lockedProduct = Product::lockForUpdate()->find($product->id);

            $variant = $variantId
                ? ProductVariant::where('id', $variantId)->where('product_id', $product->id)->lockForUpdate()->first()
                : null;

            if ($variantId && ! $variant) {
                throw ValidationException::withMessages([
                    'variant_id' => 'The selected variant is not valid for this product.',
                ]);
            }

            $availableStock = $variant ? $variant->quantity : $lockedProduct->product_quantity;

            $cart = Cart::where('user_id', Auth::id())
                ->where('product_id', $product->id)
                ->where('variant_id', $variantId)
                ->lockForUpdate()
                ->first();

            $existingQuantity = $cart?->quantity ?? 0;

            if ($availableStock < ($existingQuantity + $quantityToAdd)) {
                throw ValidationException::withMessages([
                    'quantity' => 'Only '.$availableStock.' item(s) are available.',
                ]);
            }

            if ($cart) {
                $cart->quantity += $quantityToAdd;
            } else {
                $cart = new Cart([
                    'user_id' => Auth::id(),
                    'product_id' => $product->id,
                    'variant_id' => $variantId,
                    'quantity' => $quantityToAdd,
                ]);
            }

            $cart->save();
        });

        return redirect()->back()->with('cartMessage', 'Added to cart.');
    }

    public function viewCart(OrderPricingService $pricing, ProductPairingService $pairings)
    {
        $cart = Cart::where('user_id', Auth::id())
            ->with(['product', 'variant'])
            ->get();
        $totals = $pricing->totalsFromItems($cart);
        $pairingProducts = $pairings->forCart($cart, 4);

        return view('viewcart', [
            'cart' => $cart,
            'total' => $totals['subtotal'],
            'grandTotal' => $totals['subtotal'],
            'pairingProducts' => $pairingProducts,
        ]);
    }

    public function removeCart($id)
    {
        $cartItem = Cart::where('id', $id)->where('user_id', Auth::id())->first();

        if (! $cartItem) {
            abort(404);
        }

        $cartItem->delete();

        return redirect()->back()->with('cartMessage', 'Item removed from cart.');
    }

    public function updateCart(Request $request, $id, OrderPricingService $pricing)
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:9999'],
        ]);

        return DB::transaction(function () use ($id, $validated, $pricing) {
            $cart = Cart::where('id', $id)
                ->where('user_id', Auth::id())
                ->with(['product', 'variant'])
                ->lockForUpdate()
                ->first();

            if (! $cart) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $availableStock = $cart->variant ? $cart->variant->quantity : $cart->product->product_quantity;

            if ($validated['quantity'] > $availableStock) {
                return response()->json([
                    'error' => 'Requested quantity exceeds available stock.',
                ], 422);
            }

            $cart->quantity = $validated['quantity'];
            $cart->save();

            $cartItems = Cart::where('user_id', Auth::id())
                ->with('product')
                ->get();
            $totals = $pricing->totalsFromItems($cartItems);

            return response()->json([
                'success' => true,
                'quantity' => $cart->quantity,
                'subtotal' => number_format($cart->subtotal(), 2),
                'cartTotal' => number_format($totals['subtotal'], 2),
                'grandTotal' => number_format($totals['subtotal'], 2),
            ]);
        });
    }

    public function changeVariant(Request $request, $id, OrderPricingService $pricing)
    {
        $validated = $request->validate([
            'variant_id' => ['required', 'integer', 'exists:product_variants,id'],
        ]);

        return DB::transaction(function () use ($id, $validated, $pricing) {
            $cart = Cart::where('id', $id)
                ->where('user_id', Auth::id())
                ->with('product')
                ->lockForUpdate()
                ->first();

            if (! $cart) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $variant = ProductVariant::where('id', $validated['variant_id'])
                ->where('product_id', $cart->product_id)
                ->lockForUpdate()
                ->first();

            if (! $variant) {
                return response()->json(['error' => 'Invalid variant for this product.'], 422);
            }

            if ($variant->quantity <= 0) {
                return response()->json(['error' => 'Selected variant is out of stock.'], 422);
            }

            $existing = Cart::where('user_id', Auth::id())
                ->where('product_id', $cart->product_id)
                ->where('variant_id', $variant->id)
                ->where('id', '!=', $cart->id)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                $maxStock = $variant->quantity;
                $newQty = min($existing->quantity + $cart->quantity, $maxStock);
                $existing->quantity = $newQty;
                $existing->save();
                $cart->delete();

                $cartItem = $existing;
            } else {
                if ($cart->quantity > $variant->quantity) {
                    $cart->quantity = $variant->quantity;
                }
                $cart->variant_id = $variant->id;
                $cart->save();
                $cartItem = $cart;
            }

            $cartItems = Cart::where('user_id', Auth::id())
                ->with('product')
                ->get();
            $totals = $pricing->totalsFromItems($cartItems);

            return response()->json([
                'success' => true,
                'cartItemId' => $cartItem->id,
                'weight' => $variant->weight,
                'price' => number_format($variant->price, 2),
                'subtotal' => number_format($cartItem->subtotal(), 2),
                'quantity' => $cartItem->quantity,
                'cartTotal' => number_format($totals['subtotal'], 2),
                'grandTotal' => number_format($totals['subtotal'], 2),
            ]);
        });
    }
}
