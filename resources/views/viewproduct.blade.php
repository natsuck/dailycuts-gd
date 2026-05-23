@extends('maindesign')

@section('viewproduct')

<div style="overflow-x: auto;">
<table style="width: 100%; border-collapse: collapse; font-family: Arial, sans-serif;">
    <thead>
    <tr style="background-color: #f2f2f2;">
        <th style="padding: 12px;">Product Name</th>
        <th style="padding: 12px;">Description</th>
        <th style="padding: 12px;">Qty</th>
        <th style="padding: 12px;">Price</th>
        <th style="padding: 12px;">Image</th>
        <th style="padding: 12px;">Category</th>
        <th style="padding: 12px;">Action</th>
    </tr>
    </thead>

    <tbody>
    @foreach($products as $product)
    <tr style="border-bottom: 1px solid #ddd;">
        <td style="padding: 12px; max-width:150px;">{{$product->product_title}}</td>

        <td style="padding: 12px; max-width:150px; word-wrap:break-word;">
            {{Str::limit($product->product_description, 50)}}
        </td>

        <td style="padding: 12px;">{{$product->product_quantity}}</td>
        <td style="padding: 12px;">{{$product->product_price}}</td>

        <td style="padding: 12px;">
            <img style="width:80px;" src="{{asset('products/'.$product->product_image)}}">
        </td>

        <td style="padding: 12px;">{{$product->product_category}}</td>

        <td style="padding: 12px;">
            <a href="{{route('admin.updateproduct',$product->id)}}" style="color: green;">
                Update
            </a>
            |
            <form action="{{ route('admin.deleteproduct', $product->id) }}" method="POST" style="display:inline;">
                @csrf
                @method('DELETE')
                <button type="submit" style="background:none;border:none;padding:0;color:red;">
                    Delete
                </button>
            </form>
        </td>
    </tr>
    @endforeach
    </tbody>
</table>
</div>

<div style="margin-top: 20px;">
    {{$products->links()}}
</div>

@endsection
