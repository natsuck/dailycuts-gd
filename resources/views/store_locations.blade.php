@php
    $seo = [
        'title' => 'Find Our Stores | The Daily Cuts Meat Shops',
        'description' => 'Find a The Daily Cuts store near you. Visit our fresh meat shops for premium cuts, or order online for delivery.',
        'type' => 'website',
    ];
@endphp

@extends('maindesign')

@section('store_locations')

<section class="py-section-gap px-4 md:px-margin-desktop max-w-container-max mx-auto">
  <div class="mb-10 text-center">
    <h2 class="font-display-lg text-display-lg-mobile md:text-display-lg mb-2">Find Our Stores</h2>
    <p class="font-body-md text-on-surface-variant max-w-2xl mx-auto">
      Visit us at any of our locations. Tap a card to get directions on Google Maps.
    </p>
  </div>

  @if($locations->isEmpty())
    <p class="text-center font-body-md text-on-surface-variant py-10">
      Store locations coming soon.
    </p>
  @endif

  <div class="grid grid-cols-1 md:grid-cols-2 gap-gutter">
    @foreach($locations as $location)
      <div class="bg-surface-container-lowest border border-outline-variant rounded-lg overflow-hidden flex flex-col">
        @if($mapsKey !== '')
          <div class="h-56 w-full">
            <iframe
              title="Map of {{ $location->name }}"
              src="https://www.google.com/maps/embed/v1/place?key={{ $mapsKey }}&q={{ urlencode($location->fullAddress()) }}"
              width="100%" height="100%" frameborder="0" style="border:0" class="block" loading="lazy" allowfullscreen>
            </iframe>
          </div>
        @endif

        <div class="p-6 flex flex-col gap-3 flex-1">
          <div class="flex items-center justify-between gap-3">
            <h3 class="font-headline-sm text-headline-sm text-on-surface">{{ $location->name }}</h3>
            @if($location->is_pickup)
              <span class="inline-block px-2.5 py-1 rounded-full bg-primary/10 text-primary text-xs font-bold uppercase tracking-wider">Main Store</span>
            @endif
          </div>

          <p class="font-body-md text-on-surface-variant">{{ $location->fullAddress() }}</p>

          <a href="tel:{{ $location->phone }}" class="font-body-md text-primary hover:underline underline-offset-4 w-fit">
            {{ $location->phone }}
          </a>

          <a
            href="https://www.google.com/maps/dir/?api=1&destination={{ urlencode($location->fullAddress()) }}"
            target="_blank" rel="noopener noreferrer"
            class="mt-auto inline-flex items-center justify-center gap-2 bg-primary text-white font-bold px-5 py-3 rounded-lg hover:opacity-90 transition-all"
          >
            <span class="material-symbols-outlined text-[20px]">directions</span>
            Get Directions
          </a>
        </div>
      </div>
    @endforeach
  </div>
</section>

@endsection
