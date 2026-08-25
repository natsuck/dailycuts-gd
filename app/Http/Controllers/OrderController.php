<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Review;
use App\Services\LalamoveService;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::with('items.product')
            ->where('user_id', Auth::id())
            ->orderByDesc('created_at')
            ->paginate(10);

        return view('orders.index', compact('orders'));
    }

    public function show($id)
    {
        $order = Order::with('items.product')
            ->where('user_id', Auth::id())
            ->findOrFail($id);

        $steps = ['pending', 'processing', 'shipped', 'delivered'];
        $currentIndex = array_search($order->status, $steps);
        if ($currentIndex === false) {
            $currentIndex = $order->status === 'cancelled' ? -1 : 0;
        }

        $productIds = $order->items->pluck('product_id')->filter()->values();
        $existingReviews = Review::where('user_id', Auth::id())
            ->whereIn('product_id', $productIds)
            ->get()
            ->keyBy('product_id');

        return view('orders.show', compact('order', 'steps', 'currentIndex', 'existingReviews'));
    }

    /**
     * Live Lalamove courier status for a customer's order.
     */
    public function lalamoveStatus($id, LalamoveService $lalamove)
    {
        $order = Order::where('user_id', Auth::id())->findOrFail($id);

        if (! $order->lalamove_order_id) {
            return response()->json(['available' => false]);
        }

        $info = $lalamove->getOrder($order->lalamove_order_id);

        if ($info) {
            $order->lalamove_status = $info['status'] ?? $order->lalamove_status;
            $order->delivery_status = $info['status'] ?? $order->delivery_status;
            $order->tracking_url = data_get($info, 'shareLink') ?: $order->tracking_url;
            $order->save();
        }

        $driver = $info['driver'] ?? null;

        return response()->json([
            'available' => true,
            'status' => $order->delivery_status,
            'lalamove_status' => $order->lalamove_status,
            'tracking_url' => $order->tracking_url,
            'driver' => $driver ? [
                'name' => $driver['name'] ?? null,
                'phone' => $driver['phone'] ?? null,
            ] : null,
            'error' => $info ? null : $lalamove->getLastError(),
        ]);
    }
}
