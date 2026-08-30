@php
    $seo = ['robots' => 'noindex, nofollow'];
@endphp

@extends('maindesign')
@section('viewcart')

<main class="max-w-container-max mx-auto px-4 md:px-margin-desktop py-8 md:py-12 md:py-section-gap">
  @if($cart->isEmpty())
    <div class="flex flex-col items-center justify-center py-20 text-center">
      <span class="material-symbols-outlined text-6xl text-on-surface-variant mb-6">shopping_cart</span>
      <h1 class="font-display-lg text-display-lg-mobile text-on-surface mb-4">Your basket is empty.</h1>
      <p class="font-body-lg text-body-lg text-on-surface-variant mb-8">Add fresh cuts from the shop before proceeding.</p>
      <a href="{{ route('shop') }}" class="bg-primary text-white font-bold py-4 px-8 rounded-lg hover:opacity-90 transition-all active:scale-95">Return To Shop</a>
    </div>
  @else
    <header class="mb-12">
      <h2 class="font-display-lg text-display-lg-mobile md:text-display-lg text-on-surface mb-2">Your Basket</h2>
      <p class="font-body-lg text-body-lg text-secondary">Review your selection of premium cuts before proceeding.</p>
    </header>

    <div class="flex flex-col lg:flex-row gap-gutter items-start">
      <section class="w-full lg:w-2/3 space-y-6">
        @foreach($cart as $cartProduct)
          @php
            $productVariants = $cartProduct->product->variants;
            $hasVariants = $productVariants->isNotEmpty();
          @endphp
          <article class="bg-surface-container-lowest border border-[#EEEEEE] flex flex-col md:flex-row p-6 gap-6 group hover:shadow-[0px_4px_20px_rgba(0,0,0,0.05)] transition-shadow" data-cart-id="{{ $cartProduct->id }}">
            <div class="w-full md:w-40 cart-item-image-container bg-surface-container flex-shrink-0 relative overflow-hidden">
              <img alt="{{ $cartProduct->product->product_title }}" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105" src="{{ asset('products/'.$cartProduct->product->product_image) }}">
              <span class="absolute top-2 left-2 bg-[#D4AF37] text-white font-label-caps text-label-caps px-2 py-1">{{ $cartProduct->product->typeLabel() }}</span>
            </div>
            <div class="flex-grow flex flex-col justify-between">
              <div class="flex justify-between items-start">
                <div>
                  <h3 class="font-headline-md text-headline-md text-on-surface">{{ $cartProduct->product->product_title }}</h3>
                  @if($hasVariants)
                    <div class="flex flex-wrap gap-1.5 mt-1 mb-1 cart-variant-selector" data-cart-id="{{ $cartProduct->id }}">
                      @foreach($productVariants as $pv)
                        <button
                          type="button"
                          data-variant-id="{{ $pv->id }}"
                          data-price="{{ number_format($pv->price, 2) }}"
                          data-stock="{{ $pv->quantity }}"
                          data-weight="{{ $pv->weight }}"
                          onclick="changeCartVariant(this)"
                          class="cart-variant-btn px-2.5 py-1 border-2 rounded-lg text-[11px] font-bold transition-all {{ $cartProduct->variant_id == $pv->id ? 'border-primary bg-primary text-white' : 'border-outline-variant text-on-surface hover:border-primary' }}"
                        >{{ $pv->weight }}</button>
                      @endforeach
                    </div>
                  @elseif($cartProduct->variant)
                    <p class="font-label-caps text-label-caps text-on-surface-variant mb-1">{{ $cartProduct->variant->weight }}</p>
                  @endif
                  <p class="font-body-md text-body-md text-secondary cart-item-unitprice" data-cart-id="{{ $cartProduct->id }}">&#8369;{{ number_format($cartProduct->unitPrice(), 2) }} / {{ $cartProduct->variant ? $cartProduct->variant->weight : 'unit' }}</p>
                </div>
                <form action="{{ route('cart.remove', $cartProduct->id) }}" method="POST">
                  @csrf
                  @method('DELETE')
                  <button class="material-symbols-outlined text-on-surface-variant hover:text-primary transition-colors" aria-label="Remove {{ $cartProduct->product->product_title }}">close</button>
                </form>
              </div>
              <div class="flex justify-between items-end mt-4">
                <div class="flex items-center border-2 border-secondary overflow-hidden" data-id="{{ $cartProduct->id }}">
                  <button type="button" class="qty-minus px-4 py-2 hover:bg-secondary-container transition-colors">-</button>
                  <input class="qty-input w-12 text-center border-none focus:ring-0 font-body-md text-body-md bg-transparent" min="1" type="number" value="{{ $cartProduct->quantity ?? 1 }}">
                  <button type="button" class="qty-plus px-4 py-2 hover:bg-secondary-container transition-colors">+</button>
                </div>
                <div class="text-right">
                  <p class="font-price-display text-price-display text-primary subtotal cart-item-subtotal" data-cart-id="{{ $cartProduct->id }}">&#8369;{{ number_format($cartProduct->subtotal(), 2) }}</p>
                  <p class="text-[12px] text-secondary font-label-caps cart-item-unitprice-bottom" data-cart-id="{{ $cartProduct->id }}">&#8369;{{ number_format($cartProduct->unitPrice(), 2) }} / {{ $cartProduct->variant ? $cartProduct->variant->weight : 'unit' }}</p>
                </div>
              </div>
            </div>
          </article>
        @endforeach

        @if($pairingProducts->isNotEmpty())
          <div class="bg-primary-container/5 border-2 border-dashed border-primary-container p-6 rounded-lg">
            <div class="flex items-center gap-3 mb-6">
              <span class="material-symbols-outlined text-primary text-3xl">restaurant</span>
              <div>
                <p class="font-headline-sm text-headline-sm text-on-primary-fixed">Pairs Well With</p>
                <p class="font-body-md text-body-md text-secondary">Complete your basket with these hand-picked pairings.</p>
              </div>
            </div>
            <div class="flex gap-3 overflow-x-auto snap-x snap-mandatory pb-2 lg:grid lg:grid-cols-4 lg:overflow-visible lg:pb-0">
              @foreach($pairingProducts as $pairing)
                @php
                  $pHasVariants = $pairing->hasVariants();
                  $pIsOutOfStock = $pHasVariants ? $pairing->variants->sum('quantity') <= 0 : !$pairing->inStock();
                  $pFirstVariant = $pHasVariants ? $pairing->variants->first() : null;
                @endphp
                <div class="w-[70%] sm:w-[45%] lg:w-auto flex-shrink-0 snap-start bg-surface-container-lowest border border-[#EEEEEE] rounded-lg group hover:shadow-[0_4px_20px_rgba(0,0,0,0.05)] transition-all duration-300 flex flex-col relative overflow-hidden">
                  <a href="{{ route('product_details', $pairing->id) }}" class="block relative aspect-[4/3] overflow-hidden bg-surface-container-high">
                    @if(!$pIsOutOfStock)
                      <span class="absolute top-2 left-2 z-10 bg-[#D4AF37] text-white px-2 py-0.5 text-label-caps font-label-caps uppercase rounded-sm">{{ $pairing->typeLabel() }}</span>
                    @endif
                    <img class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" alt="{{ $pairing->product_title }}" src="{{ asset('products/'.$pairing->product_image) }}">
                  </a>
                  <div class="p-3 flex flex-col gap-1.5 flex-1">
                    <h4 class="font-bold text-[13px] leading-tight">{{ $pairing->product_title }}</h4>
                    <p class="font-price-display text-price-display text-primary">&#8369;{{ number_format($pHasVariants ? $pFirstVariant->price : $pairing->product_price, 2) }}</p>
                    <form action="{{ route('cart.add', $pairing->id) }}" method="POST" class="mt-auto" data-submit-once>
                      @csrf
                      @if($pHasVariants)
                        <input type="hidden" name="variant_id" value="{{ $pFirstVariant->id }}">
                      @endif
                      <button type="submit" class="w-full bg-primary text-white text-[11px] font-bold py-1.5 px-3 uppercase tracking-widest rounded hover:opacity-90 transition-all" {{ $pIsOutOfStock ? 'disabled' : '' }}>
                        {{ $pIsOutOfStock ? 'Out of Stock' : 'Add' }}
                      </button>
                    </form>
                  </div>
                </div>
              @endforeach
            </div>
          </div>
        @endif
      </section>

      <aside class="w-full lg:w-1/3 sticky top-28">
        <div class="bg-surface-container-lowest border border-[#EEEEEE] p-8 shadow-[0px_4px_20px_rgba(0,0,0,0.05)]">
          <h3 class="font-headline-md text-headline-md text-on-surface mb-8 pb-4 border-b border-outline-variant">Order Summary</h3>
          <div class="space-y-4 mb-8">
            <div class="flex justify-between font-body-md text-body-md">
              <span class="text-secondary">Subtotal</span>
              <span class="text-on-surface cart-subtotal">&#8369;{{ number_format($total, 2) }}</span>
            </div>
          </div>
          <div class="pt-6 border-t border-outline-variant mb-8">
            <div class="flex justify-between items-end">
              <span class="font-headline-sm text-headline-sm text-on-surface">Total</span>
              <span class="font-display-lg text-[32px] text-primary tracking-tight leading-none cart-grand-total">&#8369;{{ number_format($grandTotal, 2) }}</span>
            </div>
            <p class="text-right text-[12px] text-secondary font-body-md mt-1">Inclusive of VAT</p>
          </div>
          <a href="{{ route('checkout') }}" class="w-full bg-primary text-on-primary font-headline-sm text-headline-sm py-5 hover:bg-primary-container transition-all flex items-center justify-center gap-3">
            Proceed to Checkout
            <span class="material-symbols-outlined">arrow_forward</span>
          </a>
          <div class="mt-8 flex flex-col gap-4">
            <div class="flex items-center gap-3 text-secondary text-[13px]">
              <span class="material-symbols-outlined text-primary">verified_user</span>
              <p>Encrypted SSL Secure Checkout</p>
            </div>
            <div class="flex items-center gap-3 text-secondary text-[13px]">
              <span class="material-symbols-outlined text-primary">local_shipping</span>
              <p>Temperature-Controlled Delivery</p>
            </div>
          </div>
        </div>
      </aside>
    </div>
  @endif
</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.qty-box, [data-id]').forEach(function (box) {
    const minusBtn = box.querySelector('.qty-minus');
    const plusBtn = box.querySelector('.qty-plus');
    const input = box.querySelector('.qty-input');
    if (!minusBtn || !plusBtn || !input) return;
    const article = box.closest('article');
    const subtotalCell = article?.querySelector('.subtotal');
    const cartId = box.dataset.id;

    function updateQuantity(newQty) {
      fetch('/cart/update/' + cartId, {
        method: 'PATCH',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ quantity: newQty })
      })
      .then(async response => {
        const data = await response.json();
        if (!response.ok) throw new Error(data.error || 'Unable to update cart quantity.');
        return data;
      })
      .then(data => {
        input.value = data.quantity;
        input.defaultValue = data.quantity;
        if (subtotalCell) subtotalCell.textContent = '\u20b1' + data.subtotal;
        document.querySelector('.cart-subtotal').textContent = '\u20b1' + data.cartTotal;
        document.querySelector('.cart-grand-total').textContent = '\u20b1' + data.grandTotal;
      })
      .catch(error => {
        alert(error.message);
        input.value = input.defaultValue;
      });
    }

    minusBtn.addEventListener('click', function () {
      const qty = parseInt(input.value) || 1;
      if (qty > 1) updateQuantity(qty - 1);
    });

    plusBtn.addEventListener('click', function () {
      updateQuantity((parseInt(input.value) || 1) + 1);
    });

    input.addEventListener('change', function () {
      updateQuantity(Math.max(1, parseInt(input.value) || 1));
    });
  });
});

function changeCartVariant(btn) {
  var selector = btn.closest('.cart-variant-selector');
  var cartId = selector.dataset.cartId;
  var variantId = btn.dataset.variantId;

  selector.querySelectorAll('.cart-variant-btn').forEach(function(b) {
    b.classList.remove('border-primary', 'bg-primary', 'text-white');
    b.classList.add('border-outline-variant', 'text-on-surface');
  });
  btn.classList.remove('border-outline-variant', 'text-on-surface');
  btn.classList.add('border-primary', 'bg-primary', 'text-white');

  var article = btn.closest('article');
  var subtotalEl = article.querySelector('.subtotal');
  var unitPriceEls = article.querySelectorAll('.cart-item-unitprice, .cart-item-unitprice-bottom');
  var origSubtotalHTML = subtotalEl.textContent;

  fetch('/cart/' + cartId + '/variant', {
    method: 'PATCH',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': '{{ csrf_token() }}',
      'Accept': 'application/json',
      'X-Requested-With': 'XMLHttpRequest'
    },
    body: JSON.stringify({ variant_id: variantId })
  })
  .then(function(r) { return r.json().then(function(d) { return { ok: r.ok, data: d }; }); })
  .then(function(result) {
    if (!result.ok) {
      alert(result.data.error || 'Unable to change variant.');
      btn.classList.remove('border-primary', 'bg-primary', 'text-white');
      btn.classList.add('border-outline-variant', 'text-on-surface');
      var prevBtn = selector.querySelector('.cart-variant-btn[data-variant-id]');
      return;
    }
    var d = result.data;
    subtotalEl.textContent = '\u20b1' + d.subtotal;
    unitPriceEls.forEach(function(el) {
      el.textContent = '\u20b1' + d.price + ' / ' + d.weight;
    });

    document.querySelector('.cart-subtotal').textContent = '\u20b1' + d.cartTotal;
    document.querySelector('.cart-grand-total').textContent = '\u20b1' + d.grandTotal;

    if (d.cartItemId != cartId) {
      article.dataset.cartId = d.cartItemId;
      selector.dataset.cartId = d.cartItemId;
      article.querySelector('[data-id]').dataset.id = d.cartItemId;
      var qtyInput = article.querySelector('.qty-input');
      qtyInput.value = d.quantity;
      qtyInput.defaultValue = d.quantity;
    }
  })
  .catch(function() {
    btn.classList.remove('border-primary', 'bg-primary', 'text-white');
    btn.classList.add('border-outline-variant', 'text-on-surface');
  });
}
</script>

@endsection
