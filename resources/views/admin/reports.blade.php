@extends('admin.maindesign')

@section('reports')
<div class="p-6">
    <h1 class="text-2xl font-bold mb-6">Reports</h1>

    {{-- Date Range Filter --}}
    <form method="GET" action="{{ route('admin.reports') }}" class="flex items-end gap-4 mb-6 bg-white p-4 rounded shadow">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">From</label>
            <input type="date" name="from" value="{{ request('from') }}" class="border rounded px-3 py-2">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">To</label>
            <input type="date" name="to" value="{{ request('to') }}" class="border rounded px-3 py-2">
        </div>
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Filter</button>
    </form>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white p-6 rounded shadow">
            <h3 class="text-sm font-medium text-gray-500">Total Revenue</h3>
            <p class="text-2xl font-bold mt-1">&#8369;{{ number_format($totalRevenue ?? 0, 2) }}</p>
        </div>
        <div class="bg-white p-6 rounded shadow">
            <h3 class="text-sm font-medium text-gray-500">Total Orders</h3>
            <p class="text-2xl font-bold mt-1">{{ $totalOrders ?? 0 }}</p>
        </div>
        <div class="bg-white p-6 rounded shadow">
            <h3 class="text-sm font-medium text-gray-500">Average Order Value</h3>
            <p class="text-2xl font-bold mt-1">&#8369;{{ number_format($avgOrderValue ?? 0, 2) }}</p>
        </div>
    </div>

    {{-- Top 5 Products --}}
    <div class="bg-white p-6 rounded shadow mb-8">
        <h2 class="text-lg font-semibold mb-4">Top 5 Products</h2>
        <table class="w-full text-left">
            <thead>
                <tr class="border-b">
                    <th class="pb-2">Product Name</th>
                    <th class="pb-2">Total Quantity</th>
                    <th class="pb-2">Total Revenue</th>
                </tr>
            </thead>
            <tbody>
                @forelse($topProducts ?? [] as $product)
                <tr class="border-b">
                    <td class="py-2">{{ $product->name }}</td>
                    <td class="py-2">{{ $product->total_quantity }}</td>
                    <td class="py-2">&#8369;{{ number_format($product->total_revenue, 2) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="3" class="py-4 text-center text-gray-500">No data available.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Daily Sales --}}
    <div class="bg-white p-6 rounded shadow mb-8">
        <h2 class="text-lg font-semibold mb-4">Daily Sales</h2>
        <table class="w-full text-left">
            <thead>
                <tr class="border-b">
                    <th class="pb-2">Date</th>
                    <th class="pb-2">Revenue</th>
                    <th class="pb-2">Orders</th>
                </tr>
            </thead>
            <tbody>
                @forelse($dailySales ?? [] as $day)
                <tr class="border-b">
                    <td class="py-2">{{ $day->date }}</td>
                    <td class="py-2">&#8369;{{ number_format($day->revenue, 2) }}</td>
                    <td class="py-2">{{ $day->orders }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="3" class="py-4 text-center text-gray-500">No data available.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Export CSV --}}
    <a href="{{ route('admin.reports.export') }}" class="inline-block bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">Export CSV</a>
</div>
@endsection
