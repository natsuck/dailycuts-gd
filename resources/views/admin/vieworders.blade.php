@extends('admin.maindesign')

@section('page_title', 'Orders')
@section('page_header', 'Orders')
@section('page_subtitle', 'Review purchases, payment state, delivery details, and fulfillment status.')

@section('page_actions')
    <a href="{{ route('dashboard') }}" class="btn btn-outline-primary btn-sm">
        <i class="fa fa-dashboard mr-1"></i> Dashboard
    </a>
@endsection

@push('styles')
<style>
    .orders-dashboard .block {
        border-radius: 8px;
    }

    .orders-dashboard .statistic-block {
        min-height: 120px;
    }

    .orders-dashboard .statistic-block .number {
        font-size: 1.7rem;
        line-height: 1;
    }

    .orders-table {
        margin-bottom: 0;
        table-layout: fixed;
    }

    .orders-table th {
        border-top: 0;
        color: #adb5bd;
        font-size: 0.76rem;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .orders-table td {
        vertical-align: middle;
    }

    .order-id {
        color: #fff;
        font-weight: 700;
    }

    .order-muted {
        color: #adb5bd;
        font-size: 0.82rem;
    }

    .order-customer,
    .order-address,
    .order-notes {
        overflow-wrap: anywhere;
    }

    .order-items {
        list-style: none;
        margin: 0;
        padding: 0;
    }

    .order-items li + li {
        margin-top: 6px;
    }

    .order-item-qty {
        color: #ffc107;
        font-weight: 700;
    }

    .order-badge {
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

    .order-badge-paid,
    .order-badge-delivered {
        background: #28a745;
        color: #fff;
    }

    .order-badge-failed,
    .order-badge-cancelled {
        background: #dc3545;
        color: #fff;
    }

    .order-badge-unpaid,
    .order-badge-returned {
        background: #6c757d;
        color: #fff;
    }

    .order-badge-pending {
        background: #ffc107;
        color: #212529;
    }

    .order-badge-shipped {
        background: #17a2b8;
        color: #fff;
    }

    .order-actions {
        min-width: 150px;
    }

    .order-actions .custom-select {
        background: #2d3035;
        border-color: #444951;
        color: #fff;
        height: calc(1.8125rem + 2px);
        padding-bottom: 0.2rem;
        padding-top: 0.2rem;
    }

    .order-actions .btn {
        border-radius: 4px;
        font-weight: 700;
    }

    .order-summary-text {
        max-width: 720px;
    }

    @media (max-width: 991.98px) {
        .orders-table {
            min-width: 980px;
        }
    }
</style>
@endpush

@section('vieworders')
<div class="container-fluid orders-dashboard">
    @if(session('success'))
        <div class="alert alert-success mb-4">
            {{ session('success') }}
        </div>
    @endif

    <div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
        <div class="order-summary-text mb-3 mb-md-0">
            <h3 class="mb-1">Order Management</h3>
            <p class="text-muted mb-0">Review recent purchases, update fulfillment status, and remove invalid orders.</p>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="statistic-block block">
                <div class="progress-details d-flex align-items-end justify-content-between">
                    <div class="title">
                        <div class="icon"><i class="fa fa-shopping-bag"></i></div><strong>Total Orders</strong>
                    </div>
                    <div class="number dashtext-1">{{ number_format($orderStats['total']) }}</div>
                </div>
                <small class="text-muted">All customer orders</small>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="statistic-block block">
                <div class="progress-details d-flex align-items-end justify-content-between">
                    <div class="title">
                        <div class="icon"><i class="fa fa-clock-o"></i></div><strong>Pending</strong>
                    </div>
                    <div class="number dashtext-2">{{ number_format($orderStats['pending']) }}</div>
                </div>
                <small class="text-muted">Needs admin action</small>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="statistic-block block">
                <div class="progress-details d-flex align-items-end justify-content-between">
                    <div class="title">
                        <div class="icon"><i class="fa fa-credit-card"></i></div><strong>Paid Orders</strong>
                    </div>
                    <div class="number dashtext-3">{{ number_format($orderStats['paid']) }}</div>
                </div>
                <small class="text-muted">Payment confirmed</small>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="statistic-block block">
                <div class="progress-details d-flex align-items-end justify-content-between">
                    <div class="title">
                        <div class="icon"><i class="fa fa-money"></i></div><strong>Paid Revenue</strong>
                    </div>
                    <div class="number dashtext-4">PHP {{ number_format($orderStats['revenue'], 2) }}</div>
                </div>
                <small class="text-muted">Sum of paid order totals</small>
            </div>
        </div>
    </div>

    <div class="block">
        <div class="title d-flex flex-wrap align-items-center justify-content-between mb-3">
            <div>
                <strong>Recent Orders</strong>
                <p class="text-muted mb-0">Showing {{ $orders->firstItem() ?? 0 }} to {{ $orders->lastItem() ?? 0 }} of {{ $orders->total() }} orders.</p>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover orders-table">
                <thead>
                    <tr>
                        <th style="width: 90px;">Order</th>
                        <th style="width: 210px;">Customer</th>
                        <th style="width: 230px;">Delivery</th>
                        <th style="width: 250px;">Items</th>
                        <th style="width: 130px;" class="text-right">Total</th>
                        <th style="width: 150px;">Payment</th>
                        <th style="width: 140px;">Status</th>
                        <th style="width: 170px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                        @php
                            $paymentStatus = $order->payment_status ?: 'unpaid';
                            $orderStatus = $order->status ?: 'pending';
                        @endphp

                        <tr>
                            <td>
                                <div class="order-id">#{{ $order->id }}</div>
                                <div class="order-muted">{{ $order->created_at->format('M d, Y') }}</div>
                                <div class="order-muted">{{ $order->created_at->format('h:i A') }}</div>
                            </td>

                            <td class="order-customer">
                                <strong>{{ $order->name }}</strong>
                                <div class="order-muted">{{ optional($order->user)->email ?? 'Guest / deleted user' }}</div>
                                <div class="order-muted">{{ $order->phone }}</div>
                            </td>

                            <td>
                                <div class="order-address">{{ $order->address }}</div>
                                @if($order->notes)
                                    <div class="order-notes order-muted mt-2">
                                        <strong>Note:</strong> {{ $order->notes }}
                                    </div>
                                @endif
                            </td>

                            <td>
                                <ul class="order-items">
                                    @forelse($order->items as $item)
                                        <li>
                                            {{ optional($item->product)->product_title ?? 'Deleted product' }}
                                            <span class="order-item-qty">x{{ $item->quantity }}</span>
                                        </li>
                                    @empty
                                        <li class="order-muted">No items recorded</li>
                                    @endforelse
                                </ul>
                            </td>

                            <td class="text-right">
                                <strong>PHP {{ number_format($order->total, 2) }}</strong>
                            </td>

                            <td>
                                <span class="order-badge order-badge-{{ $paymentStatus }}">
                                    {{ $paymentStatus === 'paid' ? 'Paid' : ($paymentStatus === 'failed' ? 'Failed' : 'Unpaid') }}
                                </span>
                                <div class="order-muted mt-2">{{ strtoupper($order->payment_method ?? 'N/A') }}</div>
                            </td>

                            <td>
                                <span class="order-badge order-badge-{{ $orderStatus }}">
                                    {{ ucfirst($orderStatus) }}
                                </span>
                            </td>

                            <td class="order-actions">
                                <form action="{{ route('admin.order.updateStatus', $order->id) }}" method="POST">
                                    @csrf
                                    @method('PATCH')

                                    <select name="status" class="custom-select custom-select-sm">
                                        <option value="pending" {{ $orderStatus === 'pending' ? 'selected' : '' }}>Pending</option>
                                        <option value="shipped" {{ $orderStatus === 'shipped' ? 'selected' : '' }}>Shipped</option>
                                        <option value="delivered" {{ $orderStatus === 'delivered' ? 'selected' : '' }}>Delivered</option>
                                        <option value="cancelled" {{ $orderStatus === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                        <option value="returned" {{ $orderStatus === 'returned' ? 'selected' : '' }}>Returned</option>
                                    </select>

                                    <button type="submit" class="btn btn-sm btn-primary btn-block mt-2">
                                        Update
                                    </button>
                                </form>

                                <form action="{{ route('admin.order.delete', $order->id) }}" method="POST" class="mt-2">
                                    @csrf
                                    @method('DELETE')

                                    <button type="submit" class="btn btn-sm btn-outline-danger btn-block" onclick="return confirm('Delete this order?')">
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">No orders found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-center mt-4">
            {{ $orders->links() }}
        </div>
    </div>
</div>
@endsection
