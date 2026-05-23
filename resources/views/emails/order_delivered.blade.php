<h2 style="color:#D2042D;">Your Order Has Been Delivered</h2>

<p>Hi {{ $order->name ?? 'Customer' }},</p>

<p>Your order <strong>#{{ $order->id }}</strong> has been successfully delivered!</p>

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
                ₱{{ number_format($item->product->product_price * $item->quantity, 2) }}
            </td> 
        </tr>
        @endforeach
    </tbody>
</table>

<br>

@php
    $delivery = 150;
@endphp

<table width="100%" cellpadding="5">
    <tr>
        <td>Subtotal:</td>
        <td align="right">₱{{ number_format($order->total, 2) }}</td>
    </tr>
    <tr>
        <td>Delivery Fee:</td>
        <td align="right">₱{{ number_format($delivery, 2) }}</td>
    </tr>
    <tr>
        <td><strong>Total:</strong></td>
        <td align="right" style="color:#D2042D;">
            <strong>₱{{ number_format($order->total + $delivery, 2) }}</strong>
        </td>
    </tr>
</table> 

<hr>

<p><strong>Delivery Address:</strong></p>
<p>{{ $order->address }}</p>

<br>

<p>We hope you enjoy your fresh meat!</p>

<p>Thank you for choosing <strong style="color:#D2042D;">The Daily Cuts by GD</strong></p>
