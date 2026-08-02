@extends('maindesign')

@section('register')
<div class="min-h-[60vh] flex items-center justify-center px-4 md:px-margin-desktop py-12 md:py-16">
  <div class="w-full max-w-md bg-surface-container-lowest border border-outline-variant rounded-lg p-6 md:p-8 shadow-sm">
    <h2 class="font-headline-md text-headline-md text-center mb-8">Register</h2>

    <form method="POST" action="{{ route('register') }}">
      @csrf

      <div class="mb-4">
        <label class="font-label-caps text-label-caps text-on-surface-variant mb-1 block">Name</label>
        <input type="text" name="name" required class="w-full bg-surface border border-outline-variant rounded-lg px-4 py-3 text-body-md focus:ring-primary focus:border-primary outline-none">
      </div>

      <div class="mb-4">
        <label class="font-label-caps text-label-caps text-on-surface-variant mb-1 block">Email</label>
        <input type="email" name="email" required class="w-full bg-surface border border-outline-variant rounded-lg px-4 py-3 text-body-md focus:ring-primary focus:border-primary outline-none">
      </div>

      <div class="mb-4">
        <label class="font-label-caps text-label-caps text-on-surface-variant mb-1 block">Password</label>
        <input type="password" name="password" required class="w-full bg-surface border border-outline-variant rounded-lg px-4 py-3 text-body-md focus:ring-primary focus:border-primary outline-none">
      </div>

      <div class="mb-6">
        <label class="font-label-caps text-label-caps text-on-surface-variant mb-1 block">Confirm Password</label>
        <input type="password" name="password_confirmation" required class="w-full bg-surface border border-outline-variant rounded-lg px-4 py-3 text-body-md focus:ring-primary focus:border-primary outline-none">
      </div>

      <button type="submit" class="w-full bg-primary text-white font-bold py-3 rounded-lg uppercase tracking-widest text-label-caps hover:opacity-90 transition-all active:scale-95">Sign Up</button>
    </form>

    <p class="text-center mt-6 font-body-md text-on-surface-variant">
      Already have an account?
      <a href="{{ route('login') }}" class="text-primary font-bold hover:underline">Login</a>
    </p>
  </div>
</div>
@endsection
