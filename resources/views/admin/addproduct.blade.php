@extends('admin.maindesign')

@section('add_product')

    @if(session('product_message')) 
        <div class="alert alert-strong-green mb-4">
            {{ session('product_message') }}
        </div>
    @endif

    <div class="container-fluid">

        <form action="{{route('admin.postaddproduct')}}" method="POST" enctype="multipart/form-data">
            @csrf

            <input type="text" name="product_title" placeholder="Enter product title.">
            <br><br>

            <textarea name="product_description" placeholder="Product Description!..."></textarea>
            <br><br>

            <input type="number" name="product_quantity" placeholder="Enter product quantity.">
            <br><br>

            <input type="number" name="reorder_level" placeholder="Enter reorder level." value="{{ old('reorder_level', 10) }}" min="0">
            <br><br>

            <input type="date" name="expiry_date" value="{{ old('expiry_date') }}">
            <br><br>

            <input type="number" name="product_price" placeholder="Enter product price.">
            <br><br>
            <input type="file" name="product_image">
            <br><br>
            <select name="product_category">
                @foreach($categories as $category)
                    <option value="{{$category->category}}">
                        {{$category->category}}
                    </option> 
                @endforeach
            </select>
            <br><br>

            <input type="submit" name="submit" value="Add Product">
        </form>

    </div>

@endsection
