@extends('admin.maindesign')

@section('inventory_history')

<div style="margin-bottom: 15px;">
    <a href="{{ route('admin.inventory.index') }}" style="color: #0d6efd; text-decoration: none;">&larr; Back to Inventory</a>
</div>

<h3 style="margin-bottom: 20px;">{{ $product->product_title }}</h3>

<table style="width: 100%; border-collapse: collapse; font-family: Arial, sans-serif;">
    <thead>
    <tr style="background-color: #f2f2f2;">
        <th class="px-3 py-2 text-start border-bottom">Date</th>
        <th class="px-3 py-2 text-start border-bottom">Type</th>
        <th class="px-3 py-2 text-start border-bottom">Change</th>
        <th class="px-3 py-2 text-start border-bottom">Before</th>
        <th class="px-3 py-2 text-start border-bottom">After</th>
        <th class="px-3 py-2 text-start border-bottom">Notes</th>
    </tr>
    </thead>
    <tbody>
    @foreach($history as $record)
    <tr style="border-bottom: 1px solid #ddd;">
        <td style="padding: 12px;">{{ $record->created_at->format('M d, Y h:i A') }}</td>
        <td style="padding: 12px;">
            @if($record->change_type === 'add')
                <span style="color: green;">Added</span>
            @elseif($record->change_type === 'remove')
                <span style="color: red;">Removed</span>
            @else
                <span>{{ ucfirst($record->change_type) }}</span>
            @endif
        </td>
        <td style="padding: 12px;">
            @if($record->change_type === 'add')
                +{{ $record->quantity }}
            @else
                -{{ $record->quantity }}
            @endif
        </td>
        <td style="padding: 12px;">{{ $record->quantity_before }}</td>
        <td style="padding: 12px;">{{ $record->quantity_after }}</td>
        <td style="padding: 12px;">{{ $record->notes ?? '-' }}</td>
    </tr>
    @endforeach
    </tbody>
</table>

<div class="d-flex justify-content-center mt-3">
    {{ $history->links() }}
</div>

@endsection
