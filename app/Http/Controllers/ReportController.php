<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        [$from, $to] = $this->parseRange($validated);

        $reportData = $this->buildReport($from, $to);

        return view('admin.reports', array_merge($reportData, [
            'from' => $from->format('Y-m-d'),
            'to' => $to->format('Y-m-d'),
        ]));
    }

    public function salesByDate(Request $request)
    {
        $validated = $request->validate([
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
        ]);

        [$from, $to] = $this->parseRange($validated);

        $reportData = $this->buildReport($from, $to);

        return view('admin.reports', array_merge($reportData, [
            'from' => $from->format('Y-m-d'),
            'to' => $to->format('Y-m-d'),
        ]));
    }

    public function exportSalesCsv(Request $request)
    {
        $validated = $request->validate([
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
        ]);

        [$from, $to] = $this->parseRange($validated);

        $orders = Order::with(['user', 'items'])
            ->whereBetween('created_at', [$from, $to])
            ->orderBy('created_at', 'desc')
            ->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="sales_report_'.$from->format('Y-m-d').'_to_'.$to->format('Y-m-d').'.csv"',
        ];

        $callback = function () use ($orders) {
            $file = fopen('php://output', 'w');

            $escape = static function (mixed $value): string {
                $value = (string) $value;

                if ($value !== '' && str_contains('=+-@'."\t".chr(13), $value[0])) {
                    return "'".$value;
                }

                return $value;
            };

            fputcsv($file, ['Date', 'Order ID', 'Customer', 'Items Count', 'Subtotal', 'Status', 'Payment Status']);

            foreach ($orders as $order) {
                fputcsv($file, [
                    $escape($order->created_at->format('Y-m-d H:i:s')),
                    $escape($order->id),
                    $escape($order->name),
                    $escape($order->items->sum('quantity')),
                    $escape(number_format($order->subtotal(), 2)),
                    $escape($order->status),
                    $escape($order->payment_status),
                ]);
            }

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    protected function parseRange(array $validated): array
    {
        $from = isset($validated['from'])
            ? Carbon::parse($validated['from'])->startOfDay()
            : Carbon::now()->subDays(30)->startOfDay();
        $to = isset($validated['to'])
            ? Carbon::parse($validated['to'])->endOfDay()
            : Carbon::now()->endOfDay();

        $maxTo = $from->copy()->addDays(366)->endOfDay();

        if ($to->gt($maxTo)) {
            $to = $maxTo;
        }

        return [$from, $to];
    }

    protected function buildReport(Carbon $from, Carbon $to): array
    {
        $orderQuery = Order::query()
            ->whereBetween('created_at', [$from, $to]);

        $totalOrders = (clone $orderQuery)->count();
        $totalRevenue = (clone $orderQuery)
            ->where('payment_status', 'paid')
            ->sum('total');
        $avgOrderValue = $totalOrders > 0 ? round($totalRevenue / $totalOrders, 2) : 0;

        $topProducts = OrderItem::query()
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->whereBetween('orders.created_at', [$from, $to])
            ->whereNotIn('orders.status', ['cancelled', 'returned'])
            ->select(
                'products.product_title',
                DB::raw('SUM(order_items.quantity) as total_quantity'),
                DB::raw('SUM(order_items.quantity * order_items.price) as total_revenue')
            )
            ->groupBy('products.id', 'products.product_title')
            ->orderByDesc('total_revenue')
            ->take(5)
            ->get();

        $dailySalesByDate = Order::query()
            ->where('payment_status', 'paid')
            ->whereBetween('created_at', [$from, $to])
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
        $ordersCounts = [];
        $dailySales = collect();

        foreach (CarbonPeriod::create($from->copy()->startOfDay(), $to->copy()->startOfDay()) as $date) {
            $key = $date->toDateString();
            $labels[] = $date->format('M d');
            $revenues[] = round((float) ($dailySalesByDate[$key]->revenue ?? 0), 2);
            $ordersCounts[] = (int) ($dailySalesByDate[$key]->orders_count ?? 0);
            $dailySales->push((object) [
                'date' => $date->format('M d, Y'),
                'revenue' => round((float) ($dailySalesByDate[$key]->revenue ?? 0), 2),
                'orders' => (int) ($dailySalesByDate[$key]->orders_count ?? 0),
            ]);
        }

        $salesTrend = [
            'labels' => $labels,
            'revenues' => $revenues,
            'orders' => $ordersCounts,
        ];

        return compact(
            'totalRevenue',
            'totalOrders',
            'avgOrderValue',
            'topProducts',
            'salesTrend',
            'dailySales'
        );
    }
}
