<?php

namespace App\Http\Controllers;

use App\Models\Order;
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

        return view('orders.show', compact('order', 'steps', 'currentIndex'));
    }
}
