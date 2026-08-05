@extends('admin.maindesign')

@section('page_title', 'Reports')
@section('page_header', 'Reports')
@section('page_subtitle', 'Revenue, order volume, top products, and daily sales within the selected date range.')

@push('styles')
<style>
    .reports-dashboard .block {
        border-radius: 8px;
    }

    .reports-dashboard .statistic-block {
        min-height: 120px;
    }

    .reports-dashboard .statistic-block .number {
        font-size: 1.5rem;
        line-height: 1.1;
    }

    .reports-table {
        margin-bottom: 0;
    }

    .reports-table th {
        border-top: 0;
        color: #adb5bd;
        font-size: 0.76rem;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .reports-table td {
        vertical-align: middle;
    }

    .reports-muted {
        color: #adb5bd;
        font-size: 0.82rem;
    }

    .reports-chart-wrap {
        min-height: 300px;
        position: relative;
    }

    .reports-filter {
        background: #2d3035;
        border: 1px solid #444951;
        color: #fff;
    }

    .reports-filter:focus {
        background: #2d3035;
        border-color: #007bff;
        color: #fff;
    }
</style>
@endpush

@section('reports')
<div class="container-fluid reports-dashboard">
    <div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
        <div class="mb-3 mb-md-0">
            <h3 class="mb-1">Sales Report</h3>
            <p class="text-muted mb-0">{{ \Carbon\Carbon::parse($from)->format('M d, Y') }} to {{ \Carbon\Carbon::parse($to)->format('M d, Y') }}</p>
        </div>

        <div class="d-flex flex-wrap align-items-center">
            <form method="GET" action="{{ route('admin.reports.index') }}" class="form-inline">
                <div class="form-group mr-2 mb-2 mb-sm-0">
                    <label for="report-from" class="sr-only">From</label>
                    <input type="date" id="report-from" name="from" value="{{ request('from', $from) }}" class="form-control form-control-sm reports-filter">
                </div>
                <div class="form-group mr-2 mb-2 mb-sm-0">
                    <label for="report-to" class="sr-only">To</label>
                    <input type="date" id="report-to" name="to" value="{{ request('to', $to) }}" class="form-control form-control-sm reports-filter">
                </div>
                <button type="submit" class="btn btn-primary btn-sm mr-2">
                    <i class="fa fa-filter mr-1"></i> Filter
                </button>
            </form>

            <a href="{{ route('admin.reports.exportCsv', ['from' => $from, 'to' => $to]) }}" class="btn btn-success btn-sm">
                <i class="fa fa-download mr-1"></i> Export CSV
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="statistic-block block">
                <div class="progress-details d-flex align-items-end justify-content-between">
                    <div class="title">
                        <div class="icon"><i class="fa fa-money"></i></div><strong>Total Revenue</strong>
                    </div>
                    <div class="number dashtext-1">PHP {{ number_format($totalRevenue ?? 0, 2) }}</div>
                </div>
                <small class="text-muted">Paid orders only</small>
            </div>
        </div>

        <div class="col-xl-4 col-md-6 mb-4">
            <div class="statistic-block block">
                <div class="progress-details d-flex align-items-end justify-content-between">
                    <div class="title">
                        <div class="icon"><i class="fa fa-shopping-bag"></i></div><strong>Total Orders</strong>
                    </div>
                    <div class="number dashtext-2">{{ number_format($totalOrders ?? 0) }}</div>
                </div>
                <small class="text-muted">All orders in range</small>
            </div>
        </div>

        <div class="col-xl-4 col-md-6 mb-4">
            <div class="statistic-block block">
                <div class="progress-details d-flex align-items-end justify-content-between">
                    <div class="title">
                        <div class="icon"><i class="fa fa-line-chart"></i></div><strong>Average Order Value</strong>
                    </div>
                    <div class="number dashtext-3">PHP {{ number_format($avgOrderValue ?? 0, 2) }}</div>
                </div>
                <small class="text-muted">Revenue per order</small>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-7 mb-4">
            <div class="block">
                <div class="title d-flex align-items-center justify-content-between mb-3">
                    <div>
                        <strong>Daily Sales</strong>
                        <p class="text-muted mb-0">Revenue and paid order count per day</p>
                    </div>
                    <span class="text-muted">{{ count($dailySales ?? []) }} days</span>
                </div>

                <div class="reports-chart-wrap">
                    <canvas id="reportsSalesChart"></canvas>
                </div>

                <div class="table-responsive mt-4">
                    <table class="table table-hover reports-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th class="text-right">Revenue</th>
                                <th class="text-right">Orders</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($dailySales ?? [] as $day)
                                <tr>
                                    <td>{{ $day->date }}</td>
                                    <td class="text-right">PHP {{ number_format($day->revenue, 2) }}</td>
                                    <td class="text-right">{{ $day->orders }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-4">No sales data in this range.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-xl-5 mb-4">
            <div class="block">
                <div class="title mb-3">
                    <strong>Top 5 Products</strong>
                    <p class="text-muted mb-0">Ranked by revenue in range</p>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover reports-table">
                        <thead>
                            <tr>
                                <th>Product Name</th>
                                <th class="text-right">Quantity</th>
                                <th class="text-right">Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($topProducts ?? [] as $product)
                                <tr>
                                    <td>{{ $product->product_title }}</td>
                                    <td class="text-right">{{ $product->total_quantity }}</td>
                                    <td class="text-right">PHP {{ number_format($product->total_revenue, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-4">No product sales in this range.</td>
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
        var canvas = document.getElementById('reportsSalesChart');

        if (!canvas || typeof Chart === 'undefined') {
            return;
        }

        new Chart(canvas.getContext('2d'), {
            type: 'line',
            data: {
                labels: @json($salesTrend['labels'] ?? []),
                datasets: [
                    {
                        label: 'Revenue',
                        data: @json($salesTrend['revenues'] ?? []),
                        backgroundColor: 'rgba(40, 167, 69, 0.14)',
                        borderColor: 'rgba(40, 167, 69, 1)',
                        borderWidth: 2,
                        pointBackgroundColor: 'rgba(40, 167, 69, 1)',
                        pointRadius: 4,
                        yAxisID: 'revenueAxis',
                        lineTension: 0.25
                    },
                    {
                        label: 'Orders',
                        data: @json($salesTrend['orders'] ?? []),
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
    });
</script>
@endpush
