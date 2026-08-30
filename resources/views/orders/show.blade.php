@extends('maindesign')

@section('orders')

<main class="max-w-container-max mx-auto px-4 md:px-margin-desktop py-8 md:py-12">
  <div class="max-w-3xl mx-auto">

    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-sm font-body-md text-on-surface-variant mb-8">
      <a href="{{ route('orders.index') }}" class="hover:text-primary transition-colors">My Orders</a>
      <span class="material-symbols-outlined text-[16px]">chevron_right</span>
      <span class="text-on-surface font-bold">Order #{{ $order->id }}</span>
    </nav>

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
      <div>
        <h1 class="font-headline-md text-headline-md text-on-surface mb-1">Order #{{ $order->id }}</h1>
        <p class="font-body-md text-on-surface-variant">Placed on {{ $order->created_at->format('M d, Y \a\t g:i A') }}</p>
      </div>
      <div class="text-right">
        @php
          $statusColors = [
            'pending' => 'bg-yellow-100 text-yellow-800 border-yellow-300',
            'processing' => 'bg-blue-100 text-blue-800 border-blue-300',
            'shipped' => 'bg-purple-100 text-purple-800 border-purple-300',
            'delivered' => 'bg-green-100 text-green-800 border-green-300',
            'cancelled' => 'bg-red-100 text-red-800 border-red-300',
            'returned' => 'bg-orange-100 text-orange-800 border-orange-300',
            'failed' => 'bg-red-100 text-red-800 border-red-300',
          ];
        @endphp
        <span class="inline-block px-3 py-1 rounded border text-sm font-bold uppercase {{ $statusColors[$order->status] ?? 'bg-gray-100 text-gray-800 border-gray-300' }}">
          {{ $order->status }}
        </span>
      </div>
    </div>

    {{-- Tracking Steps --}}
    @if(!in_array($order->status, ['cancelled', 'returned', 'failed']))
      <div class="bg-surface-container-lowest border border-outline-variant rounded-lg p-6 mb-8">
        <h2 class="font-headline-sm text-headline-sm text-on-surface mb-6">Order Tracking</h2>
        <div class="flex items-center justify-between relative">
          @php
            $trackingSteps = [
              'pending' => ['icon' => 'receipt', 'label' => 'Order Placed'],
              'processing' => ['icon' => 'inventory_2', 'label' => 'Processing'],
              'shipped' => ['icon' => 'local_shipping', 'label' => 'Shipped'],
              'delivered' => ['icon' => 'check_circle', 'label' => 'Delivered'],
            ];
            $stepKeys = array_keys($trackingSteps);
            $currentIdx = array_search($order->status, $stepKeys);
            if ($currentIdx === false) $currentIdx = 0;
          @endphp
          {{-- Connector line --}}
          <div class="absolute top-5 left-0 right-0 h-0.5 bg-outline-variant"></div>
          <div class="absolute top-5 left-0 h-0.5 bg-primary transition-all duration-500"
               style="width: {{ $currentIdx >= 0 ? ($currentIdx / (count($trackingSteps) - 1)) * 100 : 0 }}%"></div>

          @foreach($trackingSteps as $i => $step)
            <div class="relative z-10 flex flex-col items-center text-center" style="flex: 1;">
              <div class="w-10 h-10 rounded-full flex items-center justify-center mb-2 transition-all
                {{ $i <= $currentIdx ? 'bg-primary text-white' : 'bg-surface-container-high text-on-surface-variant border-2 border-outline-variant' }}">
                <span class="material-symbols-outlined text-[20px]">{{ $step['icon'] }}</span>
              </div>
              <span class="text-xs font-bold uppercase tracking-wider {{ $i <= $currentIdx ? 'text-primary' : 'text-on-surface-variant' }}">
                {{ $step['label'] }}
              </span>
            </div>
          @endforeach
        </div>
      </div>
    @else
      <div class="bg-red-50 border border-red-200 rounded-lg p-6 mb-8 flex items-center gap-4">
        <span class="material-symbols-outlined text-red-500 text-4xl">cancel</span>
        <div>
          <h3 class="font-headline-sm text-headline-sm text-red-800">Order {{ ucfirst($order->status) }}</h3>
          <p class="font-body-md text-red-600">This order has been {{ $order->status }}.</p>
        </div>
      </div>
    @endif

    {{-- Live Lalamove Courier Tracking --}}
    @if($order->lalamove_order_id)
      @php
        $courierKey = $order->courierStatusKey();
        $courierBadgeColors = [
          'COMPLETED' => 'bg-green-100 text-green-800 border-green-300',
          'CANCELED' => 'bg-red-100 text-red-800 border-red-300',
          'CANCELLED' => 'bg-red-100 text-red-800 border-red-300',
          'EXPIRED' => 'bg-red-100 text-red-800 border-red-300',
          'REJECTED' => 'bg-red-100 text-red-800 border-red-300',
          'FAILED' => 'bg-red-100 text-red-800 border-red-300',
          'ASSIGNING_DRIVER' => 'bg-yellow-100 text-yellow-800 border-yellow-300',
        ];
        $courierBadgeClass = $courierBadgeColors[$courierKey] ?? 'bg-blue-100 text-blue-800 border-blue-300';
      @endphp
      <div class="bg-surface-container-lowest border border-outline-variant rounded-lg p-6 mb-8">
        <div class="flex items-center justify-between mb-4">
          <h2 class="font-headline-sm text-headline-sm text-on-surface">Courier Tracking</h2>
          <button type="button" id="courier-refresh" class="flex items-center gap-1 text-sm font-semibold text-primary hover:opacity-80 transition-all">
            <span class="material-symbols-outlined text-[18px]">refresh</span>
            Refresh
          </button>
        </div>
        <div class="flex items-center gap-3 mb-4">
          <span id="courier-badge" class="inline-block px-3 py-1 rounded border text-sm font-bold uppercase {{ $courierBadgeClass }}">
            {{ $order->courierStatusShort() }}
          </span>
        </div>
        <p id="courier-driver" class="font-body-md text-on-surface-variant mb-4">
          {{ $order->courierStatusText() }}
          @if($order->lalamove_driver_name)
            Courier: {{ $order->lalamove_driver_name }}{{ $order->lalamove_driver_phone ? ' ('.$order->lalamove_driver_phone.')' : '' }}.
          @endif
        </p>
        <a id="courier-track-link" href="{{ $order->tracking_url ?: '#' }}"
           target="_blank" rel="noopener noreferrer"
           class="inline-flex items-center gap-2 bg-primary text-white font-bold px-5 py-3 rounded-lg hover:opacity-90 transition-all {{ $order->tracking_url ? '' : 'pointer-events-none opacity-40' }}">
          <span class="material-symbols-outlined text-[20px]">navigation</span>
          Track on Lalamove
        </a>
      </div>
    @endif

    {{-- Order Items --}}
    <div class="bg-surface-container-lowest border border-outline-variant rounded-lg p-6 mb-8">
      <h2 class="font-headline-sm text-headline-sm text-on-surface mb-6">Order Items</h2>
      <div class="space-y-4">
        @foreach($order->items as $item)
          <div class="pb-4 {{ !$loop->last ? 'border-b border-outline-variant' : '' }}">
            <div class="flex items-center gap-4">
              @if($item->product)
                <div class="w-16 h-16 bg-surface-container-high rounded overflow-hidden flex-shrink-0">
                  <img class="w-full h-full object-cover" src="{{ asset('products/'.$item->product->product_image) }}" alt="{{ $item->product->product_title }}">
                </div>
                <div class="flex-1 min-w-0">
                  <a href="{{ route('product_details', $item->product->id) }}" class="font-headline-sm text-headline-sm text-on-surface truncate hover:text-primary transition-colors block">{{ $item->product->product_title }}</a>
                  <p class="text-on-surface-variant text-sm font-body-md">Qty: {{ $item->quantity }} &times; &#8369;{{ number_format($item->price, 2) }}</p>
                </div>
              @else
                <div class="w-16 h-16 bg-surface-container-high rounded flex items-center justify-center flex-shrink-0">
                  <span class="material-symbols-outlined text-on-surface-variant">inventory</span>
                </div>
                <div class="flex-1 min-w-0">
                  <h4 class="font-headline-sm text-headline-sm text-on-surface truncate">Product no longer available</h4>
                  <p class="text-on-surface-variant text-sm font-body-md">Qty: {{ $item->quantity }} &times; &#8369;{{ number_format($item->price, 2) }}</p>
                </div>
              @endif
              <span class="font-price-display text-price-display text-primary flex-shrink-0">&#8369;{{ number_format($item->price * $item->quantity, 2) }}</span>
            </div>
            @if($order->status === 'delivered' && $item->product)
              <div class="mt-3 ml-20">
                @if(isset($existingReviews[$item->product_id]))
                  @php $review = $existingReviews[$item->product_id]; @endphp
                  <div class="flex items-center gap-2">
                    <div class="flex text-tertiary">
                      @for($i = 1; $i <= 5; $i++)
                        <span class="material-symbols-outlined text-[14px]" style="font-variation-settings: 'FILL' {{ $i <= $review->rating ? 1 : 0 }};">star</span>
                      @endfor
                    </div>
                    <span class="text-on-surface-variant text-xs font-body-md">Reviewed</span>
                    <a href="{{ route('product_details', $item->product->id) }}" class="text-primary text-xs font-bold hover:underline">Edit Review</a>
                  </div>
                @else
                  <a href="{{ route('product_details', $item->product->id) }}#review-form" class="inline-flex items-center gap-1 text-primary text-sm font-bold hover:underline transition-colors">
                    <span class="material-symbols-outlined text-[16px]">rate_review</span>
                    Write a Review
                  </a>
                @endif
              </div>
            @endif
          </div>
        @endforeach
      </div>
    </div>

    {{-- Order Summary --}}
    <div class="bg-surface-container-lowest border border-outline-variant rounded-lg p-6 mb-8">
      <h2 class="font-headline-sm text-headline-sm text-on-surface mb-6">Order Summary</h2>
      <div class="space-y-3">
        <div class="flex justify-between font-body-md text-body-md">
          <span class="text-on-surface-variant">Subtotal</span>
          <span class="text-on-surface">&#8369;{{ number_format($order->subtotal(), 2) }}</span>
        </div>
        <div class="flex justify-between font-body-md text-body-md">
          <span class="text-on-surface-variant">Shipping Fee</span>
          <span class="text-on-surface">&#8369;{{ number_format($order->shipping_fee, 2) }}</span>
        </div>
        <div class="flex justify-between pt-3 border-t border-outline-variant">
          <span class="font-headline-sm text-headline-sm text-on-surface">Total</span>
          <span class="font-price-display text-price-display text-primary">&#8369;{{ number_format($order->total, 2) }}</span>
        </div>
      </div>
    </div>

    {{-- Delivery Info --}}
    <div class="bg-surface-container-lowest border border-outline-variant rounded-lg p-6 mb-8">
      <h2 class="font-headline-sm text-headline-sm text-on-surface mb-4">Delivery Information</h2>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 font-body-md text-body-md">
        <div>
          <p class="text-on-surface-variant mb-1">Name</p>
          <p class="text-on-surface font-bold">{{ $order->name }}</p>
        </div>
        <div>
          <p class="text-on-surface-variant mb-1">Phone</p>
          <p class="text-on-surface font-bold">{{ $order->phone }}</p>
        </div>
        <div class="sm:col-span-2">
          <p class="text-on-surface-variant mb-1">Address</p>
          <p class="text-on-surface font-bold">{{ $order->address }}</p>
        </div>
        @if($order->notes)
          <div class="sm:col-span-2">
            <p class="text-on-surface-variant mb-1">Notes</p>
            <p class="text-on-surface">{{ $order->notes }}</p>
          </div>
        @endif
        <div>
          <p class="text-on-surface-variant mb-1">Payment Method</p>
          <p class="text-on-surface font-bold uppercase">{{ $order->payment_method ?? 'N/A' }}</p>
        </div>
        <div>
          <p class="text-on-surface-variant mb-1">Payment Status</p>
          <span class="inline-block px-2 py-0.5 rounded text-xs font-bold uppercase {{ $order->payment_status === 'paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
            {{ $order->payment_status ?? 'unpaid' }}
          </span>
        </div>
      </div>
    </div>

    <div class="text-center">
      <a href="{{ route('orders.index') }}" class="inline-flex items-center gap-2 text-primary font-bold hover:underline underline-offset-4 transition-all">
        <span class="material-symbols-outlined text-[20px]">arrow_back</span>
        Back to My Orders
      </a>
    </div>

  </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
  var badge = document.getElementById('courier-badge');
  var driver = document.getElementById('courier-driver');
  var trackLink = document.getElementById('courier-track-link');
  var refreshBtn = document.getElementById('courier-refresh');

  var courierMap = @json(\App\Models\Order::courierStatusMap());

  function badgeClass(status) {
    var base = 'inline-block px-3 py-1 rounded border text-sm font-bold uppercase ';
    if (status === 'COMPLETED') {
      return base + 'bg-green-100 text-green-800 border-green-300';
    }
    if (['CANCELED', 'CANCELLED', 'EXPIRED', 'REJECTED', 'FAILED'].indexOf(status) !== -1) {
      return base + 'bg-red-100 text-red-800 border-red-300';
    }
    return base + 'bg-blue-100 text-blue-800 border-blue-300';
  }

  function render(data) {
    var status = (data.lalamove_status || data.status || 'PENDING').toUpperCase();
    var entry = courierMap[status] || null;
    if (badge) {
      badge.textContent = entry ? entry.short : status;
      badge.className = badgeClass(status);
    }
    if (driver) {
      driver.textContent = entry ? entry.text : 'Courier status: ' + status + '.';
      if (data.driver && data.driver.name) {
        driver.textContent += ' Courier: ' + data.driver.name + (data.driver.phone ? ' (' + data.driver.phone + ')' : '') + '.';
      }
    }
    if (trackLink && data.tracking_url) {
      trackLink.href = data.tracking_url;
      trackLink.classList.remove('pointer-events-none', 'opacity-40');
    }
  }

  function loadCourierStatus() {
    if (!badge) {
      return;
    }
    fetch('{{ route("orders.lalamoveStatus", $order->id) }}', {
      headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    })
      .then(function(r) { return r.json(); })
      .then(function(data) { if (data.available) { render(data); } })
      .catch(function() {});
  }

  if (refreshBtn) {
    refreshBtn.addEventListener('click', loadCourierStatus);
  }

  loadCourierStatus();
  setInterval(loadCourierStatus, 15000);
});
</script>

@endsection
