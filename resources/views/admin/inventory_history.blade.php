@extends('admin.maindesign')

@section('page_title', 'Inventory History')
@section('page_header', 'Inventory History')
@section('page_subtitle', 'Detailed change log for a single product.')

@push('styles')
<style>
    .history-dashboard .block {
        border-radius: 8px;
    }

    .history-table {
        margin-bottom: 0;
    }

    .history-table th {
        border-top: 0;
        color: #adb5bd;
        font-size: 0.76rem;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .history-table td {
        vertical-align: middle;
    }

    .history-muted {
        color: #adb5bd;
        font-size: 0.82rem;
    }

    .history-badge {
        border-radius: 999px;
        display: inline-block;
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.03em;
        line-height: 1;
        padding: 0.45rem 0.65rem;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .history-badge-restock {
        background: #28a745;
        color: #fff;
    }

    .history-badge-sale {
        background: #dc3545;
        color: #fff;
    }

    .history-badge-adjustment {
        background: #6c757d;
        color: #fff;
    }

    .history-change-restock {
        color: #28a745;
        font-weight: 700;
    }

    .history-change-sale {
        color: #dc3545;
        font-weight: 700;
    }
</style>
@endpush

@section('inventory_history')
<div class="container-fluid history-dashboard">
    <div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
        <div class="mb-3 mb-md-0">
            <h3 class="mb-1">{{ $product->product_title }}</h3>
            <p class="text-muted mb-0">Current stock: <strong>{{ number_format($product->product_quantity) }}</strong></p>
        </div>

        <a href="{{ route('admin.inventory.index') }}" class="btn btn-outline-primary btn-sm">
            <i class="fa fa-arrow-left mr-1"></i> Back to Inventory
        </a>
    </div>

    <div class="block">
        <div class="title d-flex flex-wrap align-items-center justify-content-between mb-3">
            <div>
                <strong>Change Log</strong>
                <p class="text-muted mb-0">Most recent activity first</p>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover history-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th class="text-right">Change</th>
                        <th class="text-right">Before</th>
                        <th class="text-right">After</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($history as $record)
                        @php
                            $change = match ($record->type) {
                                'restock' => '+'.$record->quantity_change,
                                'sale' => '-'.$record->quantity_change,
                                'adjustment' => '→ '.$record->quantity_after,
                                default => (string) $record->quantity_change,
                            };
                            $changeClass = match ($record->type) {
                                'restock' => 'history-change-restock',
                                'sale' => 'history-change-sale',
                                default => '',
                            };
                            $typeLabel = match ($record->type) {
                                'restock' => 'Restock',
                                'sale' => 'Sale',
                                'adjustment' => 'Adjusted',
                                default => ucfirst($record->type),
                            };
                        @endphp
                        <tr>
                            <td>
                                {{ $record->created_at->format('M d, Y') }}
                                <div class="history-muted">{{ $record->created_at->format('h:i A') }}</div>
                            </td>
                            <td>
                                <span class="history-badge history-badge-{{ $record->type }}">
                                    {{ $typeLabel }}
                                </span>
                            </td>
                            <td class="text-right {{ $changeClass }}">{{ $change }}</td>
                            <td class="text-right">{{ number_format($record->quantity_before) }}</td>
                            <td class="text-right">{{ number_format($record->quantity_after) }}</td>
                            <td>
                                @if($record->notes)
                                    {{ $record->notes }}
                                @else
                                    <span class="history-muted">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">No inventory changes recorded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-center mt-4">
            {{ $history->links() }}
        </div>
    </div>
</div>
@endsection
