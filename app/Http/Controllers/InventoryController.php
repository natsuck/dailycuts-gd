<?php

namespace App\Http\Controllers;

use App\Models\InventoryHistory;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::query();

        if ($request->filled('search')) {
            $escaped = str_replace(['%', '_'], ['\\%', '\\_'], $request->input('search'));
            $query->where('product_title', 'like', "%{$escaped}%");
        }

        $products = $query->select('id', 'product_title', 'product_quantity', 'reorder_level', 'expiry_date')
            ->paginate(15)
            ->withQueryString();

        return view('admin.inventory', compact('products'));
    }

    public function adjust(Request $request, $id)
    {
        $validated = $request->validate([
            'type' => 'required|in:restock,adjustment',
            'quantity' => 'required|integer|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        $product = Product::findOrFail($id);

        DB::transaction(function () use ($product, $validated) {
            $product->recordInventoryChange(
                $validated['type'],
                $validated['quantity'],
                $validated['notes'] ?? null
            );
        });

        return redirect()->back()->with('success', 'Inventory adjusted successfully.');
    }

    public function history($productId)
    {
        $product = Product::findOrFail($productId);

        $history = InventoryHistory::where('product_id', $productId)
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('admin.inventory_history', compact('product', 'history'));
    }

    public function lowStock()
    {
        $products = Product::where(function ($query) {
            $query->whereColumn('product_quantity', '<=', 'reorder_level')
                ->orWhere(function ($q) {
                    $q->whereNull('reorder_level')
                        ->where('product_quantity', '<', 1);
                });
        })
            ->select('id', 'product_title', 'product_quantity', 'reorder_level', 'expiry_date')
            ->paginate(15);

        return view('admin.inventory', compact('products'));
    }
}
