@extends('maindesign')

@section('login')
<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
      <div class="bg-white p-4 rounded shadow">
        <h2 class="text-center mb-4 fw-bold">Login</h2>

        <form method="POST" action="{{ route('login') }}">
          @csrf

          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" required class="form-control">
          </div>

          <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" name="password" required class="form-control">
          </div>

          <button class="btn btn-danger w-100">Login</button>
        </form>

        <p class="text-center mt-3">
          Don't have an account?
          <a href="{{ route('register') }}" class="text-danger fw-bold">Sign up</a>
        </p>
      </div>
    </div>
  </div>
</div>
@endsection
