<?php

namespace App\Http\Controllers;

use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    public function store(Request $request, $productId)
    {
        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'nullable|string|max:255',
            'body' => 'nullable|string|max:2000',
        ]);

        $product = Product::findOrFail($productId);

        $purchased = OrderItem::query()
            ->where('product_id', $product->id)
            ->whereHas('order', function ($query) {
                $query->where('user_id', Auth::id())
                    ->where('payment_status', 'paid')
                    ->where('status', '!=', 'cancelled');
            })
            ->exists();

        if (! $purchased) {
            return redirect()
                ->route('product_details', $product->id)
                ->withErrors(['review' => 'You can only review a product you have purchased.']);
        }

        Review::updateOrCreate(
            ['user_id' => Auth::id(), 'product_id' => $product->id],
            $validated
        );

        return redirect()->route('product_details', $product->id)->with('success', 'Review submitted successfully.');
    }

    public function destroy($id)
    {
        $review = Review::where('id', $id)->where('user_id', Auth::id())->first();

        if (! $review) {
            abort(404);
        }

        $review->delete();

        return redirect()->back()->with('success', 'Review deleted successfully.');
    }
}
