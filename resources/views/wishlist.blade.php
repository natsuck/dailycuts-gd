@php
    $seo = ['robots' => 'noindex, nofollow'];
@endphp

@extends('maindesign')

@section('content')
<main class="max-w-container-max mx-auto px-4 md:px-margin-desktop py-8 md:py-12">
  <header class="mb-12">
    <h2 class="font-display-lg text-display-lg-mobile md:text-display-lg text-on-surface mb-2">My Wishlist</h2>
    <p class="font-body-lg text-body-lg text-secondary">Your saved cuts, ready when you are.</p>
  </header>

  @forelse($wishlist as $item)
    <article class="bg-surface-container-lowest border border-[#EEEEEE] flex flex-col md:flex-row p-6 gap-6 group hover:shadow-[0px_4px_20px_rgba(0,0,0,0.05)] transition-shadow mb-4">
      <a href="{{ route('product_details', $item->product->id) }}" class="w-full md:w-40 flex-shrink-0 relative overflow-hidden">
        <img alt="{{ $item->product->product_title }}" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105" src="{{ asset('products/'.$item->product->product_image) }}">
      </a>
      <div class="flex-grow flex flex-col justify-between">
        <div class="flex justify-between items-start">
          <div>
            <a href="{{ route('product_details', $item->product->id) }}" class="font-headline-md text-headline-md text-on-surface hover:text-primary transition-colors">{{ $item->product->product_title }}</a>
            <p class="font-body-md text-body-md text-secondary mt-1">{{ $item->product->product_category }}</p>
            <p class="font-price-display text-price-display text-primary mt-2">&#8369;{{ number_format($item->product->product_price, 2) }}</p>
          </div>
          <form action="{{ route('wishlist.destroy', $item->id) }}" method="POST">
            @csrf
            @method('DELETE')
            <button class="material-symbols-outlined text-on-surface-variant hover:text-error transition-colors" aria-label="Remove from wishlist">close</button>
          </form>
        </div>
        <div class="flex justify-end mt-4">
          <a href="{{ route('product_details', $item->product->id) }}" class="bg-primary text-white font-bold py-3 px-6 active:opacity-80 transition-all uppercase tracking-widest text-label-caps text-center">
            View Product
          </a>
        </div>
      </div>
    </article>
  @empty
    <div class="flex flex-col items-center justify-center py-20 text-center">
      <span class="material-symbols-outlined text-6xl text-on-surface-variant mb-6">favorite</span>
      <h1 class="font-display-lg text-display-lg-mobile text-on-surface mb-4">Your wishlist is empty.</h1>
      <p class="font-body-lg text-body-lg text-on-surface-variant mb-8">Save cuts you love so you never lose track of them.</p>
      <a href="{{ route('shop') }}" class="bg-primary text-white font-bold py-4 px-8 rounded-lg hover:opacity-90 transition-all active:scale-95">Return To Shop</a>
    </div>
  @endforelse
</main>
@endsection
