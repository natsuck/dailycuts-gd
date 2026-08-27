@extends('maindesign')
@section('product_details')

<main class="max-w-container-max mx-auto px-margin-mobile md:px-margin-desktop py-12">
  <nav class="flex items-center gap-2 mb-8 text-on-surface-variant font-label-caps text-label-caps">
    <a class="hover:text-primary transition-colors" href="{{ route('shop') }}">SHOP</a>
    <span class="material-symbols-outlined text-[12px]">chevron_right</span>
    <span class="text-on-surface">{{ strtoupper($product->product_title) }}</span>
  </nav>

  <div class="grid grid-cols-1 lg:grid-cols-12 gap-gutter">
    <div class="lg:col-span-7 flex flex-col gap-4">
      <div class="aspect-square bg-surface-container-low border border-outline-variant overflow-hidden group">
        <img id="detailMainImage" alt="{{ $product->product_title }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700" src="{{ asset('products/'.$product->product_image) }}">
      </div>
      <div class="flex gap-2 md:gap-4">
        <button type="button" class="aspect-square w-20 md:w-24 border-2 border-primary overflow-hidden thumb-active">
          <img alt="{{ $product->product_title }}" class="w-full h-full object-cover" src="{{ asset('products/'.$product->product_image) }}">
        </button>
      </div>
    </div>

    <div class="lg:col-span-5 flex flex-col gap-6">
      <div>
        <span class="inline-block px-3 py-1 bg-tertiary text-on-tertiary font-label-caps text-label-caps mb-4">{{ strtoupper($product->typeLabel()) }}</span>
        <h1 class="font-display-lg text-display-lg-mobile md:text-display-lg text-on-surface mb-2">{{ $product->product_title }}</h1>
        <div class="flex items-center gap-4 mb-4">
          <div class="flex text-tertiary">
            @for($i = 1; $i <= 5; $i++)
              <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' {{ $i <= $product->averageRating() ? 1 : 0 }};">star</span>
            @endfor
          </div>
          <span class="text-on-surface-variant font-body-md text-body-md">{{ $product->reviewCount() }} review{{ $product->reviewCount() !== 1 ? 's' : '' }}</span>
        </div>

        @if($product->hasVariants())
          <p id="variantPrice" class="font-price-display text-price-display text-primary"><span id="variantPriceValue">&#8369;{{ number_format($product->variants->first()->price, 2) }}</span> <span id="variantWeight" class="text-on-surface-variant text-body-md font-normal">/ {{ $product->variants->first()->weight }}</span></p>
          <p id="variantStock" class="text-tertiary font-body-sm text-body-sm mt-1">{{ $product->variants->first()->quantity }} in stock</p>
        @else
          <p class="font-price-display text-price-display text-primary">&#8369;{{ number_format($product->product_price, 2) }} <span class="text-on-surface-variant text-body-md font-normal">/ item</span></p>
          @if($product->product_quantity > 0)
            <p class="text-tertiary font-body-sm text-body-sm mt-1">{{ $product->product_quantity }} in stock</p>
          @else
            <p class="text-error font-body-sm text-body-sm mt-1">Out of stock</p>
          @endif
        @endif
      </div>
      <div class="h-px bg-outline-variant"></div>
      <p class="text-on-surface-variant font-body-md text-body-md">
        {{ $product->product_description ?? 'Fresh and high-quality meat, delivered daily. Each cut is packed for freshness and handled with clean, careful preparation.' }}
      </p>

      @if($product->hasVariants())
        <div>
          <label class="font-label-caps text-label-caps text-on-surface-variant mb-2 block">Select Weight</label>
          <div class="flex flex-wrap gap-2" id="variantSelector">
            @foreach($product->variants as $variant)
              <button
                type="button"
                data-id="{{ $variant->id }}"
                data-price="{{ number_format($variant->price, 2) }}"
                data-stock="{{ $variant->quantity }}"
                data-weight="{{ $variant->weight }}"
                onclick="selectVariant(this)"
                class="variant-btn px-4 py-2 border-2 rounded-lg font-bold text-sm transition-all {{ $loop->first ? 'border-primary bg-primary text-white' : 'border-outline-variant text-on-surface hover:border-primary' }}"
              >{{ $variant->weight }}</button>
            @endforeach
          </div>
          <input type="hidden" name="variant_id" id="variantId" value="{{ $product->variants->first()->id }}">
        </div>
      @endif

      <div class="flex gap-3">
        <form action="{{ route('cart.add', $product->id) }}" method="POST" class="flex flex-col sm:flex-row gap-4 flex-1" data-submit-once>
          @csrf
          @if($product->hasVariants())
            <input type="hidden" name="variant_id" id="cartVariantId" value="{{ $product->variants->first()->id }}">
          @endif
          <div class="flex items-center border border-outline h-12">
            <button type="button" class="px-4 hover:bg-surface-container transition-colors" onclick="decrement()"><span class="material-symbols-outlined">remove</span></button>
            <input class="w-12 text-center border-none focus:ring-0 bg-transparent font-bold" id="qty" readonly type="number" name="quantity" value="1" min="1" max="{{ $product->hasVariants() ? $product->variants->first()->quantity : $product->product_quantity }}">
            <button type="button" class="px-4 hover:bg-surface-container transition-colors" onclick="increment()"><span class="material-symbols-outlined">add</span></button>
          </div>
          @php
            $isOutOfStock = $product->hasVariants()
              ? $product->variants->sum('quantity') <= 0
              : !$product->inStock();
          @endphp
          <button type="submit" class="flex-grow bg-primary text-on-primary font-bold h-12 flex items-center justify-center gap-2 hover:opacity-90 active:scale-95 transition-all" {{ $isOutOfStock ? 'disabled' : '' }}>
            <span class="material-symbols-outlined">shopping_cart</span>
            {{ $isOutOfStock ? 'OUT OF STOCK' : 'ADD TO CART' }}
          </button>
        </form>
        @auth
          <form action="{{ route('wishlist.toggle', $product->id) }}" method="POST">
            @csrf
            <button type="submit" class="h-12 w-12 flex items-center justify-center border border-outline-variant hover:border-primary hover:text-primary transition-colors {{ Auth::user()->wishlistedItems()->where('product_id', $product->id)->exists() ? 'text-error' : 'text-on-surface-variant' }}">
              <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' {{ Auth::user()->wishlistedItems()->where('product_id', $product->id)->exists() ? 1 : 0 }};">favorite</span>
            </button>
          </form>
        @endauth
      </div>

      <div class="bg-surface-container-low p-4 space-y-2">
        <div class="flex items-center gap-3 text-on-surface-variant text-[14px]">
          <span class="material-symbols-outlined text-primary">local_shipping</span>
          <span>Temperature-controlled local delivery</span>
        </div>
        <div class="flex items-center gap-3 text-on-surface-variant text-[14px]">
          <span class="material-symbols-outlined text-primary">verified</span>
          <span>Clean packing and freshness-first handling</span>
        </div>
      </div>
    </div>
  </div>

  <section class="mt-section-gap">
    <div class="border-b border-outline-variant flex gap-8 mb-8 overflow-x-auto hide-scrollbar">
      <button class="pb-4 border-b-2 border-primary text-primary font-bold whitespace-nowrap">Product Details</button>
      <button class="pb-4 text-on-surface-variant hover:text-on-surface whitespace-nowrap">Freshness Notes</button>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
      <div class="space-y-6">
        <h3 class="font-headline-md text-headline-md">Exceptional Daily Quality</h3>
        <p class="text-on-surface-variant leading-relaxed">Every product is prepared with the simple goal of keeping your kitchen stocked with fresh, reliable cuts. We prioritize clean handling, practical portions, and dependable availability.</p>
        <ul class="space-y-3">
          <li class="flex items-center gap-3"><span class="material-symbols-outlined text-tertiary text-[18px]">check_circle</span> Freshly packed for delivery</li>
          <li class="flex items-center gap-3"><span class="material-symbols-outlined text-tertiary text-[18px]">check_circle</span> Locally sourced when possible</li>
          <li class="flex items-center gap-3"><span class="material-symbols-outlined text-tertiary text-[18px]">check_circle</span> Selected for everyday cooking quality</li>
        </ul>
      </div>
      <div class="bg-white border border-outline-variant p-8">
        <h3 class="font-headline-md text-headline-md mb-6 uppercase tracking-widest border-b-4 border-on-surface pb-2">Freshness Notes</h3>
        <div class="space-y-3 font-body-md">
          @foreach($product->freshnessNotes() as $noteKey => $noteValue)
            <p class="border-b border-outline-variant pb-1 flex justify-between"><span>{{ $noteKey }}</span> <span class="font-bold">{{ $noteValue }}</span></p>
          @endforeach
        </div>
      </div>
    </div>
  </section>

  <section class="mt-section-gap" id="reviews">
    <h2 class="font-headline-md text-headline-md mb-6">Customer Reviews ({{ $product->reviewCount() }})</h2>

    @auth
      @php
        $hasDeliveredOrder = \App\Models\OrderItem::where('product_id', $product->id)
            ->whereHas('order', function ($q) {
                $q->where('user_id', Auth::id())
                    ->where('payment_status', 'paid')
                    ->where('status', 'delivered');
            })->exists();
        $existingReview = $product->reviews()->where('user_id', Auth::id())->first();
      @endphp

      @if($hasDeliveredOrder || $existingReview)
        <div class="bg-surface-container-low p-6 rounded-lg mb-8" id="review-form">
          <h3 class="font-headline-sm text-headline-sm mb-4">Write a Review</h3>
          @if($existingReview)
            <p class="text-on-surface-variant mb-4">You&apos;ve already reviewed this product. You can update your review below.</p>
          @endif
          <form action="{{ route('reviews.store', $product->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="mb-4">
              <label class="block font-body-md text-body-md text-on-surface mb-2">Rating</label>
              <div class="flex gap-1" id="ratingStars">
                @for($i = 1; $i <= 5; $i++)
                  <button type="button" onclick="setRating({{ $i }})" class="text-on-surface-variant hover:text-tertiary transition-colors">
                    <span class="material-symbols-outlined star-btn" data-rating="{{ $i }}" style="font-variation-settings: 'FILL' {{ ($existingReview && $existingReview->rating >= $i) ? 1 : 0 }};">star</span>
                  </button>
                @endfor
                <input type="hidden" name="rating" id="ratingInput" value="{{ $existingReview?->rating ?? '' }}">
              </div>
              @error('rating')<p class="text-error text-body-sm mt-1">{{ $message }}</p>@enderror
            </div>
            <div class="mb-4">
              <label class="block font-body-md text-body-md text-on-surface mb-2">Title (optional)</label>
              <input type="text" name="title" value="{{ $existingReview?->title }}" class="w-full border border-outline-variant rounded-lg px-4 py-2 bg-surface focus:ring-primary focus:border-primary" maxlength="255">
            </div>
            <div class="mb-4">
              <label class="block font-body-md text-body-md text-on-surface mb-2">Your Review</label>
              <textarea name="body" rows="3" class="w-full border border-outline-variant rounded-lg px-4 py-2 bg-surface focus:ring-primary focus:border-primary" maxlength="2000">{{ $existingReview?->body }}</textarea>
              @error('body')<p class="text-error text-body-sm mt-1">{{ $message }}</p>@enderror
            </div>
            <div class="mb-4">
              <label class="block font-body-md text-body-md text-on-surface mb-2">Photos (optional, max 5)</label>
              <input type="file" name="images[]" id="reviewImages" multiple accept="image/jpg,image/jpeg,image/png,image/webp" class="w-full border border-outline-variant rounded-lg px-4 py-2 bg-surface focus:ring-primary focus:border-primary text-sm file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-primary file:text-white file:font-bold file:text-sm file:cursor-pointer file:hover:opacity-90">
              @error('images')<p class="text-error text-body-sm mt-1">{{ $message }}</p>@enderror
              @error('images.*')<p class="text-error text-body-sm mt-1">{{ $message }}</p>@enderror
              <div id="imagePreview" class="flex flex-wrap gap-2 mt-2"></div>
            </div>
            <button type="submit" class="bg-primary text-white px-6 py-2 rounded-lg font-bold hover:opacity-90 transition-all">
              {{ $existingReview ? 'Update Review' : 'Submit Review' }}
            </button>
          </form>
        </div>
      @else
        <div class="bg-surface-container-low p-6 rounded-lg mb-8 text-center">
          <p class="text-on-surface-variant">You can review this product after your order is delivered.</p>
        </div>
      @endif
    @else
      <div class="bg-surface-container-low p-6 rounded-lg mb-8 text-center">
        <p class="text-on-surface-variant mb-2">Please <a href="{{ route('login') }}" class="text-primary font-bold hover:underline">log in</a> to write a review.</p>
      </div>
    @endauth

    @forelse($reviews as $review)
      <div class="border-b border-outline-variant py-6">
        <div class="flex items-center gap-3 mb-2">
          <div class="w-8 h-8 bg-primary text-white rounded-full flex items-center justify-center font-bold text-sm">
            {{ strtoupper(substr($review->user->name, 0, 1)) }}
          </div>
          @php
            $rname = $review->user->name ?? 'Anonymous';
            $censoredName = strtok($rname, ' ') . ' ' . strtoupper(substr(strrchr($rname, ' ') ?: ' X', 1, 1)) . '.';
          @endphp
          <span class="font-bold text-on-surface">{{ $censoredName }}</span>
          <span class="text-on-surface-variant text-body-sm">{{ $review->created_at->diffForHumans() }}</span>
        </div>
        <div class="flex text-tertiary mb-2">
          @for($i = 1; $i <= 5; $i++)
            <span class="material-symbols-outlined text-[18px]" style="font-variation-settings: 'FILL' {{ $i <= $review->rating ? 1 : 0 }};">star</span>
          @endfor
        </div>
        @if($review->title)
          <h4 class="font-bold text-on-surface mb-1">{{ $review->title }}</h4>
        @endif
        <p class="text-on-surface-variant mb-3">{{ $review->body }}</p>
        @if($review->images && count($review->images) > 0)
          <div class="flex flex-wrap gap-2 mb-3">
            @foreach($review->images as $image)
              <a href="{{ asset('reviews/'.$image) }}" target="_blank" rel="noopener noreferrer" class="block w-20 h-20 rounded-lg overflow-hidden border border-outline-variant hover:border-primary transition-colors">
                <img src="{{ asset('reviews/'.$image) }}" alt="Review photo" loading="lazy" class="w-full h-full object-cover">
              </a>
            @endforeach
          </div>
        @endif
        @auth
          @if(Auth::id() === $review->user_id)
            <form action="{{ route('reviews.destroy', $review->id) }}" method="POST" class="mt-2">
              @csrf
              @method('DELETE')
              <button type="submit" onclick="return confirm('Delete this review?')" class="text-error text-body-sm hover:underline">Delete Review</button>
            </form>
          @endif
        @endauth
      </div>
    @empty
      <p class="text-on-surface-variant py-6">No reviews yet. Be the first to review this product!</p>
    @endforelse

    @if($reviews->hasPages())
      <div class="mt-8">
        {{ $reviews->links() }}
      </div>
    @endif
  </section>
</main>

@if($pairingProducts->isNotEmpty())
  <section class="bg-surface-container-low py-section-gap">
    <div class="px-4 md:px-margin-desktop max-w-container-max mx-auto">
      <h2 class="font-headline-md text-headline-md mb-6 md:mb-8">Pairs Well With</h2>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-gutter">
        @foreach ($pairingProducts as $related)
          @php
            $pHasVariants = $related->hasVariants();
            $pIsOutOfStock = $pHasVariants ? $related->variants->sum('quantity') <= 0 : !$related->inStock();
            $pFirstVariant = $pHasVariants ? $related->variants->first() : null;
          @endphp
          <div class="bg-surface-container-lowest border border-[#EEEEEE] rounded-lg group hover:shadow-[0_4px_20px_rgba(0,0,0,0.05)] transition-all duration-300 flex flex-col relative overflow-hidden">
            <a href="{{ route('product_details', $related->id) }}" class="block relative aspect-square overflow-hidden bg-surface-container-high">
              @if(!$pIsOutOfStock)
                <span class="absolute top-4 left-4 z-10 bg-[#D4AF37] text-white px-3 py-1 text-label-caps font-label-caps uppercase rounded-sm">{{ $related->typeLabel() }}</span>
              @endif
              <img class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" alt="{{ $related->product_title }}" src="{{ asset('products/'.$related->product_image) }}">
            </a>
            <div class="p-6 flex-1 flex flex-col justify-between">
              <div>
                <p class="text-on-secondary-container font-body-md mb-1 italic">{{ $related->product_category }}</p>
                <h3 class="font-headline-sm text-headline-sm text-on-surface mb-1">{{ $related->product_title }}</h3>
                <p class="text-on-secondary-container font-body-md mb-4 text-sm line-clamp-2">{{ Str::limit($related->product_description, 80) }}</p>
                @if($pHasVariants)
                  <p class="font-price-display text-price-display text-primary mb-2">&#8369;{{ number_format($pFirstVariant->price, 2) }}</p>
                  <p class="font-body-md text-body-md text-secondary mb-4">{{ $pFirstVariant->weight }}</p>
                @else
                  <div class="flex items-center justify-between mb-6">
                    <span class="text-price-display font-price-display text-primary">&#8369;{{ number_format($related->product_price, 2) }}</span>
                  </div>
                @endif
              </div>
              <div class="flex items-center gap-2">
                <form action="{{ route('cart.add', $related->id) }}" method="POST" class="w-full" data-submit-once>
                  @csrf
                  @if($pHasVariants)
                    <input type="hidden" name="variant_id" value="{{ $pFirstVariant->id }}">
                  @endif
                  <button type="submit" class="w-full bg-primary text-white font-bold py-3 px-4 active:opacity-80 transition-all uppercase tracking-widest text-label-caps" {{ $pIsOutOfStock ? 'disabled' : '' }}>
                    {{ $pIsOutOfStock ? 'Out of Stock' : 'Add to Cart' }}
                  </button>
                </form>
              </div>
            </div>
          </div>
        @endforeach
      </div>
    </div>
  </section>
@endif

<script>
  function increment() {
    const input = document.getElementById('qty');
    const max = parseInt(input.max, 10);
    const next = parseInt(input.value, 10) + 1;
    input.value = (!isNaN(max) && next > max) ? max : next;
  }
  function decrement() {
    const input = document.getElementById('qty');
    if (parseInt(input.value) > 1) {
      input.value = parseInt(input.value) - 1;
    }
  }

  function selectVariant(btn) {
    document.querySelectorAll('.variant-btn').forEach(b => {
      b.classList.remove('border-primary', 'bg-primary', 'text-white');
      b.classList.add('border-outline-variant', 'text-on-surface');
    });
    btn.classList.remove('border-outline-variant', 'text-on-surface');
    btn.classList.add('border-primary', 'bg-primary', 'text-white');

    const price = btn.dataset.price;
    const stock = btn.dataset.stock;
    const weight = btn.dataset.weight;
    const variantId = btn.dataset.id;

    document.getElementById('variantPriceValue').textContent = '₱' + price;
    document.getElementById('variantWeight').textContent = '/ ' + weight;
    document.getElementById('variantStock').textContent = stock + ' in stock';

    var cartVariantInput = document.getElementById('cartVariantId');
    if (cartVariantInput) cartVariantInput.value = variantId;

    var qtyInput = document.getElementById('qty');
    qtyInput.max = stock;
    if (parseInt(qtyInput.value) > parseInt(stock)) qtyInput.value = stock > 0 ? 1 : 0;
  }

  function setRating(rating) {
    document.getElementById('ratingInput').value = rating;
    document.querySelectorAll('.star-btn').forEach(btn => {
      const r = parseInt(btn.dataset.rating);
      btn.style.fontVariationSettings = `'FILL' ${r <= rating ? 1 : 0}`;
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    const mainImage = document.getElementById('detailMainImage');
    const thumbs = document.querySelectorAll('.thumb-active, [class*="aspect-square border border-outline-variant"]');
    thumbs.forEach(function (thumb) {
      thumb.addEventListener('click', function () {
        const img = this.querySelector('img');
        if (!img) return;
        mainImage.src = img.src;
        thumbs.forEach(t => {
          t.classList.remove('border-2', 'border-primary');
          t.classList.add('border', 'border-outline-variant');
        });
        this.classList.remove('border', 'border-outline-variant');
        this.classList.add('border-2', 'border-primary');
      });
    });

    const reviewImagesInput = document.getElementById('reviewImages');
    const imagePreview = document.getElementById('imagePreview');
    if (reviewImagesInput && imagePreview) {
      reviewImagesInput.addEventListener('change', function () {
        imagePreview.innerHTML = '';
        const files = Array.from(this.files).slice(0, 5);
        files.forEach(function (file) {
          const reader = new FileReader();
          reader.onload = function (e) {
            const wrapper = document.createElement('div');
            wrapper.className = 'relative w-20 h-20';
            wrapper.innerHTML = '<img src="' + e.target.result + '" class="w-full h-full object-cover rounded-lg border border-outline-variant">';
            imagePreview.appendChild(wrapper);
          };
          reader.readAsDataURL(file);
        });
      });
    }
  });
</script>

@endsection
