@extends('maindesign')
<base href="/public">

@section('viewcart')

<section class="cart_section layout_padding">
<div class="container">

        @if($cart->isEmpty())
        <div class="empty-cart text-center my-5">
            <i class="fa fa-shopping-cart fa-3x mb-3 text-muted"></i>
            <h4>Your cart is currently empty.</h4>
            <p>
                Before proceeding to checkout you must add some products to your shopping cart.<br>
                You will find a lot of interesting products on our Shop page.
            </p>
            <a href="{{ route('shop') }}" class="btn btn-cherry mt-3">Return to Shop</a>
        </div>
        @else
        <h2 class="mb-4">Your Cart</h2>
    <div class="row g-4">

        <div class="col-lg-8">

            <table class="table align-middle cart-table">
                <thead>
                    <tr>
                        <th></th>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th class="text-end">Subtotal</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($cart as $cartProduct)
                    <tr>
                        <td>
                            <form action="{{ route('cart.remove', $cartProduct->id) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <button class="remove-btn">x</button>
                            </form>
                        </td>

                        <td class="d-flex align-items-center gap-3">
                            <img src="{{ asset('products/'.$cartProduct->product->product_image) }}" class="cart-img">
                            <span>{{ $cartProduct->product->product_title }}</span>
                        </td>

                        <td>
                            &#8369;{{ number_format($cartProduct->product->product_price, 2) }}
                        </td>

                        <td>
                        <div class="qty-box" data-id="{{ $cartProduct->id }}">
                            <button type="button" class="qty-minus">-</button>
                            <input type="text" value="{{ $cartProduct->quantity ?? 1 }}" class="qty-input">
                            <button type="button" class="qty-plus">+</button>
                        </div>
                        </td>

                        <td class="text-end text-danger fw-bold subtotal">
                        &#8369;{{ number_format(($cartProduct->product->product_price) * ($cartProduct->quantity ?? 1), 2) }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

        </div>

        <div class="col-lg-4">
            <div class="cart-summary">
                <h5 class="mb-3">Cart Totals</h5>

                <div class="d-flex justify-content-between mb-2">
                    <span>Subtotal</span>
                    <span class="cart-subtotal">&#8369;{{ number_format($total, 2) }}</span>
                </div>

                <div class="d-flex justify-content-between fw-bold">
                    <span>Total</span>
                    <span class="text-danger cart-grand-total">
                        &#8369;{{ number_format($grandTotal, 2) }}
                    </span>
                </div>

                <a href="{{ route('checkout') }}" class="btn cart-checkout-btn w-100 mt-3">
                    Proceed to Checkout
                </a>
            </div>
        </div>

    </div>
     @endif
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.qty-box').forEach(function (box) {
        const minusBtn = box.querySelector('.qty-minus');
        const plusBtn = box.querySelector('.qty-plus');
        const input = box.querySelector('.qty-input');
        const subtotalCell = box.closest('tr').querySelector('.subtotal');
        const cartId = box.dataset.id;

        function updateQuantity(newQty) {
            fetch(`/cart/update/${cartId}`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ quantity: newQty })
            })
            .then(async response => {
                const data = await response.json();
                if (!response.ok) {
                    throw new Error(data.error || 'Unable to update cart quantity.');
                }
                return data;
            })
            .then(data => {
                input.value = data.quantity;
                subtotalCell.textContent = '₱' + data.subtotal;
                document.querySelector('.cart-subtotal').textContent = '₱' + data.cartTotal;
                document.querySelector('.cart-grand-total').textContent = '₱' + data.grandTotal;
            })
            .catch(error => {
                alert(error.message);
                input.value = input.defaultValue;
            });
        }

        minusBtn.addEventListener('click', function () {
            let qty = parseInt(input.value) || 1;
            if (qty > 1) {
                updateQuantity(qty - 1);
            }
        });

        plusBtn.addEventListener('click', function () {
            let qty = parseInt(input.value) || 1;
            updateQuantity(qty + 1);
        });

        input.addEventListener('change', function () {
            let qty = parseInt(input.value) || 1;
            updateQuantity(qty);
        });
    });
});
</script>


</section>

@endsection
