@extends('admin.maindesign')

@section('view_product')

@if(session('deletecategory_message'))
    <div style="margin-bottom: 10px; color: black; background-color: orangered;">
        {{ session('deletecategory_message') }}
    </div>
@endif

@if(session('deleteproduct_message'))
    <div style="margin-bottom: 10px; color: black; background-color: orangered;">
        {{ session('deleteproduct_message') }}
    </div>
@endif

<div class="list-inline-item">
    <form action="{{ route('admin.searchproduct') }}" method="POST">
        @csrf
        <div class="form-group">
            <input type="search" name="search" placeholder="What are you searching for...">
            <button type="submit" class="submit">Search</button>
        </div>
    </form>
</div>

<table style="width: 100%; border-collapse: collapse; font-family: Arial, sans-serif;">
    <thead>
    <tr style="background-color: #f2f2f2;">
        <th class="px-3 py-2 text-start border-bottom">Product Name</th>
        <th class="px-3 py-2 text-start border-bottom">Product Description</th>
        <th class="px-3 py-2 text-start border-bottom">Product Quantity</th>
        <th class="px-3 py-2 text-start border-bottom">Product Price</th>
        <th class="px-3 py-2 text-start border-bottom">Product Image</th>
        <th class="px-3 py-2 text-start border-bottom">Product Category</th>
        <th class="px-3 py-2 text-start border-bottom">Action</th>
    </tr>
    </thead>
    <tbody>
    @foreach($products as $product)
    <tr style="border-bottom: 1px solid #ddd;">
        <td style="padding: 12px;">{{ $product->product_title }}</td>
        <td style="padding: 12px;">{{ Str::limit($product->product_description, 50) }}</td>
        <td style="padding: 12px;">{{ $product->product_quantity }}</td>
        <td style="padding: 12px;">{{ $product->product_price }}</td>
        <td style="padding: 12px;">
            <img style="width: 150px;" src="{{ asset('products/'.$product->product_image) }}">
        </td>
        <td style="padding: 12px;">{{ $product->product_category }}</td>
        <td style="padding: 12px;">
            <a href="{{ route('admin.updateproduct', $product->id) }}" style="color: green;" onclick="return confirm('Do you want to update this?')">Update</a>
            <form action="{{ route('admin.deleteproduct', $product->id) }}" method="POST" style="display:inline;">
                @csrf
                @method('DELETE')
                <button type="submit" style="background:none;border:none;padding:0;color:#0d6efd;" onclick="return confirm('Do you want to delete this?')">Delete</button>
            </form>
        </td>
    </tr>
    @endforeach
        <div class="d-flex justify-content-center mt-3">
            {{ $products->links() }}
        </div>
    </tbody>
</table>

@endsection
