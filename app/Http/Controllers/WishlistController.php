<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\WishlistedItem;
use Illuminate\Support\Facades\Auth;

class WishlistController extends Controller
{
    public function index()
    {
        $wishlist = WishlistedItem::where('user_id', Auth::id())
            ->with('product')
            ->get();

        return view('wishlist', compact('wishlist'));
    }

    public function toggle($id)
    {
        $product = Product::findOrFail($id);

        $existing = WishlistedItem::where('user_id', Auth::id())
            ->where('product_id', $product->id)
            ->first();

        if ($existing) {
            $existing->delete();

            return redirect()->back()->with('success', 'Product removed from your wishlist.');
        }

        WishlistedItem::create([
            'user_id' => Auth::id(),
            'product_id' => $product->id,
        ]);

        return redirect()->back()->with('success', 'Product added to your wishlist.');
    }

    public function destroy($id)
    {
        $wishlistItem = WishlistedItem::where('user_id', Auth::id())
            ->findOrFail($id);

        $wishlistItem->delete();

        return redirect()->back()->with('success', 'Item removed from your wishlist.');
    }
}
