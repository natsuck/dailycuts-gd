<h2 style="color:#D2042D;">Thank you for your order!</h2>

<p>Hi {{ $order->name ?? 'Customer' }},</p>

<p>We've received your order <strong>#{{ $order->id }}</strong> and are getting it ready for you.</p>

<hr>

<h4>Order Summary</h4>

<table width="100%" cellpadding="8" cellspacing="0" style="border-collapse: collapse;">
    <thead>
        <tr style="background:#f5f5f5;">
            <th align="left">Product</th>
            <th align="center">Qty</th>
            <th align="right">Price</th>
        </tr>
    </thead>

    <tbody>
        @foreach($order->items as $item)
        <tr style="border-bottom:1px solid #eee;">
            <td>{{ $item->product ? $item->product->product_title : 'Product no longer available' }}</td>
            <td align="center">x{{ $item->quantity }}</td>
            <td align="right">
                &#8369;{{ number_format($item->price * $item->quantity, 2) }}
            </td>
        </tr>
        @endforeach
    </tbody>
</table>

<br>

@php
    $delivery = $order->shipping_fee ?? 0;
    $subtotal = $order->total - $delivery;
@endphp

<table width="100%" cellpadding="5">
    <tr>
        <td>Subtotal:</td>
        <td align="right">&#8369;{{ number_format($subtotal, 2) }}</td>
    </tr>
    <tr>
        <td>Delivery Fee:</td>
        <td align="right">&#8369;{{ number_format($delivery, 2) }}</td>
    </tr>
    @if((float) $order->discount > 0)
    <tr>
        <td>Discount:</td>
        <td align="right">-&#8369;{{ number_format($order->discount, 2) }}</td>
    </tr>
    @endif
    <tr>
        <td><strong>Total:</strong></td>
        <td align="right" style="color:#D2042D;">
            <strong>&#8369;{{ number_format($order->total, 2) }}</strong>
        </td>
    </tr>
</table>

<hr>

<p><strong>Payment Method:</strong> {{ ucfirst($order->payment_method ?? 'N/A') }}</p>

<p><strong>Delivery Address:</strong></p>
<p>{{ $order->address }}</p>

<br>

<p>You can track the status of your order anytime in your account under <strong>My Orders</strong>.</p>

<p>Thank you for choosing <strong style="color:#D2042D;">The Daily Cuts</strong></p>
