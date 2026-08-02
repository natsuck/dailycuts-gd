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
          ];
        @endphp
        <span class="inline-block px-3 py-1 rounded border text-sm font-bold uppercase {{ $statusColors[$order->status] ?? 'bg-gray-100 text-gray-800 border-gray-300' }}">
          {{ $order->status }}
        </span>
      </div>
    </div>

    {{-- Tracking Steps --}}
    @if(!in_array($order->status, ['cancelled', 'returned']))
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

    {{-- Order Items --}}
    <div class="bg-surface-container-lowest border border-outline-variant rounded-lg p-6 mb-8">
      <h2 class="font-headline-sm text-headline-sm text-on-surface mb-6">Order Items</h2>
      <div class="space-y-4">
        @foreach($order->items as $item)
          <div class="flex items-center gap-4 pb-4 {{ !$loop->last ? 'border-b border-outline-variant' : '' }}">
            @if($item->product)
              <div class="w-16 h-16 bg-surface-container-high rounded overflow-hidden flex-shrink-0">
                <img class="w-full h-full object-cover" src="{{ asset('products/'.$item->product->product_image) }}" alt="{{ $item->product->product_title }}">
              </div>
              <div class="flex-1 min-w-0">
                <h4 class="font-headline-sm text-headline-sm text-on-surface truncate">{{ $item->product->product_title }}</h4>
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

@endsection
