@extends('maindesign')

@section('orders')

<main class="max-w-container-max mx-auto px-4 md:px-margin-desktop py-8 md:py-12">
  <header class="mb-8 md:mb-12">
    <h1 class="font-display-lg text-display-lg-mobile md:text-display-lg text-on-surface mb-2">My Orders</h1>
    <p class="font-body-lg text-body-lg text-on-surface-variant">Track and manage your recent orders.</p>
  </header>

  @if($orders->isEmpty())
    <div class="flex flex-col items-center justify-center py-20 text-center">
      <span class="material-symbols-outlined text-6xl text-on-surface-variant mb-6">receipt_long</span>
      <h2 class="font-headline-sm text-headline-sm text-on-surface mb-2">No orders yet</h2>
      <p class="font-body-md text-body-md text-on-surface-variant mb-8">When you place an order, it will appear here.</p>
      <a href="{{ route('shop') }}" class="bg-primary text-white font-bold py-4 px-8 rounded-lg hover:opacity-90 transition-all active:scale-95">Start Shopping</a>
    </div>
  @else
    <div class="space-y-4">
      @foreach($orders as $order)
        <a href="{{ route('orders.show', $order->id) }}" class="block bg-surface-container-lowest border border-outline-variant rounded-lg p-6 hover:shadow-[0_4px_20px_rgba(0,0,0,0.05)] transition-all">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-4">
            <div>
              <div class="flex items-center gap-3 mb-1">
                <h3 class="font-headline-sm text-headline-sm text-on-surface">Order #{{ $order->id }}</h3>
                @php
                  $statusColors = [
                    'pending' => 'bg-yellow-100 text-yellow-800',
                    'processing' => 'bg-blue-100 text-blue-800',
                    'shipped' => 'bg-purple-100 text-purple-800',
                    'delivered' => 'bg-green-100 text-green-800',
                    'cancelled' => 'bg-red-100 text-red-800',
                    'returned' => 'bg-orange-100 text-orange-800',
                  ];
                @endphp
                <span class="px-2 py-0.5 rounded text-xs font-bold uppercase {{ $statusColors[$order->status] ?? 'bg-gray-100 text-gray-800' }}">
                  {{ $order->status }}
                </span>
                @if($order->payment_status === 'paid')
                  <span class="px-2 py-0.5 rounded text-xs font-bold uppercase bg-green-100 text-green-800">Paid</span>
                @endif
              </div>
              <p class="text-on-surface-variant text-sm font-body-md">{{ $order->created_at->format('M d, Y \a\t g:i A') }}</p>
            </div>
            <div class="text-right">
              <p class="font-price-display text-price-display text-primary">&#8369;{{ number_format($order->total, 2) }}</p>
              <p class="text-on-surface-variant text-sm font-body-md">{{ $order->items->sum('quantity') }} item(s)</p>
            </div>
          </div>

          <div class="flex items-center gap-3 overflow-x-auto pb-1">
            @foreach($order->items->take(4) as $item)
              @if($item->product)
                <div class="flex-shrink-0 w-12 h-12 bg-surface-container-high rounded overflow-hidden">
                  <img class="w-full h-full object-cover" src="{{ asset('products/'.$item->product->product_image) }}" alt="{{ $item->product->product_title }}">
                </div>
              @endif
            @endforeach
            @if($order->items->count() > 4)
              <span class="text-on-surface-variant text-sm font-body-md">+{{ $order->items->count() - 4 }} more</span>
            @endif
          </div>
        </a>
      @endforeach
    </div>

    <div class="mt-8">
      {{ $orders->links() }}
    </div>
  @endif
</main>

@endsection
