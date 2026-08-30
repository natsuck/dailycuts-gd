@php
    $seo = ['robots' => 'noindex, nofollow'];
@endphp

@extends('maindesign')
@section('dashboard')

<main class="max-w-container-max mx-auto px-margin-desktop py-12 flex flex-col md:flex-row gap-12">
  <aside class="w-full md:w-64 flex-shrink-0">
    <div class="sticky top-32 space-y-8">
      <div>
        <h2 class="font-label-caps text-label-caps text-on-surface-variant mb-6 uppercase tracking-widest">Account</h2>
        <nav class="flex flex-col gap-1">
          <a class="active-nav-indicator flex items-center gap-3 px-4 py-3 bg-surface-container-high text-primary font-bold transition-all" href="{{ route('dashboard') }}">
            <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">person</span>
            <span class="font-headline-sm text-body-md">Dashboard</span>
          </a>
          <a class="flex items-center gap-3 px-4 py-3 text-on-surface-variant hover:bg-surface-container transition-all group" href="{{ route('shop') }}">
            <span class="material-symbols-outlined group-hover:text-primary">storefront</span>
            <span class="font-body-md">Browse Shop</span>
          </a>
          <a class="flex items-center gap-3 px-4 py-3 text-on-surface-variant hover:bg-surface-container transition-all group" href="{{ route('viewcart') }}">
            <span class="material-symbols-outlined group-hover:text-primary">shopping_cart</span>
            <span class="font-body-md">My Cart</span>
          </a>
          <a class="flex items-center gap-3 px-4 py-3 text-on-surface-variant hover:bg-surface-container transition-all group" href="{{ route('profile.edit') }}">
            <span class="material-symbols-outlined group-hover:text-primary">settings</span>
            <span class="font-body-md">Settings</span>
          </a>
        </nav>
      </div>
      <div class="pt-8 border-t border-outline-variant">
        <form method="POST" action="{{ route('logout') }}">
          @csrf
          <button type="submit" class="flex items-center gap-3 px-4 py-3 text-error hover:bg-error-container/20 w-full transition-all">
            <span class="material-symbols-outlined">logout</span>
            <span class="font-body-md font-bold">Sign Out</span>
          </button>
        </form>
      </div>
    </div>
  </aside>

  <section class="flex-grow space-y-12">
    <header class="flex flex-col md:flex-row md:items-end justify-between gap-6">
      <div>
        <p class="font-label-caps text-label-caps text-primary uppercase mb-2">Welcome back</p>
        <h2 class="font-display-lg text-display-lg text-on-surface">{{ Auth::user()->name }}</h2>
      </div>
    </header>

    <div class="bento-grid">
      <div class="col-span-12 lg:col-span-8 bg-surface-container-lowest border border-outline-variant p-8">
        <div class="flex items-center justify-between mb-8">
          <h3 class="font-headline-md text-headline-md">Your Products</h3>
          <a class="text-primary font-bold text-label-caps hover:underline underline-offset-4" href="{{ route('shop') }}">VIEW SHOP</a>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          @foreach ($products->take(4) as $product)
            <a href="{{ route('product_details', $product->id) }}" class="flex gap-4 group hover:bg-surface-container-low p-4 transition-colors">
              <div class="w-20 h-20 bg-surface-container-high overflow-hidden flex-shrink-0">
                <img alt="{{ $product->product_title }}" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500" src="{{ asset('products/'.$product->product_image) }}">
              </div>
              <div class="flex-grow">
                <p class="font-bold text-sm leading-tight text-on-surface">{{ $product->product_title }}</p>
                <p class="text-xs text-on-surface-variant">{{ $product->product_description ?? 'Fresh daily cut' }}</p>
                <p class="text-primary font-bold mt-1 text-sm">&#8369;{{ number_format($product->product_price, 2) }}</p>
              </div>
            </a>
          @endforeach
        </div>
      </div>

      <div class="col-span-12 lg:col-span-4 flex flex-col gap-6">
        <div class="bg-surface-container-lowest border border-outline-variant p-6 flex-grow">
          <div class="flex items-center justify-between mb-6">
            <h3 class="font-headline-sm text-headline-sm">Quick Actions</h3>
          </div>
          <div class="space-y-4">
            <a href="{{ route('shop') }}" class="flex gap-4 group cursor-pointer hover:bg-surface-container-low p-2 transition-colors">
              <div class="w-16 h-16 bg-surface-container-high overflow-hidden flex items-center justify-center">
                <span class="material-symbols-outlined text-primary text-3xl">storefront</span>
              </div>
              <div class="flex-grow flex flex-col justify-center">
                <p class="font-bold text-sm leading-tight text-on-surface">Browse Shop</p>
                <p class="text-xs text-on-surface-variant">Explore fresh cuts</p>
              </div>
            </a>
            <a href="{{ route('viewcart') }}" class="flex gap-4 group cursor-pointer hover:bg-surface-container-low p-2 transition-colors">
              <div class="w-16 h-16 bg-surface-container-high overflow-hidden flex items-center justify-center">
                <span class="material-symbols-outlined text-primary text-3xl">shopping_cart</span>
              </div>
              <div class="flex-grow flex flex-col justify-center">
                <p class="font-bold text-sm leading-tight text-on-surface">View Cart</p>
                <p class="text-xs text-on-surface-variant">Check your selections</p>
              </div>
            </a>
          </div>
        </div>

        <div class="bg-primary-container p-6 flex flex-col justify-between relative overflow-hidden h-40 rounded-lg">
          <div class="relative z-10">
            <p class="text-on-primary-container font-label-caps text-label-caps uppercase opacity-80 mb-1">Need help?</p>
            <h4 class="text-white font-headline-sm text-headline-sm">Personal Butcher Service</h4>
          </div>
          <a href="{{ route('contact_us') }}" class="relative z-10 self-start text-white border-b-2 border-white font-bold text-sm pb-1 hover:opacity-80 transition-all">Contact Us</a>
          <span class="material-symbols-outlined absolute -right-4 -bottom-4 text-9xl text-on-primary-container/20 rotate-12">restaurant_menu</span>
        </div>
      </div>
    </div>
  </section>
</main>

@endsection
