@extends('maindesign')

@section('verify')
<div class="min-h-[70vh] flex justify-center items-center px-4 py-12">
  <div class="w-full max-w-md">

    <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl p-8 text-center shadow-lg">

      <div class="w-16 h-16 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-6">
        <span class="material-symbols-outlined text-primary text-3xl">mail</span>
      </div>

      <h2 class="font-headline-sm text-headline-sm text-on-surface mb-2">Verify Your Email</h2>

      <p class="text-on-surface-variant font-body-md mb-6">
        We've sent a 6-digit verification code to your email.<br>
        Please enter it below to confirm your account.
      </p>

      @if (session('status') == 'verification-code-sent')
        <div class="bg-green-50 text-green-700 border border-green-200 rounded-lg px-4 py-3 mb-6 font-body-md">
          A new verification code has been sent to your email.
        </div>
      @endif

      @if ($errors->has('code'))
        <div class="bg-red-50 text-red-700 border border-red-200 rounded-lg px-4 py-3 mb-6 font-body-md">
          {{ $errors->first('code') }}
        </div>
      @endif

      <form method="POST" action="{{ route('verification.verify') }}" id="otp-form">
        @csrf
        <div class="flex justify-center gap-3 mb-6">
          <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]"
            class="otp-input w-12 h-14 text-center text-xl font-bold bg-surface border-2 border-outline-variant rounded-lg focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition-all text-on-surface"
            autocomplete="off" autofocus>
          <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]"
            class="otp-input w-12 h-14 text-center text-xl font-bold bg-surface border-2 border-outline-variant rounded-lg focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition-all text-on-surface"
            autocomplete="off">
          <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]"
            class="otp-input w-12 h-14 text-center text-xl font-bold bg-surface border-2 border-outline-variant rounded-lg focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition-all text-on-surface"
            autocomplete="off">
          <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]"
            class="otp-input w-12 h-14 text-center text-xl font-bold bg-surface border-2 border-outline-variant rounded-lg focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition-all text-on-surface"
            autocomplete="off">
          <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]"
            class="otp-input w-12 h-14 text-center text-xl font-bold bg-surface border-2 border-outline-variant rounded-lg focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition-all text-on-surface"
            autocomplete="off">
          <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]"
            class="otp-input w-12 h-14 text-center text-xl font-bold bg-surface border-2 border-outline-variant rounded-lg focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition-all text-on-surface"
            autocomplete="off">
        </div>
        <input type="hidden" name="code" id="otp-hidden">

        <button type="submit"
          class="w-full bg-primary text-white font-bold py-3 rounded-lg uppercase tracking-widest text-label-caps hover:opacity-90 active:scale-[0.98] transition-all">
          Verify Email
        </button>
      </form>

      <div class="mt-6">
        <p class="text-on-surface-variant font-body-md text-sm">
          Didn't receive the code?
        </p>
        <form method="POST" action="{{ route('verification.send') }}" class="mt-2">
          @csrf
          <button type="submit" class="text-primary font-bold text-sm hover:underline transition-all">
            Resend Code
          </button>
        </form>
      </div>

      <div class="mt-6 pt-4 border-t border-outline-variant">
        <a href="{{ route('shop') }}" class="text-on-surface-variant hover:text-primary font-body-md text-sm transition-colors">
          &larr; Back to Shop
        </a>
      </div>

      <form method="POST" action="{{ route('logout') }}" class="mt-4">
        @csrf
        <button type="submit" class="text-on-surface-variant hover:text-error font-body-md text-sm transition-colors">
          Log out
        </button>
      </form>

    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const inputs = document.querySelectorAll('.otp-input');
  const hidden = document.getElementById('otp-hidden');
  const form = document.getElementById('otp-form');

  inputs.forEach(function (input, index) {
    input.addEventListener('input', function (e) {
      const val = e.target.value.replace(/[^0-9]/g, '');
      e.target.value = val;

      if (val && index < inputs.length - 1) {
        inputs[index + 1].focus();
      }

      updateHidden();
    });

    input.addEventListener('keydown', function (e) {
      if (e.key === 'Backspace' && !e.target.value && index > 0) {
        inputs[index - 1].focus();
        inputs[index - 1].value = '';
      }
    });

    input.addEventListener('paste', function (e) {
      e.preventDefault();
      const paste = (e.clipboardData || window.clipboardData).getData('text').replace(/[^0-9]/g, '').slice(0, 6);
      paste.split('').forEach(function (char, i) {
        if (inputs[i]) inputs[i].value = char;
      });
      if (paste.length > 0) {
        inputs[Math.min(paste.length, inputs.length) - 1].focus();
      }
      updateHidden();
    });
  });

  function updateHidden() {
    hidden.value = Array.from(inputs).map(function (i) { return i.value; }).join('');
  }

  form.addEventListener('submit', function (e) {
    updateHidden();
    if (hidden.value.length !== 6) {
      e.preventDefault();
    }
  });
});
</script>
@endsection
