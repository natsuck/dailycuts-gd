<?php

namespace App\Http\Controllers;

use App\Mail\OrderDeliveredMail;
use App\Mail\OrderShippedMail;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

class AdminController extends Controller
{
    public function dashboard()
    {
        $today = Carbon::today();
        $salesStartDate = $today->copy()->subDays(6)->startOfDay();
        $salesEndDate = $today->copy()->endOfDay();
        $hasReorderLevel = Schema::hasColumn('products', 'reorder_level');
        $hasExpiryDate = Schema::hasColumn('products', 'expiry_date');

        $dashboardStats = [
            'totalProducts' => Product::count(),
            'totalOrders' => Order::count(),
            'ordersToday' => Order::whereDate('created_at', $today)->count(),
            'pendingOrders' => Order::where('status', 'pending')->count(),
            'paidRevenue' => Order::where('payment_status', 'paid')->sum('total'),
            'todayRevenue' => Order::where('payment_status', 'paid')
                ->whereDate('created_at', $today)
                ->sum('total'),
            'monthRevenue' => Order::where('payment_status', 'paid')
                ->whereBetween('created_at', [$today->copy()->startOfMonth(), $today->copy()->endOfMonth()])
                ->sum('total'),
            'lowStockProducts' => $hasReorderLevel
                ? Product::whereColumn('product_quantity', '<', 'reorder_level')->count()
                : 0,
            'expiringProducts' => $hasExpiryDate
                ? Product::whereNotNull('expiry_date')
                    ->whereDate('expiry_date', '<=', $today->copy()->addDays(3))
                    ->where('product_quantity', '>', 0)
                    ->count()
                : 0,
        ];

        $recentOrders = Order::with(['user', 'items.product'])
            ->latest()
            ->take(6)
            ->get();

        $topProducts = OrderItem::query()
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->whereNotIn('orders.status', ['cancelled', 'returned'])
            ->select(
                'products.product_title',
                DB::raw('SUM(order_items.quantity) as total_quantity'),
                DB::raw('SUM(order_items.quantity * order_items.price) as total_sales')
            )
            ->groupBy('products.id', 'products.product_title')
            ->orderByDesc('total_quantity')
            ->take(5)
            ->get();

        $lowStockProducts = $hasReorderLevel
            ? Product::whereColumn('product_quantity', '<', 'reorder_level')
                ->orderBy('product_quantity')
                ->take(5)
                ->get(['id', 'product_title', 'product_quantity', 'reorder_level'])
            : collect();

        $expiringProducts = $hasExpiryDate
            ? Product::whereNotNull('expiry_date')
                ->whereDate('expiry_date', '<=', $today->copy()->addDays(3))
                ->where('product_quantity', '>', 0)
                ->orderBy('expiry_date')
                ->take(5)
                ->get(['id', 'product_title', 'product_quantity', 'expiry_date'])
            : collect();

        $orderStatusCounts = Order::query()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $salesTrend = $this->dashboardSalesTrend($salesStartDate, $salesEndDate);

        return view('admin.dashboard', compact(
            'dashboardStats',
            'recentOrders',
            'topProducts',
            'lowStockProducts',
            'expiringProducts',
            'orderStatusCounts',
            'salesTrend'
        ));
    }

    public function addCategory()
    {
        return view('admin.addcategory');
    }

    public function postAddCategory(Request $request)
    {
        $validated = $request->validate([
            'category' => ['required', 'string', 'max:255', 'unique:categories,category'],
        ]);

        $category = new Category();
        $category->category = $validated['category'];
        $category->save();

        return redirect()->back()->with('category_message', 'Category added successfully!');
    }

    public function viewCategory()
    {
        $categories = Category::all();

        return view('admin.viewcategory', compact('categories'));
    }

    public function deleteCategory($id)
    {
        $category = Category::findOrFail($id);
        $category->delete();

        return redirect()->back()->with('deletecategory_message', 'Deleted successfully!');
    }

    public function updateCategory($id)
    {
        $category = Category::findOrFail($id);

        return view('admin.updatecategory', compact('category'));
    }

    public function postUpdatecategory(Request $request, $id)
    {
        $validated = $request->validate([
            'category' => ['required', 'string', 'max:255', 'unique:categories,category,'.$id],
        ]);

        $category = Category::findOrFail($id);
        $category->category = $validated['category'];
        $category->save();

        return redirect()->back()->with('category_updated_message', 'Category updated successfully!');
    }

    public function addProduct()
    {
        $categories = Category::all();

        return view('admin.addproduct', compact('categories'));
    }

    public function postAddProduct(Request $request)
    {
        $validated = $this->validateProduct($request);
        $product = new Product();
        $product->fill($validated);

        if ($request->hasFile('product_image')) {
            $product->product_image = $this->storeProductImage($request);
        }

        $product->save();

        return redirect()->back()->with('product_message', 'Product added successfully!');
    }

    public function viewProduct()
    {
        $products = Product::orderBy('created_at', 'desc')->paginate(5);

        return view('admin.viewproduct', compact('products'));
    }

    public function deleteProduct($id)
    {
        $product = Product::findOrFail($id);
        $imagePath = public_path('products/'.$product->product_image);

        if ($product->product_image && file_exists($imagePath)) {
            unlink($imagePath);
        }

        $product->delete();

        return redirect()->back()->with('deleteproduct_message', 'Deleted successfully.');
    }

    public function updateProduct($id)
    {
        $product = Product::findOrFail($id);
        $categories = Category::all();

        return view('admin.updateproduct', compact('product', 'categories'));
    }

    public function postUpdateProduct(Request $request, $id)
    {
        $validated = $this->validateProduct($request, true);
        $product = Product::findOrFail($id);
        $product->fill($validated);

        if ($request->hasFile('product_image')) {
            $oldImagePath = public_path('products/'.$product->product_image);

            if ($product->product_image && file_exists($oldImagePath)) {
                unlink($oldImagePath);
            }

            $product->product_image = $this->storeProductImage($request);
        }

        $product->save();

        return redirect()->back()->with('update_product_message', 'Product updated successfully!');
    }

    public function searchProduct(Request $request)
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
        ]);
        $search = $validated['search'] ?? '';

        $products = Product::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where('product_title', 'LIKE', '%'.$search.'%')
                    ->orWhere('product_description', 'LIKE', '%'.$search.'%')
                    ->orWhere('product_category', 'LIKE', '%'.$search.'%');
            })
            ->orderBy('created_at', 'desc')
            ->paginate(5)
            ->appends(['search' => $search]);

        return view('admin.viewproduct', compact('products'));
    }

    public function viewOrders()
    {
        $orders = Order::with('user', 'items.product')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        $orderStats = [
            'total' => Order::count(),
            'pending' => Order::where('status', 'pending')->count(),
            'paid' => Order::where('payment_status', 'paid')->count(),
            'revenue' => Order::where('payment_status', 'paid')->sum('total'),
        ];

        return view('admin.vieworders', compact('orders', 'orderStats'));
    }

    public function updateStatus(Request $request, $id)
    {
        $order = Order::with(['items.product', 'user'])->findOrFail($id);
        $validated = $request->validate([
            'status' => ['required', 'in:pending,shipped,delivered,cancelled,returned'],
        ]);

        $order->status = $validated['status'];
        $order->save();

        if ($order->status === 'shipped') {
            Mail::to($order->user->email)->send(new OrderShippedMail($order));
        }

        if ($order->status === 'delivered') {
            Mail::to($order->user->email)->send(new OrderDeliveredMail($order));
        }

        return redirect()->back()->with('success', 'Order status updated!');
    }

    public function deleteOrder($id)
    {
        $order = Order::findOrFail($id);

        DB::transaction(function () use ($order) {
            OrderItem::where('order_id', $order->id)->delete();
            $order->delete();
        });

        return redirect()->back()->with('success', 'Order deleted successfully.');
    }

    protected function dashboardSalesTrend(Carbon $startDate, Carbon $endDate): array
    {
        $dailySales = Order::query()
            ->where('payment_status', 'paid')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select(
                DB::raw('DATE(created_at) as order_date'),
                DB::raw('SUM(total) as revenue'),
                DB::raw('COUNT(*) as orders_count')
            )
            ->groupBy('order_date')
            ->orderBy('order_date')
            ->get()
            ->keyBy('order_date');

        $labels = [];
        $revenues = [];
        $orders = [];

        foreach (CarbonPeriod::create($startDate->copy()->startOfDay(), $endDate->copy()->startOfDay()) as $date) {
            $key = $date->toDateString();
            $labels[] = $date->format('M d');
            $revenues[] = round((float) ($dailySales[$key]->revenue ?? 0), 2);
            $orders[] = (int) ($dailySales[$key]->orders_count ?? 0);
        }

        return [
            'labels' => $labels,
            'revenues' => $revenues,
            'orders' => $orders,
        ];
    }

    protected function validateProduct(Request $request, bool $isUpdate = false): array
    {
        return $request->validate([
            'product_title' => ['required', 'string', 'max:255'],
            'product_description' => ['required', 'string'],
            'product_quantity' => ['required', 'integer', 'min:0'],
            'reorder_level' => ['nullable', 'integer', 'min:0'],
            'expiry_date' => ['nullable', 'date'],
            'product_price' => ['required', 'numeric', 'min:0'],
            'product_category' => ['required', 'string', 'max:255'],
            'product_image' => [$isUpdate ? 'nullable' : 'required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);
    }

    protected function storeProductImage(Request $request): string
    {
        $image = $request->file('product_image');
        $imageName = uniqid('product_', true).'.'.$image->getClientOriginalExtension();
        $image->move(public_path('products'), $imageName);

        return $imageName;
    }
}
