<?php

namespace App\Http\Controllers;

use App\Models\OrderItem;
use App\Models\Product;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;

class AdminSimulationController extends Controller
{
    public function dashboard()
    {
        return view('admin.simulation-dashboard', $this->simulationData());
    }

    public function forecast()
    {
        return view('admin.simulation-dashboard', $this->simulationData('forecast'));
    }

    public function reorder()
    {
        return view('admin.simulation-dashboard', $this->simulationData('reorder'));
    }

    public function spoilage()
    {
        return view('admin.simulation-dashboard', $this->simulationData('spoilage'));
    }

    private function simulationData(string $activeSection = 'overview'): array
    {
        $today = Carbon::today();
        $startDate = $today->copy()->subDays(6)->startOfDay();
        $endDate = $today->copy()->endOfDay();

        $products = Product::query()
            ->select('id', 'product_title', 'product_quantity', 'reorder_level', 'expiry_date')
            ->orderBy('product_title')
            ->get();

        $forecastRows = $this->forecastRows($products, $startDate, $endDate);
        $reorderRows = $this->reorderRows($products);
        $spoilageRows = $this->spoilageRows($products, $today);
        $salesTrend = $this->salesTrend($startDate, $endDate);

        return [
            'activeSection' => $activeSection,
            'forecastRows' => $forecastRows,
            'reorderRows' => $reorderRows,
            'spoilageRows' => $spoilageRows,
            'salesTrendLabels' => $salesTrend['labels'],
            'salesTrendQuantities' => $salesTrend['quantities'],
            'summary' => [
                'totalProducts' => $products->count(),
                'lowStockCount' => $reorderRows->where('status', 'LOW STOCK')->count(),
                'highRiskCount' => $spoilageRows->where('risk_level', 'HIGH RISK')->count(),
                'forecastTotal' => round($forecastRows->sum('predicted_tomorrow_demand'), 2),
                'topForecastProduct' => $forecastRows
                    ->sortByDesc('predicted_tomorrow_demand')
                    ->first()['product_name'] ?? 'No products yet',
            ],
            'forecastWindow' => [
                'from' => $startDate->toFormattedDateString(),
                'to' => $endDate->toFormattedDateString(),
                'tomorrow' => $today->copy()->addDay()->toFormattedDateString(),
            ],
        ];
    }

    private function forecastRows($products, Carbon $startDate, Carbon $endDate)
    {
        $soldQuantities = OrderItem::query()
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereBetween('orders.created_at', [$startDate, $endDate])
            ->whereNotIn('orders.status', ['cancelled', 'returned'])
            ->select('order_items.product_id', DB::raw('COALESCE(SUM(order_items.quantity), 0) as total_sold'))
            ->groupBy('order_items.product_id')
            ->pluck('total_sold', 'product_id');

        return $products->map(function (Product $product) use ($soldQuantities) {
            $totalSold = (int) ($soldQuantities[$product->id] ?? 0);
            $averageDailyDemand = round($totalSold / 7, 2);

            return [
                'product_name' => $product->product_title,
                'total_sold_last_7_days' => $totalSold,
                'avg_daily_demand' => $averageDailyDemand,
                'predicted_tomorrow_demand' => $averageDailyDemand,
            ];
        });
    }

    private function reorderRows($products)
    {
        return $products->map(function (Product $product) {
            $currentStock = (int) ($product->product_quantity ?? 0);
            $reorderLevel = (int) ($product->reorder_level ?? 0);
            $isLowStock = $currentStock < $reorderLevel;

            return [
                'product_name' => $product->product_title,
                'current_stock' => $currentStock,
                'reorder_level' => $reorderLevel,
                'status' => $isLowStock ? 'LOW STOCK' : 'OK',
                'suggested_reorder_qty' => $reorderLevel * 2,
            ];
        });
    }

    private function spoilageRows($products, Carbon $today)
    {
        $riskWindowEnd = $today->copy()->addDays(3)->endOfDay();

        return $products->map(function (Product $product) use ($today, $riskWindowEnd) {
            $currentStock = (int) ($product->product_quantity ?? 0);
            $expiryDate = $product->expiry_date ? Carbon::parse($product->expiry_date)->startOfDay() : null;
            $daysRemaining = $expiryDate ? (int) $today->diffInDays($expiryDate, false) : null;

            // High risk means sell or remove soon: expiring within 3 days while stock remains.
            $isHighRisk = $expiryDate !== null
                && $expiryDate->lte($riskWindowEnd)
                && $currentStock > 0;

            return [
                'product_name' => $product->product_title,
                'days_remaining' => $daysRemaining,
                'stock_left' => $currentStock,
                'risk_level' => $isHighRisk ? 'HIGH RISK' : 'LOW RISK',
                'expiry_date' => $expiryDate?->toFormattedDateString(),
            ];
        });
    }

    private function salesTrend(Carbon $startDate, Carbon $endDate): array
    {
        $dailyTotals = OrderItem::query()
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereBetween('orders.created_at', [$startDate, $endDate])
            ->whereNotIn('orders.status', ['cancelled', 'returned'])
            ->select(DB::raw('DATE(orders.created_at) as sale_date'), DB::raw('COALESCE(SUM(order_items.quantity), 0) as quantity_sold'))
            ->groupBy('sale_date')
            ->orderBy('sale_date')
            ->pluck('quantity_sold', 'sale_date');

        $labels = [];
        $quantities = [];

        foreach (CarbonPeriod::create($startDate->copy()->startOfDay(), $endDate->copy()->startOfDay()) as $date) {
            $key = $date->toDateString();
            $labels[] = $date->format('M d');
            $quantities[] = (int) ($dailyTotals[$key] ?? 0);
        }

        return [
            'labels' => $labels,
            'quantities' => $quantities,
        ];
    }
}
