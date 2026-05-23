@extends('maindesign')
<base href=/public>
@section('product_details')

<section class="product_details layout_padding">
  <div class="container">
    <div class="row g-4">

      <!-- PRODUCT IMAGE -->
      <div class="col-md-6">
        <div class="product-details-img">
          <img src="{{ asset('products/'.$product->product_image) }}" class="img-fluid">
        </div>
      </div>

      <!-- PRODUCT INFO -->
      <div class="col-md-6">
        <div class="product-details-info">

          <h2 class="mb-3">{{ $product->product_title }}</h2>

          <h3 style="color:#D2042D;" class="mb-3">
            ₱{{ $product->product_price }}
          </h3>

          <p class="mb-4">
            {{ $product->product_description ?? 'Fresh and high-quality meat, delivered daily.' }}
          </p>

          <form action="{{ route('cart.add', $product->id) }}" method="POST">
            @csrf
            <div class="mb-3">
              <label>Quantity:</label>
              <input type="number" name="quantity" value="1" min="1" max="{{ $product->product_quantity }}" class="form-control w-25">
            </div>

            <div class="d-flex gap-3">
              <button type="submit" class="btn btn-dark w-100 add-cart-btn">
                Add to Cart
              </button>
            </div>
          </form>

          <!-- TRUST BADGES -->
          <div class="trust-badges mt-4 d-flex flex-column flex-md-row gap-3">
            <span>✔ 100% Fresh</span>
            <span>✔ Locally Sourced</span>
            <span>✔ Same-Day Delivery</span>
          </div>

        </div>
      </div>

    </div>
  </div>
</section>

<!-- ================= RELATED PRODUCTS ================= -->

<section class="shop_section layout_padding">
  <div class="container">
    <div class="heading_container heading_center">
      <h2>Related Products</h2>
    </div>

    <div class="row g-3">
      @foreach ($related_products as $product)
      <div class="col-6 col-sm-4 col-md-3">
        <div class="product-card h-100">
          <a href="{{ route('product_details',$product->id) }}">

            <div class="product-img">
              <img src="{{ asset('products/'.$product->product_image) }}">
            </div>

            <div class="product-info">
              <h6>{{ $product->product_title }}</h6>
              <h5 style="color:#D2042D;">₱{{ $product->product_price }}</h5>
            </div>

          </a>
        </div>
      </div>
      @endforeach
    </div>
  </div>
</section>

@endsection
