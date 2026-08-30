@php
    $seo = [
        'title' => 'Frequently Asked Questions | The Daily Cuts',
        'description' => 'Answers to common questions about The Daily Cuts — ordering, delivery, storage, and how our fresh meat delivery works.',
        'type' => 'website',
    ];
@endphp

@extends('maindesign')

@section('content')

<main class="max-w-container-max mx-auto px-4 md:px-margin-desktop py-8 md:py-12">
  <div class="max-w-3xl mx-auto">

    <div class="mb-10 md:mb-12 text-center">
      <h1 class="font-display-lg text-display-lg-mobile md:text-display-lg text-on-surface mb-2">The Daily Cuts</h1>
      <p class="font-label-caps text-label-caps text-on-surface-variant tracking-widest">Frequently Asked Questions</p>
    </div>

    <div class="space-y-4">
      @php
        $storeLocation = \App\Models\StoreLocation::activePickup();
        $deliveryWindow = app(\App\Services\DeliveryWindow::class);
        $faqs = [
          'How can I order?' => 'You can place your order directly through this official account. Just follow the usual steps to check out.',
          'Can you do same-day delivery?' => 'Yes. Same-day delivery is available from '.$deliveryWindow->label().'. Orders placed after '.$deliveryWindow->endLabel().' will be delivered the next day.',
          'How will you deliver my goods?' => 'For your convenience, we will book the delivery for you via Lalamove.',
          'What days are you open?' => 'We are open from Mondays-Sundays, 9am-6pm.',
          'Where are you located?' => $storeLocation
            ? ($storeLocation->fullAddress().'. You can reach us at '.$storeLocation->phone.'.')
            : 'Visit our <a href="'.route('store.locations').'" class="text-primary font-bold hover:underline">Find Our Stores</a> page for our locations.',
          'Can you ship nationwide?' => 'As of the moment, we can only serve Metro Manila and some parts of Luzon (Laguna, Cavite, Bulacan, and Rizal).',
        ];
      @endphp

      @foreach($faqs as $question => $answer)
        <div x-data="{ open: false }" class="border border-outline-variant rounded-lg overflow-hidden bg-surface-container-lowest">
          <button
            type="button"
            class="w-full flex items-center justify-between gap-4 text-left p-5 md:p-6 hover:bg-surface-container-low transition-colors"
            @click="open = !open"
            :aria-expanded="open.toString()"
          >
            <h2 class="font-headline-sm text-headline-sm text-on-surface flex items-center gap-4">
              <span class="font-price-display text-price-display text-primary">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
              <span>{{ $question }}</span>
            </h2>
            <span class="material-symbols-outlined text-on-surface-variant transition-transform duration-300" :class="open ? 'rotate-180' : ''">expand_more</span>
          </button>
          <div x-show="open" x-transition:enter="transition-all duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition-all duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" x-cloak>
            <p class="px-5 md:px-6 pb-5 md:pb-6 pt-0 text-on-surface-variant font-body-md border-t border-outline-variant/50">{{ $answer }}</p>
          </div>
        </div>
      @endforeach
    </div>

    <p class="text-center font-body-md text-on-surface-variant mt-10 md:mt-12">
      Still have questions? <a href="{{ route('contact_us') }}" class="text-primary font-bold hover:underline">Contact us</a>.
    </p>
  </div>
</main>

@endsection
