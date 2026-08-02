@extends('maindesign')

@section('contact_us')

<section class="py-section-gap px-4 md:px-margin-desktop max-w-container-max mx-auto">
  <div class="mb-10">
    <h2 class="font-headline-md text-headline-md mb-2">Become A Reseller</h2>
    <p class="font-body-md text-on-surface-variant">Partner with us and bring premium cuts to your customers.</p>
  </div>

  @if(session('resellerMessage'))
    <div class="bg-green-50 text-green-800 border border-green-200 px-6 py-4 rounded-lg mb-8 font-body-md">
      {{ session('resellerMessage') }}
    </div>
  @endif

  <div class="grid grid-cols-1 lg:grid-cols-12 gap-gutter">
    <div class="lg:col-span-7 rounded-lg overflow-hidden border border-outline-variant" style="min-height: 250px;">
      <iframe
        src="https://www.google.com/maps/embed/v1/place?key=AIzaSyA0s1a7phLN0iaD6-UE7m4qP-z21pH0eSc&q=PHASE+10+Wellington+Place"
        width="100%" height="100%" frameborder="0" style="border:0; min-height: 250px;" class="md:min-h-[400px]" allowfullscreen>
      </iframe>
    </div>
    <div class="lg:col-span-5">
      <form action="{{ route('contact_us.submit') }}" method="POST" class="bg-surface-container-lowest border border-outline-variant rounded-lg p-6 md:p-8">
        @csrf
        <div class="mb-4">
          <input type="text" name="name" class="w-full bg-surface border border-outline-variant rounded-lg px-4 py-3 text-body-md focus:ring-primary focus:border-primary outline-none" placeholder="Name" value="{{ old('name') }}" required />
        </div>
        <div class="mb-4">
          <input type="email" name="email" class="w-full bg-surface border border-outline-variant rounded-lg px-4 py-3 text-body-md focus:ring-primary focus:border-primary outline-none" placeholder="Email" value="{{ old('email') }}" required />
        </div>
        <div class="mb-4">
          <input type="tel" name="phone" class="w-full bg-surface border border-outline-variant rounded-lg px-4 py-3 text-body-md focus:ring-primary focus:border-primary outline-none" placeholder="Phone" value="{{ old('phone') }}" />
        </div>
        <div class="mb-6">
          <textarea name="message" class="w-full bg-surface border border-outline-variant rounded-lg px-4 py-3 text-body-md focus:ring-primary focus:border-primary outline-none resize-none" rows="4" style="min-height: 120px;" placeholder="Message" required>{{ old('message') }}</textarea>
        </div>
        <button type="submit" class="w-full bg-primary text-white font-bold py-3 rounded-lg uppercase tracking-widest text-label-caps hover:opacity-90 transition-all active:scale-95">Send</button>
      </form>
    </div>
  </div>
</section>

@endsection
