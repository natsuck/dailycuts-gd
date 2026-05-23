@extends('maindesign')

@section('checkout')

<section class="layout_padding text-center">
  <div class="container">

    <div class="success-card">

      <h2 class="mb-3">Order Placed Successfully!</h2>

      <p class="text-muted mb-4">
        Thank you for your order. We’ll prepare your fresh meat and deliver it soon.
      </p>

      <a href="{{ route('shop') }}" class="btn success-btn">
        Continue Shopping
      </a>

    </div>

  </div>
</section>

@endsection