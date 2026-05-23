@extends('maindesign')
@section('index')

    @if(session('cartMessage'))
    <div id="cartSnackbar" class="snackbar">
      <i class="fa fa-check-circle me-2"></i> {{ session('cartMessage') }}
    </div>
    @php session()->forget('cartMessage'); @endphp
    @endif

    <div class="container">
      <div class="heading_container heading_center">
        <h2>
          Latest Products
        </h2>
      </div>
      <div class="row g-3">
        @foreach ($products as $product)
            <div class="col-6 col-sm-4 col-md-3">
              <div class="product-card h-100">
                <a href="{{ route('product_details',$product->id)}}" class="text-decoration-none">
                  
                  <div class="product-img">
                    <img src="{{ asset('products/'.$product->product_image) }}" alt="{{ $product->product_title }}">
                    <span class="badge-new">New</span>
                  </div>

                  <div class="product-info">
                    <h6 class="product-title" title="{{ $product->product_title }}">
                      {{$product->product_title}}
                    </h6>

                    <h5 class="product-price">
                      ₱{{ $product->product_price }}
                    </h5>
                  </div>

                  <div class="product-action text-center">
                    <form action="{{ route('cart.add', $product->id) }}" method="POST">
                      @csrf
                      <button type="submit" class="btn btn-dark w-100 add-cart-btn">
                        Add to Cart
                      </button>
                    </form>
                  </div>

                </a>
              </div>
            </div>
        @endforeach
      </div>
      <div class="btn-box">
        <a href="{{ route('shop') }}">
          View All Products
        </a>
      </div>
    </div>
@endsection
