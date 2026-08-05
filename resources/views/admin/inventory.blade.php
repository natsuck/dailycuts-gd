@extends('admin.maindesign')

@section('page_title', 'Inventory')
@section('page_header', 'Inventory')
@section('page_subtitle', 'Monitor current stock levels, reorder points, and expiry dates across all products.')

@push('styles')
<style>
    .inventory-dashboard .block {
        border-radius: 8px;
    }

    .inventory-table {
        margin-bottom: 0;
    }

    .inventory-table th {
        border-top: 0;
        color: #adb5bd;
        font-size: 0.76rem;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .inventory-table td {
        vertical-align: middle;
    }

    .inventory-muted {
        color: #adb5bd;
        font-size: 0.82rem;
    }

    .inventory-row-low {
        background: rgba(255, 193, 7, 0.08);
    }

    .inventory-badge {
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

    .inventory-badge-low {
        background: #ffc107;
        color: #212529;
    }

    .inventory-badge-ok {
        background: #28a745;
        color: #fff;
    }

    .inventory-badge-expiring {
        background: #dc3545;
        color: #fff;
    }

    .inventory-search {
        background: #2d3035;
        border-color: #444951;
        color: #fff;
    }

    .inventory-search:focus {
        background: #2d3035;
        border-color: #007bff;
        color: #fff;
    }

    .inventory-modal .modal-content {
        background: #2d3035;
        border-radius: 8px;
    }

    .inventory-modal .modal-header,
    .inventory-modal .modal-footer {
        border-color: rgba(255, 255, 255, 0.08);
    }

    .inventory-modal .close {
        color: #fff;
        text-shadow: none;
    }

    .inventory-modal .form-control {
        background: #3a3e44;
        border-color: #444951;
        color: #fff;
    }
</style>
@endpush

@section('inventory')
<div class="container-fluid inventory-dashboard">
    @if(session('success'))
        <div class="alert alert-success">
            <i class="fa fa-check mr-1"></i> {{ session('success') }}
        </div>
    @endif

    <div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
        <div class="mb-3 mb-md-0">
            <h3 class="mb-1">Stock Management</h3>
            <p class="text-muted mb-0">
                @if(request()->routeIs('admin.inventory.lowStock'))
                    Showing products that are low on stock.
                @else
                    Showing {{ $products->total() }} products.
                @endif
            </p>
        </div>

        <div class="d-flex flex-wrap align-items-center">
            <form action="{{ route('admin.inventory.index') }}" method="GET" class="form-inline mr-2">
                <div class="form-group mr-2 mb-2 mb-sm-0">
                    <label for="inventory-search" class="sr-only">Search products</label>
                    <input type="search" id="inventory-search" name="search" value="{{ request('search') }}" placeholder="Search products..." class="form-control form-control-sm inventory-search">
                </div>
                <button type="submit" class="btn btn-primary btn-sm mr-2 mb-2 mb-sm-0">
                    <i class="fa fa-search mr-1"></i> Search
                </button>
            </form>

            <a href="{{ route('admin.inventory.lowStock') }}" class="btn btn-outline-danger btn-sm">
                <i class="fa fa-warning mr-1"></i> Low Stock
            </a>
        </div>
    </div>

    <div class="block">
        <div class="title d-flex flex-wrap align-items-center justify-content-between mb-3">
            <div>
                <strong>Product Inventory</strong>
                <p class="text-muted mb-0">Current stock versus reorder level</p>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover inventory-table">
                <thead>
                    <tr>
                        <th>Product Name</th>
                        <th class="text-right">Current Stock</th>
                        <th class="text-right">Reorder Level</th>
                        <th>Status</th>
                        <th>Expiry Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                        @php $isLow = $product->isLowStock(); @endphp
                        <tr class="{{ $isLow ? 'inventory-row-low' : '' }}">
                            <td>
                                <strong>{{ $product->product_title }}</strong>
                            </td>
                            <td class="text-right">
                                <strong>{{ number_format($product->product_quantity) }}</strong>
                            </td>
                            <td class="text-right">{{ $product->reorder_level !== null ? number_format($product->reorder_level) : '—' }}</td>
                            <td>
                                @if($isLow)
                                    <span class="inventory-badge inventory-badge-low">Low Stock</span>
                                @else
                                    <span class="inventory-badge inventory-badge-ok">In Stock</span>
                                @endif
                            </td>
                            <td>
                                @if($product->expiry_date)
                                    @if(\Carbon\Carbon::parse($product->expiry_date)->lte(now()->addDays(3)))
                                        <span class="inventory-badge inventory-badge-expiring">{{ \Carbon\Carbon::parse($product->expiry_date)->format('M d, Y') }}</span>
                                    @else
                                        {{ \Carbon\Carbon::parse($product->expiry_date)->format('M d, Y') }}
                                    @endif
                                @else
                                    <span class="inventory-muted">N/A</span>
                                @endif
                            </td>
                            <td>
                                <button type="button" class="btn btn-outline-primary btn-sm mr-1" data-toggle="modal" data-target="#adjust-{{ $product->id }}">
                                    <i class="fa fa-sliders mr-1"></i> Adjust Stock
                                </button>
                                <a href="{{ route('admin.inventory.history', $product->id) }}" class="btn btn-outline-secondary btn-sm">
                                    <i class="fa fa-history mr-1"></i> View History
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">No products found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-center mt-4">
            {{ $products->links() }}
        </div>
    </div>
</div>

@foreach($products as $product)
    <div class="modal fade inventory-modal" id="adjust-{{ $product->id }}" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Adjust Stock — {{ $product->product_title }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('admin.inventory.adjust', $product->id) }}" method="POST" class="inventory-adjust-form">
                    @csrf
                    <div class="modal-body">
                        <div class="alert alert-secondary py-2">
                            Current Stock: <strong>{{ number_format($product->product_quantity) }}</strong>
                        </div>
                        <div class="form-group">
                            <label for="type-{{ $product->id }}">Type</label>
                            <select name="type" id="type-{{ $product->id }}" class="form-control" required>
                                <option value="restock">Restock</option>
                                <option value="adjustment">Set Stock</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="quantity-{{ $product->id }}">Quantity</label>
                            <input type="number" name="quantity" id="quantity-{{ $product->id }}" class="form-control" min="1" value="1" required>
                        </div>
                        <div class="form-group mb-0">
                            <label for="notes-{{ $product->id }}">Notes</label>
                            <textarea name="notes" id="notes-{{ $product->id }}" rows="3" class="form-control"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-check mr-1"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endforeach

@push('scripts')
<script>
    document.querySelectorAll('.inventory-adjust-form').forEach(function (form) {
        form.addEventListener('submit', function () {
            var btn = form.querySelector('button[type="submit"]');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fa fa-spinner fa-spin mr-1"></i> Saving...';
            }
        });
    });
</script>
@endpush
@endsection
