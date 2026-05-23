@extends('maindesign')
<base href="/public">

@section('checkout')

<section class="checkout_section layout_padding">
  <div class="container">

    @if($cart->isEmpty())
    <div class="empty-cart text-center my-5">
        <i class="fa fa-shopping-cart fa-3x mb-3 text-muted"></i>
        <h4>Your cart is currently empty.</h4>
        <p class="text-secondary">
            You cannot proceed to checkout without items in your cart.<br>
            Please add products first.
        </p>
        <a href="{{ route('shop') }}" class="btn btn-cherry mt-3">Return to Shop</a>
    </div>
@else
  <div class="heading_container mb-4">
      <h2>Checkout</h2>
    </div>

    @if($errors->has('checkout'))
      <div class="alert alert-danger">{{ $errors->first('checkout') }}</div>
    @endif

    <form action="{{ route('checkout.placeOrder') }}" method="POST">
      @csrf

      <div class="row g-4">

        <div class="col-lg-7">
          <div class="checkout-card">
            <h5 class="mb-3">Billing Details</h5>

            <div class="row">
              <div class="col-md-6 mb-3">
                <label>First Name *</label>
                <input type="text" name="first_name" class="form-control" value="{{ old('first_name') }}" required>
              </div>

              <div class="col-md-6 mb-3">
                <label>Last Name *</label>
                <input type="text" name="last_name" class="form-control" value="{{ old('last_name') }}" required>
              </div>
            </div>

            <div class="mb-3">
              <label>Country *</label>
              <input type="text" class="form-control" value="Philippines" readonly>
            </div>

            <div class="mb-3">
              <label>Street Address *</label>
              <input type="text" name="address" class="form-control mb-2" value="{{ old('address') }}" placeholder="House number and street name" required>
              <input type="text" name="address2" class="form-control" value="{{ old('address2') }}" placeholder="Apartment, suite, unit, etc. (optional)">
            </div>

            <div class="mb-3">
              <label>City *</label>
              <select name="city" class="form-control" required>
                <option value="">-- Select City --</option>
                @foreach (['Caloocan', 'Las Pinas', 'Makati', 'Malabon', 'Mandaluyong', 'Manila', 'Marikina', 'Muntinlupa', 'Navotas', 'Paranaque', 'Pasay', 'Pasig', 'Quezon City', 'San Juan', 'Taguig', 'Valenzuela', 'Pateros'] as $city)
                  <option value="{{ $city }}" @selected(old('city') === $city)>{{ $city }}</option>
                @endforeach
              </select>
            </div>

            <div class="mb-3">
              <label>Postal *</label>
              <input type="text" name="postal" class="form-control mb-2" value="{{ old('postal') }}" placeholder="Postal Code" required>
            </div>

            <div class="mb-3">
              <label>Region *</label>
              <input type="text" class="form-control" value="Luzon" readonly>
            </div>

            <div class="mb-3">
              <label>Phone *</label>
              <input type="text" name="phone" class="form-control" value="{{ old('phone') }}" required maxlength="11" pattern="\d{11}" title="Phone number must be exactly 11 digits">
            </div>

            <div class="mb-3">
              <label>Email *</label>
              <input type="email" name="email" class="form-control" value="{{ old('email', Auth::user()->email ?? '') }}" required>
            </div>

            <div class="mb-3">
              <label>Order Notes (optional)</label>
              <textarea name="notes" class="form-control" rows="4">{{ old('notes') }}</textarea>
            </div>
          </div>
        </div>

        <div class="col-lg-5">
          <div class="checkout-card">
            <h5 class="mb-3">Your Order</h5>

            <table class="table">
              <thead>
                <tr>
                  <th>Product</th>
                  <th class="text-end">Subtotal</th>
                </tr>
              </thead>

              <tbody>
                @foreach($cart as $cartProduct)
                <tr>
                  <td>
                    {{ $cartProduct->product->product_title }} x {{ $cartProduct->quantity }}
                  </td>
                  <td class="text-end">
                    &#8369;{{ number_format($cartProduct->product->product_price * $cartProduct->quantity, 2) }}
                  </td>
                </tr>
                @endforeach
              </tbody>
            </table>

            <hr>

            <div class="d-flex justify-content-between mb-2">
              <span>Subtotal</span>
              <span>&#8369;{{ number_format($total, 2) }}</span>
            </div>

            <div class="d-flex justify-content-between fw-bold">
              <span>Total</span>
              <span style="color:#D2042D;">
                &#8369;{{ number_format($grandTotal, 2) }}
              </span>
            </div>

            <button type="submit" class="btn checkout-btn w-100 mt-3">
              PLACE ORDER
            </button>
          </div>
        </div>

      </div>
    </form>
  </div>
@endif
</section>

@endsection
