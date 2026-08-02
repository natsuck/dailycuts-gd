@extends('admin.maindesign')

@section('coupons')

@if(session('success'))
    <div style="margin-bottom: 10px; color: black; background-color: lightgreen; padding: 10px;">
        {{ session('success') }}
    </div>
@endif

<div class="list-inline-item" style="margin-bottom: 15px;">
    <a href="{{ route('admin.coupons.create') }}" style="background-color: #0d6efd; color: #fff; padding: 8px 16px; text-decoration: none; border-radius: 4px;">Add New Coupon</a>
</div>

<table style="width: 100%; border-collapse: collapse; font-family: Arial, sans-serif;">
    <thead>
    <tr style="background-color: #f2f2f2;">
        <th class="px-3 py-2 text-start border-bottom">Code</th>
        <th class="px-3 py-2 text-start border-bottom">Type</th>
        <th class="px-3 py-2 text-start border-bottom">Value</th>
        <th class="px-3 py-2 text-start border-bottom">Min Order</th>
        <th class="px-3 py-2 text-start border-bottom">Usage</th>
        <th class="px-3 py-2 text-start border-bottom">Status</th>
        <th class="px-3 py-2 text-start border-bottom">Actions</th>
    </tr>
    </thead>
    <tbody>
    @foreach($coupons as $coupon)
    <tr style="border-bottom: 1px solid #ddd;">
        <td style="padding: 12px;">{{ $coupon->code }}</td>
        <td style="padding: 12px;">{{ match ($coupon->type) {
            'percentage' => 'Percentage',
            'fixed' => 'Fixed',
            'free_shipping' => 'Free Shipping',
            default => ucfirst($coupon->type),
        } }}</td>
        <td style="padding: 12px;">{{ $coupon->value }}</td>
        <td style="padding: 12px;">{{ $coupon->min_order }}</td>
        <td style="padding: 12px;">{{ $coupon->used_count ?? 0 }} / {{ $coupon->usage_limit ?? '∞' }}</td>
        <td style="padding: 12px;">
            @if($coupon->is_active)
                <span style="color: green;">Active</span>
            @else
                <span style="color: red;">Inactive</span>
            @endif
        </td>
        <td style="padding: 12px;">
            <a href="{{ route('admin.coupons.edit', $coupon->id) }}" style="color: green;">Edit</a>
            <form action="{{ route('admin.coupons.destroy', $coupon->id) }}" method="POST" style="display:inline;">
                @csrf
                @method('DELETE')
                <button type="submit" style="background:none;border:none;padding:0;color:#dc3545;" onclick="return confirm('Do you want to delete this coupon?')">Delete</button>
            </form>
        </td>
    </tr>
    @endforeach
    </tbody>
</table>

<div class="d-flex justify-content-center mt-3">
    {{ $coupons->links() }}
</div>

@endsection
