@extends('admin.maindesign')

@section('inventory')

@if(session('success'))
    <div style="margin-bottom: 10px; color: black; background-color: lightgreen; padding: 10px;">
        {{ session('success') }}
    </div>
@endif

<div class="list-inline-item" style="margin-bottom: 15px;">
    <form action="{{ route('admin.inventory.index') }}" method="GET" style="display:inline;">
        <input type="search" name="search" value="{{ request('search') }}" placeholder="Search products..." style="padding: 8px;">
        <button type="submit" style="background-color: #0d6efd; color: #fff; padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer;">Search</button>
    </form>
</div>

<table style="width: 100%; border-collapse: collapse; font-family: Arial, sans-serif;">
    <thead>
    <tr style="background-color: #f2f2f2;">
        <th class="px-3 py-2 text-start border-bottom">Product Name</th>
        <th class="px-3 py-2 text-start border-bottom">Current Stock</th>
        <th class="px-3 py-2 text-start border-bottom">Reorder Level</th>
        <th class="px-3 py-2 text-start border-bottom">Expiry Date</th>
        <th class="px-3 py-2 text-start border-bottom">Actions</th>
    </tr>
    </thead>
    <tbody>
    @foreach($products as $product)
    <tr style="border-bottom: 1px solid #ddd; {{ $product->product_quantity <= $product->reorder_level ? 'background-color: #fff3cd;' : '' }}">
        <td style="padding: 12px;">{{ $product->product_title }}</td>
        <td style="padding: 12px;">{{ $product->product_quantity }}</td>
        <td style="padding: 12px;">{{ $product->reorder_level }}</td>
        <td style="padding: 12px;">{{ $product->expiry_date ? \Carbon\Carbon::parse($product->expiry_date)->format('M d, Y') : 'N/A' }}</td>
        <td style="padding: 12px;">
            <a href="#adjust-{{ $product->id }}" style="color: #0d6efd; margin-right: 10px;" data-toggle="modal">Adjust Stock</a>
            <a href="{{ route('admin.inventory.history', $product->id) }}" style="color: #6c757d;">View History</a>

            <div class="modal fade" id="adjust-{{ $product->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Adjust Stock - {{ $product->product_title }}</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <form action="{{ route('admin.inventory.adjust', $product->id) }}" method="POST">
                            @csrf
                            <div class="modal-body">
                                <div style="margin-bottom: 15px;">
                                    <label style="display:block; margin-bottom:5px;"><strong>Current Stock: {{ $product->product_quantity }}</strong></label>
                                </div>
                                <div style="margin-bottom: 15px;">
                                    <label for="type-{{ $product->id }}" style="display:block; margin-bottom:5px;">Type</label>
                                    <select name="type" id="type-{{ $product->id }}" style="width: 100%; padding: 8px;">
                                        <option value="restock">Restock</option>
                                        <option value="adjustment">Set Stock</option>
                                    </select>
                                </div>
                                <div style="margin-bottom: 15px;">
                                    <label for="quantity-{{ $product->id }}" style="display:block; margin-bottom:5px;">Quantity</label>
                                    <input type="number" name="quantity" id="quantity-{{ $product->id }}" min="1" value="1" style="width: 100%; padding: 8px;" required>
                                </div>
                                <div style="margin-bottom: 15px;">
                                    <label for="notes-{{ $product->id }}" style="display:block; margin-bottom:5px;">Notes</label>
                                    <textarea name="notes" id="notes-{{ $product->id }}" rows="3" style="width: 100%; padding: 8px;"></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-primary">Save Changes</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </td>
    </tr>
    @endforeach
    </tbody>
</table>

<div class="d-flex justify-content-center mt-3">
    {{ $products->links() }}
</div>

@endsection
