@extends('maindesign')

@section('login')
<div class="min-h-[60vh] flex items-center justify-center px-4 md:px-margin-desktop py-12 md:py-16">
  <div class="w-full max-w-md bg-surface-container-lowest border border-outline-variant rounded-lg p-6 md:p-8 shadow-sm">
    <h2 class="font-headline-md text-headline-md text-center mb-8">Login</h2>

    @if ($errors->any())
      <div class="mb-6 bg-error-container/20 border border-error rounded-lg px-4 py-3">
        <ul class="list-disc pl-4 font-body-md text-error space-y-1">
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <form method="POST" action="{{ route('login') }}">
      @csrf

      <div class="mb-5">
        <label class="font-label-caps text-label-caps text-on-surface-variant mb-1 block" for="email">Email</label>
        <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus autocomplete="username" class="w-full bg-surface border border-outline-variant rounded-lg px-4 py-3 text-body-md focus:ring-primary focus:border-primary outline-none @error('email') border-error @enderror">
        @error('email')
          <p class="mt-1 font-body-md text-error">{{ $message }}</p>
        @enderror
      </div>

      <div class="mb-6">
        <label class="font-label-caps text-label-caps text-on-surface-variant mb-1 block" for="password">Password</label>
        <input type="password" name="password" id="password" required autocomplete="current-password" class="w-full bg-surface border border-outline-variant rounded-lg px-4 py-3 text-body-md focus:ring-primary focus:border-primary outline-none @error('password') border-error @enderror">
        @error('password')
          <p class="mt-1 font-body-md text-error">{{ $message }}</p>
        @enderror
      </div>

      <button type="submit" class="w-full bg-primary text-white font-bold py-3 rounded-lg uppercase tracking-widest text-label-caps hover:opacity-90 transition-all active:scale-95">Login</button>
    </form>

    <div class="relative flex items-center justify-center my-6">
      <div class="border-t border-outline-variant flex-1"></div>
      <span class="px-3 font-body-md text-on-surface-variant">or</span>
      <div class="border-t border-outline-variant flex-1"></div>
    </div>

    <div class="space-y-3">
      <a href="{{ route('auth.social.redirect', ['provider' => 'google']) }}"
        class="w-full flex items-center justify-center gap-3 bg-surface border border-outline-variant rounded-lg py-3 font-label-caps text-on-surface hover:bg-surface-container-high transition-all active:scale-95">
        <svg class="w-5 h-5" viewBox="0 0 24 24" aria-hidden="true">
          <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
          <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
          <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
          <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
        </svg>
        Continue with Google
      </a>
    </div>

    <p class="text-center mt-6 font-body-md text-on-surface-variant">
      Don't have an account?
      <a href="{{ route('register') }}" class="text-primary font-bold hover:underline">Sign up</a>
    </p>
  </div>
</div>
@endsection
