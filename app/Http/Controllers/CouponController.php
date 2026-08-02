<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    public function index()
    {
        $coupons = Coupon::latest()->paginate(15);

        return view('admin.coupons', compact('coupons'));
    }

    public function create()
    {
        return view('admin.addcoupon');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:coupons,code',
            'type' => 'required|in:percentage,fixed,free_shipping',
            'value' => ['required', 'numeric', 'min:0', 'max:1000000', function ($attribute, $value, $fail) use ($request) {
                if ($request->input('type') === 'percentage' && $value > 100) {
                    $fail('The value may not be greater than 100 for percentage coupons.');
                }
            }],
            'min_order' => 'nullable|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'is_active' => 'nullable|boolean',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after_or_equal:starts_at',
        ]);

        $validated['code'] = strtoupper($validated['code']);
        $validated['min_order'] = $validated['min_order'] ?? 0;
        $this->normalizeFreeShipping($validated);

        Coupon::create($validated);

        return redirect()->route('admin.coupons.index')->with('success', 'Coupon created successfully.');
    }

    public function show($id)
    {
        $coupon = Coupon::findOrFail($id);

        return redirect()->route('admin.coupons.edit', $coupon->id);
    }

    public function edit($id)
    {
        $coupon = Coupon::findOrFail($id);

        return view('admin.editcoupon', compact('coupon'));
    }

    public function update(Request $request, $id)
    {
        $coupon = Coupon::findOrFail($id);

        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:coupons,code,'.$id,
            'type' => 'required|in:percentage,fixed,free_shipping',
            'value' => ['required', 'numeric', 'min:0', 'max:1000000', function ($attribute, $value, $fail) use ($request) {
                if ($request->input('type') === 'percentage' && $value > 100) {
                    $fail('The value may not be greater than 100 for percentage coupons.');
                }
            }],
            'min_order' => 'nullable|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'is_active' => 'nullable|boolean',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after_or_equal:starts_at',
        ]);

        $validated['code'] = strtoupper($validated['code']);
        $validated['min_order'] = $validated['min_order'] ?? 0;
        $this->normalizeFreeShipping($validated);

        $coupon->update($validated);

        return redirect()->route('admin.coupons.index')->with('success', 'Coupon updated successfully.');
    }

    public function destroy($id)
    {
        $coupon = Coupon::findOrFail($id);
        $coupon->delete();

        return redirect()->back()->with('success', 'Coupon deleted successfully.');
    }

    protected function normalizeFreeShipping(array &$validated): void
    {
        if (($validated['type'] ?? null) === 'free_shipping') {
            $validated['value'] = 0;
            $validated['max_discount'] = null;
        }
    }
}
