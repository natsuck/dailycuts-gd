<?php

namespace App\Http\Controllers;

use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;

class ReviewController extends Controller
{
    public function store(Request $request, $productId)
    {
        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'nullable|string|max:255',
            'body' => 'nullable|string|max:2000',
            'images' => 'nullable|array|max:5',
            'images.*' => 'image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $product = Product::findOrFail($productId);

        $delivered = OrderItem::query()
            ->where('product_id', $product->id)
            ->whereHas('order', function ($query) {
                $query->where('user_id', Auth::id())
                    ->where('payment_status', 'paid')
                    ->where('status', 'delivered');
            })
            ->exists();

        if (! $delivered) {
            return redirect()
                ->route('product_details', $product->id)
                ->withErrors(['review' => 'You can only review a product after your order has been delivered.']);
        }

        $existingReview = Review::where('user_id', Auth::id())
            ->where('product_id', $product->id)
            ->first();

        $imageFilenames = $existingReview->images ?? [];

        if ($existingReview && ! empty($existingReview->images)) {
            foreach ($existingReview->images as $oldImage) {
                $path = public_path('reviews/' . $oldImage);
                if (File::exists($path)) {
                    File::delete($path);
                }
            }
        }

        if ($request->hasFile('images')) {
            $newImages = [];
            foreach ($request->file('images') as $image) {
                $filename = uniqid('review_', true) . '.' . $image->guessExtension();
                $image->move(public_path('reviews'), $filename);
                $newImages[] = $filename;
            }
            $imageFilenames = $newImages;
        } else {
            $imageFilenames = $existingReview ? $existingReview->images : [];
        }

        $validated['images'] = ! empty($imageFilenames) ? $imageFilenames : null;

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

        if (! empty($review->images)) {
            foreach ($review->images as $image) {
                $path = public_path('reviews/' . $image);
                if (File::exists($path)) {
                    File::delete($path);
                }
            }
        }

        $review->delete();

        return redirect()->back()->with('success', 'Review deleted successfully.');
    }
}
