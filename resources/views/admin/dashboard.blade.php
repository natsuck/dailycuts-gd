@extends('admin.maindesign')

@section('page_title', 'Dashboard')
@section('page_header', 'Admin Dashboard')
@section('page_subtitle', 'Live store snapshot for sales, fulfillment, inventory, and product movement.')

@section('page_actions')
    <a href="{{ route('admin.vieworders') }}" class="btn btn-primary btn-sm">
        <i class="fa fa-shopping-bag mr-1"></i> View Orders
    </a>
    <a href="{{ route('admin.simulation.dashboard') }}" class="btn btn-outline-primary btn-sm">
        <i class="fa fa-line-chart mr-1"></i> Simulations
    </a>
@endsection

@php
    $statusLabels = ['Pending', 'Shipped', 'Delivered', 'Cancelled', 'Returned'];
    $statusKeys = ['pending', 'shipped', 'delivered', 'cancelled', 'returned'];
    $statusChartData = collect($statusKeys)->map(fn ($status) => (int) ($orderStatusCounts[$status] ?? 0))->values();
@endphp

@push('styles')
<style>
    .store-dashboard .block {
        border-radius: 8px;
    }

    .dashboard-stat {
        min-height: 132px;
    }

    .dashboard-stat .number {
        font-size: 1.65rem;
        line-height: 1.05;
    }

    .dashboard-card-link {
        color: #8ab4f8;
        font-size: 0.82rem;
        font-weight: 700;
    }

    .dashboard-chart-wrap {
        min-height: 310px;
        position: relative;
    }

    .dashboard-chart-wrap.small {
        min-height: 250px;
    }

    .dashboard-table {
        margin-bottom: 0;
    }

    .dashboard-table th {
        border-top: 0;
        color: #adb5bd;
        font-size: 0.76rem;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .dashboard-table td {
        vertical-align: middle;
    }

    .dashboard-muted {
        color: #adb5bd;
        font-size: 0.82rem;
    }

    .dashboard-badge {
        border-radius: 999px;
        display: inline-block;
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.03em;
        line-height: 1;
        padding: 0.45rem 0.65rem;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .dashboard-badge-pending {
        background: #ffc107;
        color: #212529;
    }

    .dashboard-badge-shipped {
        background: #17a2b8;
        color: #fff;
    }

    .dashboard-badge-delivered,
    .dashboard-badge-paid {
        background: #28a745;
        color: #fff;
    }

    .dashboard-badge-cancelled,
    .dashboard-badge-failed,
    .dashboard-badge-alert {
        background: #dc3545;
        color: #fff;
    }

    .dashboard-badge-returned,
    .dashboard-badge-unpaid {
        background: #6c757d;
        color: #fff;
    }

    .dashboard-list {
        list-style: none;
        margin: 0;
        padding: 0;
    }

    .dashboard-list li {
        align-items: center;
        border-bottom: 1px solid rgba(255, 255, 255, 0.06);
        display: flex;
        justify-content: space-between;
        padding: 0.85rem 0;
    }

    .dashboard-list li:first-child {
        padding-top: 0;
    }

    .dashboard-list li:last-child {
        border-bottom: 0;
        padding-bottom: 0;
    }

    .dashboard-product-name {
        color: #fff;
        font-weight: 700;
        overflow-wrap: anywhere;
    }
</style>
@endpush

@section('dashboard')
<div class="container-fluid store-dashboard">
    <div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
        <div class="mb-3 mb-md-0">
            <h3 class="mb-1">Operations Overview</h3>
            <p class="text-muted mb-0">This month: PHP {{ number_format($dashboardStats['monthRevenue'], 2) }} paid revenue across current store activity.</p>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="statistic-block block dashboard-stat">
                <div class="progress-details d-flex align-items-end justify-content-between">
                    <div class="title">
                        <div class="icon"><i class="fa fa-money"></i></div><strong>Paid Revenue</strong>
                    </div>
                    <div class="number dashtext-1">PHP {{ number_format($dashboardStats['paidRevenue'], 2) }}</div>
                </div>
                <small class="text-muted">Today: PHP {{ number_format($dashboardStats['todayRevenue'], 2) }}</small>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="statistic-block block dashboard-stat">
                <div class="progress-details d-flex align-items-end justify-content-between">
                    <div class="title">
                        <div class="icon"><i class="fa fa-shopping-cart"></i></div><strong>Orders Today</strong>
                    </div>
                    <div class="number dashtext-2">{{ number_format($dashboardStats['ordersToday']) }}</div>
                </div>
                <small class="text-muted">{{ number_format($dashboardStats['totalOrders']) }} total orders</small>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="statistic-block block dashboard-stat">
                <div class="progress-details d-flex align-items-end justify-content-between">
                    <div class="title">
                        <div class="icon"><i class="fa fa-clock-o"></i></div><strong>Pending Orders</strong>
                    </div>
                    <div class="number dashtext-3">{{ number_format($dashboardStats['pendingOrders']) }}</div>
                </div>
                <a href="{{ route('admin.vieworders') }}" class="dashboard-card-link">Manage fulfillment</a>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="statistic-block block dashboard-stat">
                <div class="progress-details d-flex align-items-end justify-content-between">
                    <div class="title">
                        <div class="icon"><i class="fa fa-warning"></i></div><strong>Inventory Alerts</strong>
                    </div>
                    <div class="number dashtext-4">{{ number_format($dashboardStats['lowStockProducts'] + $dashboardStats['expiringProducts']) }}</div>
                </div>
                <small class="text-muted">{{ $dashboardStats['lowStockProducts'] }} low stock, {{ $dashboardStats['expiringProducts'] }} expiring, {{ $dashboardStats['totalProducts'] }} products</small>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-8 mb-4">
            <div class="block">
                <div class="title mb-3">
                    <strong>7-Day Paid Sales</strong>
                    <span class="text-muted ml-2">Revenue and paid order count</span>
                </div>
                <div class="dashboard-chart-wrap">
                    <canvas id="dashboardSalesChart"></canvas>
                </div>
            </div>
        </div>

        <div class="col-xl-4 mb-4">
            <div class="block">
                <div class="title mb-3">
                    <strong>Order Status Mix</strong>
                    <span class="text-muted ml-2">All orders</span>
                </div>
                <div class="dashboard-chart-wrap small">
                    <canvas id="dashboardStatusChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-8 mb-4">
            <div class="block">
                <div class="title d-flex align-items-center justify-content-between mb-3">
                    <div>
                        <strong>Recent Orders</strong>
                        <p class="text-muted mb-0">Latest customer activity</p>
                    </div>
                    <a href="{{ route('admin.vieworders') }}" class="dashboard-card-link">View all</a>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover dashboard-table">
                        <thead>
                            <tr>
                                <th>Order</th>
                                <th>Customer</th>
                                <th class="text-right">Total</th>
                                <th>Payment</th>
                                <th>Status</th>
                                <th>Placed</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentOrders as $order)
                                @php
                                    $paymentStatus = $order->payment_status ?: 'unpaid';
                                    $orderStatus = $order->status ?: 'pending';
                                @endphp
                                <tr>
                                    <td><strong>#{{ $order->id }}</strong></td>
                                    <td>
                                        {{ $order->name }}
                                        <div class="dashboard-muted">{{ optional($order->user)->email ?? 'Guest / deleted user' }}</div>
                                    </td>
                                    <td class="text-right">PHP {{ number_format($order->total, 2) }}</td>
                                    <td>
                                        <span class="dashboard-badge dashboard-badge-{{ $paymentStatus }}">
                                            {{ $paymentStatus === 'paid' ? 'Paid' : ($paymentStatus === 'failed' ? 'Failed' : 'Unpaid') }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="dashboard-badge dashboard-badge-{{ $orderStatus }}">
                                            {{ ucfirst($orderStatus) }}
                                        </span>
                                    </td>
                                    <td>
                                        {{ $order->created_at->format('M d') }}
                                        <div class="dashboard-muted">{{ $order->created_at->format('h:i A') }}</div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">No recent orders yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-xl-4 mb-4">
            <div class="block">
                <div class="title mb-3">
                    <strong>Best-Selling Products</strong>
                    <p class="text-muted mb-0">Ranked by quantity sold</p>
                </div>

                <ul class="dashboard-list">
                    @forelse($topProducts as $product)
                        <li>
                            <div>
                                <div class="dashboard-product-name">{{ $product->product_title }}</div>
                                <div class="dashboard-muted">PHP {{ number_format($product->total_sales, 2) }} sales</div>
                            </div>
                            <span class="dashboard-badge dashboard-badge-paid">{{ number_format($product->total_quantity) }} sold</span>
                        </li>
                    @empty
                        <li class="text-muted">No product sales yet.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-6 mb-4">
            <div class="block">
                <div class="title d-flex align-items-center justify-content-between mb-3">
                    <div>
                        <strong>Low Stock Products</strong>
                        <p class="text-muted mb-0">Current stock below reorder level</p>
                    </div>
                    <a href="{{ route('admin.simulation.reorder') }}" class="dashboard-card-link">Open simulation</a>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover dashboard-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th class="text-right">Stock</th>
                                <th class="text-right">Reorder Level</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($lowStockProducts as $product)
                                <tr>
                                    <td>{{ $product->product_title }}</td>
                                    <td class="text-right">{{ number_format($product->product_quantity) }}</td>
                                    <td class="text-right">{{ number_format($product->reorder_level) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-4">No low stock products.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-xl-6 mb-4">
            <div class="block">
                <div class="title d-flex align-items-center justify-content-between mb-3">
                    <div>
                        <strong>Expiring Products</strong>
                        <p class="text-muted mb-0">Stock expiring within 3 days</p>
                    </div>
                    <a href="{{ route('admin.simulation.spoilage') }}" class="dashboard-card-link">Open simulation</a>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover dashboard-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th class="text-right">Stock</th>
                                <th>Expiry Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($expiringProducts as $product)
                                <tr>
                                    <td>{{ $product->product_title }}</td>
                                    <td class="text-right">{{ number_format($product->product_quantity) }}</td>
                                    <td>
                                        <span class="dashboard-badge dashboard-badge-alert">
                                            {{ optional($product->expiry_date)->format('M d, Y') }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-4">No products expiring soon.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof Chart === 'undefined') {
            return;
        }

        var salesCanvas = document.getElementById('dashboardSalesChart');
        var statusCanvas = document.getElementById('dashboardStatusChart');

        if (salesCanvas) {
            new Chart(salesCanvas.getContext('2d'), {
                type: 'line',
                data: {
                    labels: @json($salesTrend['labels']),
                    datasets: [
                        {
                            label: 'Revenue',
                            data: @json($salesTrend['revenues']),
                            backgroundColor: 'rgba(54, 162, 235, 0.16)',
                            borderColor: 'rgba(54, 162, 235, 1)',
                            borderWidth: 2,
                            pointBackgroundColor: 'rgba(54, 162, 235, 1)',
                            pointRadius: 4,
                            yAxisID: 'revenueAxis',
                            lineTension: 0.25
                        },
                        {
                            label: 'Orders',
                            data: @json($salesTrend['orders']),
                            backgroundColor: 'rgba(255, 193, 7, 0.14)',
                            borderColor: 'rgba(255, 193, 7, 1)',
                            borderWidth: 2,
                            pointBackgroundColor: 'rgba(255, 193, 7, 1)',
                            pointRadius: 4,
                            yAxisID: 'ordersAxis',
                            lineTension: 0.25
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    legend: {
                        labels: {
                            fontColor: '#adb5bd'
                        }
                    },
                    scales: {
                        yAxes: [
                            {
                                id: 'revenueAxis',
                                position: 'left',
                                ticks: {
                                    beginAtZero: true,
                                    fontColor: '#adb5bd'
                                },
                                gridLines: {
                                    color: 'rgba(255, 255, 255, 0.06)'
                                }
                            },
                            {
                                id: 'ordersAxis',
                                position: 'right',
                                ticks: {
                                    beginAtZero: true,
                                    precision: 0,
                                    fontColor: '#adb5bd'
                                },
                                gridLines: {
                                    drawOnChartArea: false
                                }
                            }
                        ],
                        xAxes: [{
                            ticks: {
                                fontColor: '#adb5bd'
                            },
                            gridLines: {
                                color: 'rgba(255, 255, 255, 0.06)'
                            }
                        }]
                    }
                }
            });
        }

        if (statusCanvas) {
            new Chart(statusCanvas.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: @json($statusLabels),
                    datasets: [{
                        data: @json($statusChartData),
                        backgroundColor: [
                            '#ffc107',
                            '#17a2b8',
                            '#28a745',
                            '#dc3545',
                            '#6c757d'
                        ],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    legend: {
                        position: 'bottom',
                        labels: {
                            fontColor: '#adb5bd'
                        }
                    }
                }
            });
        }
    });
</script>
@endpush
