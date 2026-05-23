@extends('maindesign')
@section('shop')

<section class="shop_section">

    
    @if(session('cartMessage'))
    <div id="cartSnackbar" class="snackbar">
      <i class="fa fa-check-circle me-2"></i> 
      {{ session('cartMessage') }}
    </div>
    @endif

    @if(session('orderMessage'))
      <div id="cartSnackbar" class="snackbar">
        <i class="fa fa-check-circle me-2"></i>
        {{ session('orderMessage') }}
    </div>
    @endif

  

  <div class="container">

    <div class="heading_container heading_center mb-4">
      <h2 class="fw-bold">Our Shop</h2>
      <p class="text-muted">Fresh. Premium. Delivered to your door.</p>
    </div>

    <div class="row g-4">
      @foreach ($products as $product)
        <div class="col-6 col-sm-4 col-md-3">
          
          <div class="product-card h-100">

            <a href="{{ route('product_details',$product->id)}}" class="text-decoration-none text-dark">

              <!-- Image -->
              <div class="product-img">
                
                <img src="{{ asset('products/'.$product->product_image) }}" alt="{{ $product->product_title }}">
                <span class="badge-new">Fresh</span>
              </div>

              <!-- Info -->
              <div class="product-info text-center">
                <h6 class="product-title">
                  {{$product->product_title}}
                </h6>

                <h5 class="product-price">
                  ₱{{ number_format($product->product_price, 2) }}
                </h5>
              </div>

            </a>

            <!-- CTA -->
            <div class="product-action text-center">
              <form action="{{ route('cart.add', $product->id) }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-dark w-100 add-cart-btn">
                  Add to Cart
                </button>
              </form>
            </div>

          </div>

        </div>
      @endforeach
    </div>

  </div>
</section>

@endsection
