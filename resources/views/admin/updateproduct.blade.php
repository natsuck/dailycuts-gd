@extends('admin.maindesign')

<base href="/public">

@section('update_product')

@if(session('update_product_message')) 
    <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
        {{ session('update_product_message') }}
    </div>
@endif 

<div class="container-fluid">

    <form action="{{ route('admin.postupdateproduct', $product->id) }}" method="POST" enctype="multipart/form-data">
        @csrf

        <input type="text" name="product_title" value="{{ $product->product_title }}"> 
        <br><br>

        <textarea name="product_description" style="width: 300px; height: 200px;">{{ $product->product_description }}</textarea> 
        <br><br>

        <input type="number" name="product_quantity" value="{{ $product->product_quantity }}"> 
        <br><br>

        <input type="number" name="reorder_level" value="{{ old('reorder_level', $product->reorder_level) }}" min="0">
        <label>Reorder Level</label>
        <br><br>

        <input type="date" name="expiry_date" value="{{ old('expiry_date', optional($product->expiry_date)->format('Y-m-d')) }}">
        <label>Expiry Date</label>
        <br><br>

        <input type="number" name="product_price" value="{{ $product->product_price }}"> 
        <br><br>

        <img style="width: 100px;" src="{{ asset('products/'.$product->product_image) }}">
        <label>Old Image</label>

        <input type="file" name="product_image"> 
        <label>Add New Image</label> 
        <br><br>

        <select name="product_category">
            <option value="{{ $product->product_category }}">
                {{ $product->product_category }}
            </option>

            @foreach($categories as $category)
                <option value="{{ $category->category }}">
                    {{ $category->category }}
                </option> 
            @endforeach
        </select>

        <label>Select A Category</label> 
        <br><br>

        <input type="submit" name="submit" value="Update Product"> 
        <br><br>

    </form>

</div>

@endsection
