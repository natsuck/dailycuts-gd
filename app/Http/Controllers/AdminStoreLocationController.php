<?php

namespace App\Http\Controllers;

use App\Models\StoreLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminStoreLocationController extends Controller
{
    public function index()
    {
        $locations = StoreLocation::orderBy('sort_order')
            ->orderByDesc('created_at')
            ->paginate(10);

        return view('admin.store-locations.index', compact('locations'));
    }

    public function create()
    {
        return view('admin.store-locations.create', [
            'location' => new StoreLocation([
                'is_active' => true,
                'sort_order' => (int) StoreLocation::max('sort_order') + 1,
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateLocation($request);
        $validated['is_active'] = $request->boolean('is_active');
        $validated['is_pickup'] = $request->boolean('is_pickup');

        DB::transaction(function () use ($validated) {
            if ($validated['is_pickup']) {
                StoreLocation::query()->update(['is_pickup' => false]);
            }

            StoreLocation::create($validated);
        });

        return redirect()
            ->route('admin.store-locations.index')
            ->with('success', 'Store location created successfully.');
    }

    public function edit(StoreLocation $storeLocation)
    {
        return view('admin.store-locations.edit', [
            'location' => $storeLocation,
        ]);
    }

    public function update(Request $request, StoreLocation $storeLocation)
    {
        $validated = $this->validateLocation($request);
        $validated['is_active'] = $request->boolean('is_active');
        $validated['is_pickup'] = $request->boolean('is_pickup');

        DB::transaction(function () use ($validated, $storeLocation) {
            if ($validated['is_pickup']) {
                StoreLocation::where('id', '!=', $storeLocation->id)->update(['is_pickup' => false]);
            }

            $storeLocation->update($validated);
        });

        return redirect()
            ->route('admin.store-locations.index')
            ->with('success', 'Store location updated successfully.');
    }

    public function destroy(StoreLocation $storeLocation)
    {
        $wasPickup = $storeLocation->is_pickup;

        DB::transaction(function () use ($storeLocation, $wasPickup) {
            $storeLocation->delete();

            if ($wasPickup) {
                StoreLocation::promoteNextPickup();
            }
        });

        return redirect()
            ->route('admin.store-locations.index')
            ->with('success', 'Store location deleted successfully.');
    }

    protected function validateLocation(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'lat' => ['required', 'numeric', 'between:-10,21'],
            'lng' => ['required', 'numeric', 'between:116,127'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);
    }
}
