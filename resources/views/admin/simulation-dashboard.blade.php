@extends('admin.maindesign')

@section('page_title', 'Modeling and Simulation')
@section('page_header', 'Modeling and Simulation')
@section('page_subtitle', 'Forecast demand, simulate reorder alerts, and flag spoilage risk from live store data.')

@section('page_actions')
    <a href="{{ route('dashboard') }}" class="btn btn-outline-primary btn-sm">
        <i class="fa fa-dashboard mr-1"></i> Dashboard
    </a>
@endsection

@push('styles')
<style>
    .simulation-dashboard .block {
        border-radius: 8px;
    }

    .simulation-toolbar {
        gap: 8px;
    }

    .simulation-stat-card {
        min-height: 132px;
    }

    .simulation-stat-card .number {
        font-size: 1.8rem;
        line-height: 1;
    }

    .simulation-section {
        scroll-margin-top: 90px;
    }

    .simulation-table th,
    .simulation-table td {
        vertical-align: middle;
    }

    .simulation-chart-wrap {
        position: relative;
        min-height: 280px;
    }

    .badge-status {
        font-size: 0.78rem;
        letter-spacing: 0.02em;
        padding: 0.45rem 0.65rem;
    }

    .badge-ok {
        background: #28a745;
        color: #fff;
    }

    .badge-low,
    .badge-risk {
        background: #dc3545;
        color: #fff;
    }

    .badge-safe {
        background: #17a2b8;
        color: #fff;
    }
</style>
@endpush

@section('dashboard')
<div class="container-fluid simulation-dashboard">
    <div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
        <div class="mb-3 mb-md-0">
            <h3 class="mb-1">Admin Analytics Dashboard</h3>
            <p class="text-muted mb-0">
                Forecast window: {{ $forecastWindow['from'] }} to {{ $forecastWindow['to'] }}.
                Prediction target: {{ $forecastWindow['tomorrow'] }}.
            </p>
        </div>

        <div class="btn-group simulation-toolbar" role="group" aria-label="Simulation views">
            <a href="{{ route('admin.simulation.dashboard') }}" class="btn btn-sm {{ $activeSection === 'overview' ? 'btn-primary' : 'btn-outline-primary' }}">Overview</a>
            <a href="{{ route('admin.simulation.forecast') }}#forecast-section" class="btn btn-sm {{ $activeSection === 'forecast' ? 'btn-primary' : 'btn-outline-primary' }}">Forecast</a>
            <a href="{{ route('admin.simulation.reorder') }}#reorder-section" class="btn btn-sm {{ $activeSection === 'reorder' ? 'btn-primary' : 'btn-outline-primary' }}">Reorder</a>
            <a href="{{ route('admin.simulation.spoilage') }}#spoilage-section" class="btn btn-sm {{ $activeSection === 'spoilage' ? 'btn-primary' : 'btn-outline-primary' }}">Spoilage</a>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="statistic-block block simulation-stat-card">
                <div class="progress-details d-flex align-items-end justify-content-between">
                    <div class="title">
                        <div class="icon"><i class="fa fa-cubes"></i></div><strong>Total Products</strong>
                    </div>
                    <div class="number dashtext-1">{{ number_format($summary['totalProducts']) }}</div>
                </div>
                <small class="text-muted">Products included in all simulations</small>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="statistic-block block simulation-stat-card">
                <div class="progress-details d-flex align-items-end justify-content-between">
                    <div class="title">
                        <div class="icon"><i class="fa fa-warning"></i></div><strong>Low Stock Count</strong>
                    </div>
                    <div class="number dashtext-2">{{ number_format($summary['lowStockCount']) }}</div>
                </div>
                <small class="text-muted">Products below reorder level</small>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="statistic-block block simulation-stat-card">
                <div class="progress-details d-flex align-items-end justify-content-between">
                    <div class="title">
                        <div class="icon"><i class="fa fa-calendar-times-o"></i></div><strong>High Risk Count</strong>
                    </div>
                    <div class="number dashtext-3">{{ number_format($summary['highRiskCount']) }}</div>
                </div>
                <small class="text-muted">Expiring within the next 3 days</small>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="statistic-block block simulation-stat-card">
                <div class="progress-details d-flex align-items-end justify-content-between">
                    <div class="title">
                        <div class="icon"><i class="fa fa-line-chart"></i></div><strong>Forecast Summary</strong>
                    </div>
                    <div class="number dashtext-4">{{ number_format($summary['forecastTotal'], 2) }}</div>
                </div>
                <small class="text-muted">Predicted units tomorrow; top: {{ $summary['topForecastProduct'] }}</small>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-lg-12">
            <div class="block">
                <div class="title mb-3">
                    <strong>7-Day Sales Trend</strong>
                    <span class="text-muted ml-2">Total quantity sold per day</span>
                </div>
                <div class="simulation-chart-wrap">
                    <canvas id="salesTrendChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div id="forecast-section" class="block simulation-section mb-4">
        <div class="title mb-3 d-flex flex-wrap align-items-center justify-content-between">
            <div>
                <strong>Demand Forecasting Simulation</strong>
                <p class="text-muted mb-0">Formula: last 7 days quantity sold / 7.</p>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-striped table-hover simulation-table">
                <thead>
                    <tr>
                        <th>Product Name</th>
                        <th class="text-right">Sold Last 7 Days</th>
                        <th class="text-right">Avg Daily Demand</th>
                        <th class="text-right">Predicted Tomorrow Demand</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($forecastRows as $row)
                        <tr>
                            <td>{{ $row['product_name'] }}</td>
                            <td class="text-right">{{ number_format($row['total_sold_last_7_days']) }}</td>
                            <td class="text-right">{{ number_format($row['avg_daily_demand'], 2) }}</td>
                            <td class="text-right"><strong>{{ number_format($row['predicted_tomorrow_demand'], 2) }}</strong></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted">No products found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div id="reorder-section" class="block simulation-section mb-4">
        <div class="title mb-3">
            <strong>Inventory Reorder Simulation</strong>
            <p class="text-muted mb-0">Low stock is triggered when current stock is less than the reorder level.</p>
        </div>

        <div class="table-responsive">
            <table class="table table-striped table-hover simulation-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th class="text-right">Current Stock</th>
                        <th class="text-right">Reorder Level</th>
                        <th>Status</th>
                        <th class="text-right">Suggested Reorder Qty</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reorderRows as $row)
                        <tr>
                            <td>{{ $row['product_name'] }}</td>
                            <td class="text-right">{{ number_format($row['current_stock']) }}</td>
                            <td class="text-right">{{ number_format($row['reorder_level']) }}</td>
                            <td>
                                <span class="badge badge-status {{ $row['status'] === 'LOW STOCK' ? 'badge-low' : 'badge-ok' }}">
                                    {{ $row['status'] }}
                                </span>
                            </td>
                            <td class="text-right">{{ number_format($row['suggested_reorder_qty']) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted">No products found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div id="spoilage-section" class="block simulation-section mb-4">
        <div class="title mb-3">
            <strong>Spoilage Risk Simulation</strong>
            <p class="text-muted mb-0">High risk means the product expires within 3 days and still has stock.</p>
        </div>

        <div class="table-responsive">
            <table class="table table-striped table-hover simulation-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Expiry Date</th>
                        <th class="text-right">Days Remaining</th>
                        <th class="text-right">Stock Left</th>
                        <th>Risk Level</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($spoilageRows as $row)
                        <tr>
                            <td>{{ $row['product_name'] }}</td>
                            <td>{{ $row['expiry_date'] ?? 'N/A' }}</td>
                            <td class="text-right">{{ $row['days_remaining'] ?? 'N/A' }}</td>
                            <td class="text-right">{{ number_format($row['stock_left']) }}</td>
                            <td>
                                <span class="badge badge-status {{ $row['risk_level'] === 'HIGH RISK' ? 'badge-risk' : 'badge-safe' }}">
                                    {{ $row['risk_level'] }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted">No products found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var canvas = document.getElementById('salesTrendChart');

        if (!canvas || typeof Chart === 'undefined') {
            return;
        }

        new Chart(canvas.getContext('2d'), {
            type: 'line',
            data: {
                labels: @json($salesTrendLabels),
                datasets: [{
                    label: 'Quantity Sold',
                    data: @json($salesTrendQuantities),
                    backgroundColor: 'rgba(54, 162, 235, 0.18)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 2,
                    pointBackgroundColor: 'rgba(54, 162, 235, 1)',
                    pointRadius: 4,
                    lineTension: 0.25
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                legend: {
                    display: false
                },
                scales: {
                    yAxes: [{
                        ticks: {
                            beginAtZero: true,
                            precision: 0
                        }
                    }]
                }
            }
        });
    });
</script>
@endpush
