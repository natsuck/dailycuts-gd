@extends('maindesign')

@section('verify')
<div class="verify-wrapper d-flex justify-content-center align-items-center">

  <div class="verify-card text-center">

    <!-- Icon -->
    <div class="verify-icon">
      <i class="fa fa-envelope-open"></i>
    </div>

    <!-- Title -->
    <h3 class="verify-title">Verify Your Email</h3>

    <!-- Text -->
    <p class="verify-text">
      We’ve sent a verification link to your email.  
      Please confirm your account before continuing.
    </p>

    <!-- Success -->
    @if (session('status') == 'verification-link-sent')
      <div class="verify-success">
        ✔ Verification link sent again
      </div>
    @endif

    <!-- Actions -->
    <form method="POST" action="{{ route('verification.send') }}">
      @csrf
      <button type="submit" class="btn verify-btn-main">
        Resend Email
      </button>
    </form>

    <a href="{{ route('shop') }}" class="verify-link">Back to Shop</a>

    <form method="POST" action="{{ route('logout') }}">
      @csrf
      <button class="verify-logout">Log out</button>
    </form>

  </div>
</div>
@endsection