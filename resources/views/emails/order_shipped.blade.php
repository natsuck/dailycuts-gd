<h2 style="color:#D2042D;">Your Order Has Been Shipped</h2>

<p>Hi {{ $order->name ?? 'Customer' }},</p>

<p>Your order <strong>#{{ $order->id }}</strong> has been shipped!</p>

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
            <td>{{ $item->product->product_title }}</td>
            <td align="center">x{{ $item->quantity }}</td>
            <td align="right">
                ₱{{ number_format($item->price * $item->quantity, 2) }}
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
        <td align="right">₱{{ number_format($subtotal, 2) }}</td>
    </tr>
    <tr>
        <td>Delivery Fee:</td>
        <td align="right">₱{{ number_format($delivery, 2) }}</td>
    </tr>
    <tr>
        <td><strong>Total:</strong></td>
        <td align="right" style="color:#D2042D;">
            <strong>₱{{ number_format($order->total, 2) }}</strong>
        </td>
    </tr>
</table> 

<hr>

<p><strong>Delivery Address:</strong></p>
<p>{{ $order->address }}</p>

<br>

@if(!empty($order->tracking_url))
    <p><strong>Track your delivery:</strong></p>
    <p>
        <a href="{{ $order->tracking_url }}" style="display:inline-block;background:#D2042D;color:#ffffff;padding:12px 24px;text-decoration:none;border-radius:6px;font-weight:bold;">Track on Lalamove</a>
    </p>
    <p style="color:#666;">If the button doesn't work, copy and paste this link into your browser:</p>
    <p>{{ $order->tracking_url }}</p>
@else
    <p>Tracking will be available once your courier has been assigned. You can also track your order anytime in your account under <strong>My Orders</strong>.</p>
@endif

<br>

<p>Thank you for ordering from <strong style="color:#D2042D;">The Daily Cuts</strong></p>