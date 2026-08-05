@extends('maindesign')
@section('shop')

@if(session('cartMessage'))
  <div id="cartSnackbar" class="fixed bottom-20 md:bottom-8 left-1/2 -translate-x-1/2 z-50 bg-inverse-surface text-inverse-on-surface px-6 py-3 rounded-lg shadow-lg flex items-center gap-2 font-body-md transition-all">
    <span class="material-symbols-outlined text-green-400">check_circle</span> {{ session('cartMessage') }}
  </div>
@endif

@if(session('orderMessage'))
  <div id="cartSnackbar" class="fixed bottom-20 md:bottom-8 left-1/2 -translate-x-1/2 z-50 bg-inverse-surface text-inverse-on-surface px-6 py-3 rounded-lg shadow-lg flex items-center gap-2 font-body-md transition-all">
    <span class="material-symbols-outlined text-green-400">check_circle</span> {{ session('orderMessage') }}
  </div>
@endif

<main class="max-w-container-max mx-auto px-4 md:px-margin-desktop py-8 md:py-12">
  <div x-data="{ sidebarOpen: false }" class="flex flex-col md:flex-row gap-gutter">

    {{-- Mobile sidebar toggle --}}
    <div class="md:hidden mb-2">
      <button @click="sidebarOpen = !sidebarOpen" class="flex items-center gap-2 text-on-surface font-bold py-2 px-4 border border-outline-variant rounded-lg w-full justify-between">
        <span class="flex items-center gap-2"><span class="material-symbols-outlined text-[20px]">filter_list</span> Filters</span>
        <span class="material-symbols-outlined text-[20px]" x-text="sidebarOpen ? 'expand_less' : 'expand_more'">expand_more</span>
      </button>
    </div>

    <aside class="w-full md:w-64 flex-shrink-0 space-y-10" :class="sidebarOpen ? 'block' : 'hidden md:block'">
      <div>
        <h3 class="font-headline-sm text-headline-sm mb-6 text-on-surface">Categories</h3>
        <ul class="space-y-4">
          <li>
            <a href="{{ route('shop') }}" class="flex items-center justify-between w-full group {{ !request('category') ? 'text-primary font-bold' : 'text-on-surface-variant hover:text-primary transition-colors' }}">
              <span class="font-body-lg text-body-lg">All Cuts</span>
            </a>
          </li>
          <li>
            <a href="{{ route('shop', array_merge(request()->query(), ['category' => 'best_sellers'])) }}" class="flex items-center justify-between w-full group {{ request('category') === 'best_sellers' ? 'text-primary font-bold' : 'text-on-surface-variant hover:text-primary transition-colors' }}">
              <span class="font-body-lg text-body-lg">Best Sellers</span>
            </a>
          </li>
          @foreach($categories as $cat)
          <li>
            <a href="{{ route('shop', array_merge(request()->query(), ['category' => $cat])) }}" class="flex items-center justify-between w-full group {{ request('category') === $cat ? 'text-primary font-bold' : 'text-on-surface-variant hover:text-primary transition-colors' }}">
              <span class="font-body-lg text-body-lg">{{ $cat }}</span>
            </a>
          </li>
          @endforeach
        </ul>
      </div>
      <div class="bg-surface-container-low p-4 rounded-lg flex items-start gap-3">
        <span class="material-symbols-outlined text-primary text-3xl">local_shipping</span>
        <p class="text-on-surface-variant text-body-md">Temperature-controlled delivery for Metro Manila orders.</p>
      </div>
    </aside>

    <div class="flex-1">
      <div class="flex flex-col md:flex-row md:items-center justify-between mb-6 md:mb-8 gap-4">
        <div>
          <h1 class="font-display-lg text-display-lg-mobile md:text-display-lg text-on-surface">Premium Fresh Cuts</h1>
          <p class="font-body-md text-on-surface-variant mt-2">
            @if(request('category') === 'best_sellers')
              Showing {{ $products->total() }} top-selling {{ Str::plural('cut', $products->total()) }} ranked by orders.
            @else
              Showing {{ $products->total() }} artisanal cuts from today&apos;s selection.
            @endif
          </p>
        </div>
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 sm:gap-4">
          <form action="{{ route('shop') }}" method="GET" class="flex items-center gap-2">
            @if(request('category'))
              <input type="hidden" name="category" value="{{ request('category') }}">
            @endif
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search cuts..." class="bg-surface border-outline-variant rounded-lg text-body-md py-2 px-4 focus:ring-primary focus:border-primary w-full sm:w-48">
          </form>
          <div class="flex items-center gap-2 sm:gap-4">
            <label class="text-label-caps font-label-caps text-on-surface-variant uppercase tracking-widest">Sort By</label>
            <select onchange="window.location.href=this.value" class="bg-surface border-outline-variant rounded-lg text-body-md py-2 px-4 focus:ring-primary focus:border-primary flex-1 sm:flex-none">
              @php $currentQuery = request()->query(); @endphp
              @if(request('category') === 'best_sellers')
                <option value="{{ route('shop', array_merge($currentQuery, ['sort' => 'best_sellers'])) }}" selected>Most Sold</option>
              @endif
              <option value="{{ route('shop', array_merge($currentQuery, ['sort' => 'latest'])) }}" {{ request('sort', request('category') === 'best_sellers' ? '' : 'latest') === 'latest' ? 'selected' : '' }}>Newest Arrival</option>
              <option value="{{ route('shop', array_merge($currentQuery, ['sort' => 'price_high'])) }}" {{ request('sort') === 'price_high' ? 'selected' : '' }}>Highest Price</option>
              <option value="{{ route('shop', array_merge($currentQuery, ['sort' => 'price_low'])) }}" {{ request('sort') === 'price_low' ? 'selected' : '' }}>Lowest Price</option>
              <option value="{{ route('shop', array_merge($currentQuery, ['sort' => 'name'])) }}" {{ request('sort') === 'name' ? 'selected' : '' }}>Name A-Z</option>
            </select>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-gutter">
        @forelse ($products as $product)
          @php
            $hasVariants = $product->hasVariants();
            $isOutOfStock = $hasVariants ? $product->variants->sum('quantity') <= 0 : !$product->inStock();
            $firstVariant = $hasVariants ? $product->variants->first() : null;
          @endphp
          <div class="bg-surface-container-lowest border border-[#EEEEEE] rounded-lg group hover:shadow-[0_4px_20px_rgba(0,0,0,0.05)] transition-all duration-300 flex flex-col relative overflow-hidden">
            <a href="{{ route('product_details', $product->id) }}" class="block relative aspect-square overflow-hidden bg-surface-container-high">
              @if(!$isOutOfStock)
                <span class="absolute top-4 left-4 z-10 bg-[#D4AF37] text-white px-3 py-1 text-label-caps font-label-caps uppercase rounded-sm">{{ $product->typeLabel() }}</span>
              @endif
              <button class="absolute top-4 right-4 z-10 text-on-surface-variant hover:text-[#D4AF37] active:scale-90 transition-transform bg-white/80 p-1.5 rounded-full">
                <span class="material-symbols-outlined text-[20px]">star</span>
              </button>
              <img class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" alt="{{ $product->product_title }}" src="{{ asset('products/'.$product->product_image) }}">
            </a>
            <div class="p-6 flex-1 flex flex-col justify-between">
              <div>
                <p class="text-on-secondary-container font-body-md mb-1 italic">{{ $product->product_category }}</p>
                <h3 class="font-headline-sm text-headline-sm text-on-surface mb-1">{{ $product->product_title }}</h3>
                <p class="text-on-secondary-container font-body-md mb-4 text-sm line-clamp-2">{{ Str::limit($product->product_description, 80) }}</p>

                @if($hasVariants)
                  <p class="shop-card-price font-price-display text-price-display text-primary mb-2" data-product="{{ $product->id }}">&#8369;{{ number_format($firstVariant->price, 2) }}</p>
                  <div class="flex flex-wrap gap-1.5 mb-4" id="shopVariant{{ $product->id }}">
                    @foreach($product->variants as $variant)
                      <button
                        type="button"
                        data-id="{{ $variant->id }}"
                        data-price="{{ number_format($variant->price, 2) }}"
                        data-stock="{{ $variant->quantity }}"
                        onclick="selectShopVariant(this, {{ $product->id }})"
                        class="shop-variant-btn px-3 py-1.5 border-2 rounded-lg font-bold text-xs transition-all {{ $loop->first ? 'border-primary bg-primary text-white' : 'border-outline-variant text-on-surface hover:border-primary' }}"
                      >{{ $variant->weight }}</button>
                    @endforeach
                  </div>
                @else
                  <div class="flex items-center justify-between mb-6">
                    <span class="text-price-display font-price-display text-primary">&#8369;{{ number_format($product->product_price, 2) }}</span>
                  </div>
                @endif
              </div>
              <div class="flex items-center gap-2">
                <form action="{{ route('cart.add', $product->id) }}" method="POST" class="w-full" data-submit-once>
                  @csrf
                  @if($hasVariants)
                    <input type="hidden" name="variant_id" value="{{ $firstVariant->id }}">
                  @endif
                  <button type="submit" class="w-full bg-primary text-white font-bold py-3 px-4 active:opacity-80 transition-all uppercase tracking-widest text-label-caps" {{ $isOutOfStock ? 'disabled' : '' }}>
                    {{ $isOutOfStock ? 'Out of Stock' : 'Add to Cart' }}
                  </button>
                </form>
              </div>
            </div>
          </div>
        @empty
          <div class="col-span-full text-center py-20">
            <span class="material-symbols-outlined text-6xl text-on-surface-variant mb-4">search_off</span>
            <h3 class="font-headline-sm text-headline-sm text-on-surface mb-2">No products found</h3>
            <p class="text-on-surface-variant mb-6">Try adjusting your search or filter criteria.</p>
            <a href="{{ route('shop') }}" class="bg-primary text-white px-6 py-3 rounded-lg font-bold">View All Products</a>
          </div>
        @endforelse
      </div>

      @if($products->hasPages())
      <div class="mt-10 flex justify-center">
        {{ $products->links() }}
      </div>
      @endif
    </div>
  </div>
</main>

<script>
function selectShopVariant(btn, productId) {
  var container = document.getElementById('shopVariant' + productId);
  container.querySelectorAll('.shop-variant-btn').forEach(function(b) {
    b.classList.remove('border-primary', 'bg-primary', 'text-white');
    b.classList.add('border-outline-variant', 'text-on-surface');
  });
  btn.classList.remove('border-outline-variant', 'text-on-surface');
  btn.classList.add('border-primary', 'bg-primary', 'text-white');

  var priceEl = container.closest('.p-6').querySelector('.shop-card-price');
  if (priceEl) priceEl.innerHTML = '&#8369;' + btn.dataset.price;

  var form = container.closest('.p-6').querySelector('form');
  var variantInput = form.querySelector('input[name="variant_id"]');
  if (variantInput) variantInput.value = btn.dataset.id;
}
</script>

@endsection
