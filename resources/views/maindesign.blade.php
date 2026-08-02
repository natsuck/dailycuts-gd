<!DOCTYPE html>
<html lang="en" class="light">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>The Daily Cuts by GD</title>
  <link rel="shortcut icon" href="{{ asset('frontend/images/img3.jpg') }}">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Montserrat:wght@600;700&family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">

  @vite(['resources/css/app.css', 'resources/js/app.js'])

  <style>
    input::-webkit-outer-spin-button,
    input::-webkit-inner-spin-button {
      -webkit-appearance: none;
      margin: 0;
    }
  </style>
</head>

@php
  $cartTotalCount = $cartCount ?? $count ?? 0;
@endphp

<body class="bg-background text-on-surface font-body-md pb-[calc(4rem+env(safe-area-inset-bottom))] md:pb-0">
  @auth
    @if(!Auth::user()->hasVerifiedEmail())
      <div class="bg-tertiary-container text-on-tertiary-container px-4 md:px-margin-desktop py-3 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
        <span class="font-label-caps text-label-caps">Please verify your email to continue shopping.</span>
        <div class="flex items-center gap-4 shrink-0">
          <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="bg-primary text-white px-4 py-2 font-label-caps text-label-caps hover:opacity-90 transition-all">Resend Verification Email</button>
          </form>
          <button type="button" onclick="this.parentElement.parentElement.remove()" class="text-on-primary-fixed hover:opacity-70">
            <span class="material-symbols-outlined">close</span>
          </button>
        </div>
      </div>
    @endif
  @endauth

  <div x-data="{ mobileMenu: false }">
    <header class="sticky top-0 w-full z-50 bg-surface px-4 md:px-margin-desktop max-w-container-max mx-auto flex items-center justify-between h-16 md:h-20 border-b border-outline-variant">
      <div class="flex items-center gap-2 md:gap-10 min-w-0">
        <button @click="mobileMenu = !mobileMenu" class="md:hidden active:opacity-80 transition-all text-on-surface-variant hover:text-primary flex-shrink-0" aria-label="Open menu">
          <span class="material-symbols-outlined" x-text="mobileMenu ? 'close' : 'menu'">menu</span>
        </button>
        <a href="{{ route('index') }}" class="font-display-lg text-[clamp(18px,12vw-19px,32px)] md:text-display-lg text-on-surface tracking-tight whitespace-nowrap min-w-0 truncate">The Daily Cuts</a>
        <nav class="hidden md:flex gap-6 items-center">
          <a href="{{ route('index') }}" class="{{ request()->routeIs('index') ? 'text-primary font-bold border-b-2 border-primary pb-1' : 'text-on-surface-variant hover:text-primary' }} transition-colors duration-200 font-headline-sm text-headline-sm">Home</a>
          <a href="{{ route('shop') }}" class="{{ request()->routeIs('shop') || request()->routeIs('product_details') ? 'text-primary font-bold border-b-2 border-primary pb-1' : 'text-on-surface-variant hover:text-primary' }} transition-colors duration-200 font-headline-sm text-headline-sm">Shop</a>
          <a href="{{ route('contact_us') }}" class="{{ request()->routeIs('contact_us') ? 'text-primary font-bold border-b-2 border-primary pb-1' : 'text-on-surface-variant hover:text-primary' }} transition-colors duration-200 font-headline-sm text-headline-sm">Become A Reseller</a>
        </nav>
      </div>
      <div class="flex items-center gap-2 md:gap-6 flex-shrink-0">
        <a href="{{ route('shop') }}" class="active:opacity-80 active:scale-95 transition-all text-on-surface-variant hover:text-primary" aria-label="Search products">
          <span class="material-symbols-outlined">search</span>
        </a>
        @auth
          <a href="{{ route('wishlist') }}" class="active:opacity-80 active:scale-95 transition-all text-on-surface-variant hover:text-primary" aria-label="Wishlist">
            <span class="material-symbols-outlined">favorite</span>
          </a>
        @endauth
        <a href="{{ route('viewcart') }}" class="relative active:opacity-80 active:scale-95 transition-all text-on-surface-variant hover:text-primary" aria-label="View cart">
          <span class="material-symbols-outlined">shopping_cart</span>
          @if($cartTotalCount > 0)
            <span class="absolute -top-2 -right-2 bg-primary text-white text-[10px] w-4 h-4 rounded-full flex items-center justify-center font-bold">{{ $cartTotalCount }}</span>
          @endif
        </a>

        @auth
          <div class="relative group hidden md:block">
            <button class="active:opacity-80 active:scale-95 transition-all text-on-surface-variant hover:text-primary flex items-center gap-1">
              <span class="material-symbols-outlined">person</span>
              <span class="material-symbols-outlined text-[12px]">expand_more</span>
            </button>
            <div class="absolute right-0 top-full mt-2 w-48 bg-surface-container-lowest border border-outline-variant shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50">
              <a class="block px-4 py-3 text-body-md text-on-surface hover:bg-surface-container-low transition-colors" href="{{ route('profile.edit') }}">Profile</a>
              <a class="block px-4 py-3 text-body-md text-on-surface hover:bg-surface-container-low transition-colors" href="{{ route('wishlist') }}">My Wishlist</a>
              <a class="block px-4 py-3 text-body-md text-on-surface hover:bg-surface-container-low transition-colors" href="{{ route('orders.index') }}">My Orders</a>
              @if(Auth::user()->user_type == 'admin')
                <div class="border-t border-outline-variant"></div>
                <a class="block px-4 py-3 text-body-md text-primary font-bold hover:bg-surface-container-low transition-colors" href="{{ route('dashboard') }}">Admin Panel</a>
              @endif
              <div class="border-t border-outline-variant"></div>
              <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="block w-full text-left px-4 py-3 text-body-md text-error hover:bg-error-container/20 transition-colors font-bold">Logout</button>
              </form>
            </div>
          </div>
        @else
          <a href="{{ route('login') }}" class="active:opacity-80 active:scale-95 transition-all text-on-surface-variant hover:text-primary flex items-center gap-1">
            <span class="material-symbols-outlined">person</span>
            <span class="font-label-caps text-label-caps hidden lg:inline">Login</span>
          </a>
        @endauth
      </div>
    </header>

    {{-- Mobile slide-out menu --}}
    <div x-show="mobileMenu" x-transition:enter="transition-opacity duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition-opacity duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" @click="mobileMenu = false" class="md:hidden fixed inset-0 bg-black/40 z-40" style="display:none"></div>
    <div x-show="mobileMenu" x-transition:enter="transition-transform duration-300" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transition-transform duration-300" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full" @click.away="mobileMenu = false" class="md:hidden fixed top-0 left-0 bottom-0 w-72 bg-surface z-50 shadow-xl flex flex-col" style="display:none">
      <div class="flex items-center justify-between p-4 border-b border-outline-variant">
        <a href="{{ route('index') }}" class="font-headline-sm text-headline-sm text-on-surface font-bold" @click="mobileMenu = false">The Daily Cuts</a>
        <button @click="mobileMenu = false" class="text-on-surface-variant hover:text-primary"><span class="material-symbols-outlined">close</span></button>
      </div>
      <nav class="flex flex-col p-4 gap-1">
        <a href="{{ route('index') }}" class="{{ request()->routeIs('index') ? 'bg-primary/10 text-primary font-bold' : 'text-on-surface hover:bg-surface-container-low' }} px-4 py-3 rounded-lg text-body-lg transition-colors" @click="mobileMenu = false">Home</a>
        <a href="{{ route('shop') }}" class="{{ request()->routeIs('shop') || request()->routeIs('product_details') ? 'bg-primary/10 text-primary font-bold' : 'text-on-surface hover:bg-surface-container-low' }} px-4 py-3 rounded-lg text-body-lg transition-colors" @click="mobileMenu = false">Shop</a>
        <a href="{{ route('contact_us') }}" class="{{ request()->routeIs('contact_us') ? 'bg-primary/10 text-primary font-bold' : 'text-on-surface hover:bg-surface-container-low' }} px-4 py-3 rounded-lg text-body-lg transition-colors" @click="mobileMenu = false">Become A Reseller</a>
      </nav>
      <div class="border-t border-outline-variant mt-1 p-4 flex flex-col gap-1">
        @auth
          <a href="{{ route('profile.edit') }}" class="text-on-surface hover:bg-surface-container-low px-4 py-3 rounded-lg text-body-lg transition-colors" @click="mobileMenu = false">Profile</a>
          <a href="{{ route('wishlist') }}" class="text-on-surface hover:bg-surface-container-low px-4 py-3 rounded-lg text-body-lg transition-colors" @click="mobileMenu = false">My Wishlist</a>
          <a href="{{ route('orders.index') }}" class="text-on-surface hover:bg-surface-container-low px-4 py-3 rounded-lg text-body-lg transition-colors" @click="mobileMenu = false">My Orders</a>
          @if(Auth::user()->user_type == 'admin')
            <a href="{{ route('dashboard') }}" class="text-primary font-bold hover:bg-surface-container-low px-4 py-3 rounded-lg text-body-lg transition-colors" @click="mobileMenu = false">Admin Panel</a>
          @endif
          <div class="border-t border-outline-variant my-2"></div>
          <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="w-full text-left text-error hover:bg-error-container/20 px-4 py-3 rounded-lg text-body-lg font-bold transition-colors">Logout</button>
          </form>
        @else
          <a href="{{ route('login') }}" class="text-on-surface hover:bg-surface-container-low px-4 py-3 rounded-lg text-body-lg transition-colors" @click="mobileMenu = false">Login</a>
          <a href="{{ route('register') }}" class="bg-primary text-white text-center px-4 py-3 rounded-lg text-body-lg font-bold transition-colors" @click="mobileMenu = false">Register</a>
        @endauth
      </div>
    </div>
  </div>

  <main>
    @yield('index')
    @yield('product_details')
    @yield('contact_us')
    @yield('shop')
    @yield('login')
    @yield('register')
    @yield('viewcart')
    @yield('checkout')
    @yield('content')
    @yield('orders')
    @yield('profile')
    @yield('verify')
  </main>

  <footer class="bg-surface-container-low border-t border-outline-variant">
    <div class="w-full py-section-gap px-4 md:px-margin-desktop max-w-container-max mx-auto grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-gutter">
      <div class="col-span-1 sm:col-span-2 md:col-span-1">
        <a href="{{ route('index') }}" class="font-display-lg text-display-lg text-primary mb-6 block">The Daily Cuts</a>
        <p class="text-on-secondary-container font-body-md mb-8">Artisanal butchery meets digital excellence. We source from only the most committed local producers.</p>
        <div class="flex gap-4">
          <a href="https://www.facebook.com/profile.php?id=61581799587385" target="_blank" rel="noopener noreferrer" aria-label="Facebook" class="w-10 h-10 bg-surface-container-high rounded-full flex items-center justify-center text-primary hover:bg-primary hover:text-white transition-all">
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
              <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
            </svg>
          </a>
          <a href="https://www.instagram.com/thedailycutbygd/" target="_blank" rel="noopener noreferrer" aria-label="Instagram" class="w-10 h-10 bg-surface-container-high rounded-full flex items-center justify-center text-primary hover:bg-primary hover:text-white transition-all">
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
              <path d="M12 0C8.74 0 8.333.015 7.053.072 5.775.132 4.905.333 4.14.63c-.789.306-1.459.717-2.126 1.384S.935 3.35.63 4.14C.333 4.905.131 5.775.072 7.053.012 8.333 0 8.74 0 12s.015 3.667.072 4.947c.06 1.277.261 2.148.558 2.913.306.788.717 1.459 1.384 2.126.667.666 1.336 1.079 2.126 1.384.766.296 1.636.499 2.913.558C8.333 23.988 8.74 24 12 24s3.667-.015 4.947-.072c1.277-.06 2.148-.262 2.913-.558.788-.306 1.459-.718 2.126-1.384.666-.667 1.079-1.335 1.384-2.126.296-.765.499-1.636.558-2.913.06-1.28.072-1.687.072-4.947s-.015-3.667-.072-4.947c-.06-1.277-.262-2.149-.558-2.913-.306-.789-.718-1.459-1.384-2.126C21.319 1.347 20.651.935 19.86.63c-.765-.297-1.636-.499-2.913-.558C15.667.012 15.26 0 12 0zm0 2.16c3.203 0 3.585.016 4.85.071 1.17.055 1.805.249 2.227.415.562.217.96.477 1.382.896.419.42.679.819.896 1.381.164.422.36 1.057.413 2.227.057 1.266.07 1.646.07 4.85s-.015 3.585-.074 4.85c-.061 1.17-.256 1.805-.421 2.227-.224.562-.479.96-.899 1.382-.419.419-.824.679-1.38.896-.42.164-1.065.36-2.235.413-1.274.057-1.649.07-4.859.07-3.211 0-3.586-.015-4.859-.074-1.171-.061-1.816-.256-2.236-.421-.569-.224-.96-.479-1.379-.899-.421-.419-.69-.824-.9-1.38-.165-.42-.359-1.065-.42-2.235-.045-1.26-.061-1.649-.061-4.844 0-3.196.016-3.586.061-4.861.061-1.17.255-1.814.42-2.234.21-.57.479-.96.9-1.381.419-.419.81-.689 1.379-.898.42-.166 1.051-.361 2.221-.421 1.275-.045 1.65-.06 4.859-.06l.045.03zm0 3.678c-3.405 0-6.162 2.76-6.162 6.162 0 3.405 2.76 6.162 6.162 6.162 3.405 0 6.162-2.76 6.162-6.162 0-3.405-2.76-6.162-6.162-6.162zM12 16c-2.21 0-4-1.79-4-4s1.79-4 4-4 4 1.79 4 4-1.79 4-4 4zm7.846-10.405c0 .795-.646 1.44-1.44 1.44-.795 0-1.44-.646-1.44-1.44 0-.794.646-1.439 1.44-1.439.793-.001 1.44.645 1.44 1.439z"/>
            </svg>
          </a>
        </div>
      </div>
      <div class="col-span-1">
        <h4 class="font-headline-sm text-headline-sm mb-6 text-on-surface">Shop</h4>
        <nav class="flex flex-col gap-4">
          <a class="text-on-secondary-container hover:text-primary underline-offset-4 transition-all duration-300" href="{{ route('shop') }}">All Products</a>
          <a class="text-on-secondary-container hover:text-primary underline-offset-4 transition-all duration-300" href="{{ route('contact_us') }}">Become A Reseller</a>
          <a class="text-on-secondary-container hover:text-primary underline-offset-4 transition-all duration-300" href="{{ route('faq') }}">FAQ</a>
          <a class="text-on-secondary-container hover:text-primary underline-offset-4 transition-all duration-300" href="{{ route('index') }}">About Us</a>
        </nav>
      </div>
      <div class="col-span-1">
        <h4 class="font-headline-sm text-headline-sm mb-6 text-on-surface">Support</h4>
        <nav class="flex flex-col gap-4">
          <a class="text-on-secondary-container hover:text-primary underline-offset-4 transition-all duration-300" href="tel:+631234567891">+63 1234567891</a>
          <a class="text-on-secondary-container hover:text-primary underline-offset-4 transition-all duration-300" href="mailto:demo@gmail.com">demo@gmail.com</a>
          <a class="text-on-secondary-container hover:text-primary underline-offset-4 transition-all duration-300" href="https://www.google.com/maps/search/?api=1&query=PHASE+10+Wellington+Place" target="_blank" rel="noopener noreferrer">Find Our Stores</a>
        </nav>
      </div>
      <div class="col-span-1">
        <h4 class="font-headline-sm text-headline-sm mb-6 text-on-surface">Newsletter</h4>
        <p class="text-on-secondary-container font-body-md mb-4">Join our culinary club for exclusive cuts.</p>
        <div class="flex flex-col gap-2">
          <input class="bg-surface border-outline-variant rounded-lg p-3 text-body-md focus:ring-primary focus:border-primary" placeholder="Your email address" type="email">
          <button class="bg-primary text-white font-bold py-3 rounded-lg uppercase tracking-widest text-label-caps active:opacity-80 transition-all">Subscribe</button>
        </div>
      </div>
    </div>
    <div class="max-w-container-max mx-auto px-4 md:px-margin-desktop py-8 border-t border-outline-variant/30 flex flex-col md:flex-row justify-between items-center gap-4">
      <span class="text-on-secondary-container font-body-md text-center md:text-left text-sm">&copy; {{ date('Y') }} The Daily Cuts by GD. Artisanal butchery meets digital excellence.</span>
      <div class="flex gap-6">
        <a href="{{ route('privacy.policy') }}" class="text-on-secondary-container hover:text-primary text-sm font-body-md transition-colors">Privacy Policy</a>
        <a href="{{ route('terms.conditions') }}" class="text-on-secondary-container hover:text-primary text-sm font-body-md transition-colors">Terms &amp; Conditions</a>
        <a href="{{ route('store.policies') }}" class="text-on-secondary-container hover:text-primary text-sm font-body-md transition-colors">Store Policies</a>
      </div>
    </div>
  </footer>

  <nav class="md:hidden fixed bottom-0 left-0 right-0 z-50 bg-surface-container-lowest border-t border-outline-variant flex items-center justify-around h-[calc(4rem+env(safe-area-inset-bottom))] pb-[env(safe-area-inset-bottom)]" aria-label="Mobile navigation">
    <a href="{{ route('index') }}" class="{{ request()->routeIs('index') ? 'text-primary' : 'text-on-surface-variant' }} flex flex-col items-center gap-0.5 transition-colors">
      <span class="material-symbols-outlined">home</span>
      <small class="text-[10px] font-label-caps">Home</small>
    </a>
    <a href="{{ route('shop') }}" class="{{ request()->routeIs('shop') || request()->routeIs('product_details') ? 'text-primary' : 'text-on-surface-variant' }} flex flex-col items-center gap-0.5 transition-colors">
      <span class="material-symbols-outlined">storefront</span>
      <small class="text-[10px] font-label-caps">Shop</small>
    </a>
    <a href="{{ route('viewcart') }}" class="{{ request()->routeIs('viewcart') ? 'text-primary' : 'text-on-surface-variant' }} flex flex-col items-center gap-0.5 transition-colors relative">
      <span class="material-symbols-outlined">shopping_cart</span>
      @if($cartTotalCount > 0)
        <span class="absolute top-0 right-1 bg-primary text-white text-[8px] w-3 h-3 rounded-full flex items-center justify-center font-bold">{{ $cartTotalCount }}</span>
      @endif
      <small class="text-[10px] font-label-caps">Cart</small>
    </a>
    @auth
      <a href="{{ route('profile.edit') }}" class="text-on-surface-variant flex flex-col items-center gap-0.5 transition-colors">
        <span class="material-symbols-outlined">person</span>
        <small class="text-[10px] font-label-caps">Account</small>
      </a>
    @else
      <a href="{{ route('login') }}" class="text-on-surface-variant flex flex-col items-center gap-0.5 transition-colors">
        <span class="material-symbols-outlined">person</span>
        <small class="text-[10px] font-label-caps">Login</small>
      </a>
    @endauth
  </nav>

  <script src="{{ asset('frontend/js/jquery-3.4.1.min.js') }}"></script>
  <script src="{{ asset('frontend/js/bootstrap.js') }}"></script>
  <script src="{{ asset('frontend/js/custom.js') }}"></script>
  <script>
    document.addEventListener("DOMContentLoaded", function () {
      const snackbar = document.getElementById("cartSnackbar");
      if (snackbar) {
        snackbar.classList.add("show");
        setTimeout(() => snackbar.classList.remove("show"), 2200);
      }
    });
  </script>
</body>
</html>
