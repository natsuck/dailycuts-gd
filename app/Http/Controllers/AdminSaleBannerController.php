<?php

namespace App\Http\Controllers;

use App\Models\SaleBanner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class AdminSaleBannerController extends Controller
{
    public function index()
    {
        $banners = SaleBanner::orderBy('sort_order')
            ->orderByDesc('created_at')
            ->paginate(10);

        return view('admin.sale-banners.index', compact('banners'));
    }

    public function create()
    {
        return view('admin.sale-banners.create', [
            'banner' => new SaleBanner([
                'background_color' => '#7a1118',
                'text_color' => '#ffffff',
                'is_active' => true,
                'sort_order' => 0,
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateBanner($request);
        $validated['is_active'] = $request->boolean('is_active');
        $validated['sort_order'] = (int) ($validated['sort_order'] ?? 0);

        if ($request->hasFile('image')) {
            $validated['image_path'] = $this->storeImage($request);
        }

        SaleBanner::create($validated);

        return redirect()
            ->route('admin.sale-banners.index')
            ->with('success', 'Sale banner created successfully.');
    }

    public function edit(SaleBanner $saleBanner)
    {
        return view('admin.sale-banners.edit', [
            'banner' => $saleBanner,
        ]);
    }

    public function update(Request $request, SaleBanner $saleBanner)
    {
        $validated = $this->validateBanner($request);
        $validated['is_active'] = $request->boolean('is_active');
        $validated['sort_order'] = (int) ($validated['sort_order'] ?? 0);

        if ($request->hasFile('image')) {
            $this->deleteImage($saleBanner);
            $validated['image_path'] = $this->storeImage($request);
        }

        $saleBanner->update($validated);

        return redirect()
            ->route('admin.sale-banners.index')
            ->with('success', 'Sale banner updated successfully.');
    }

    public function destroy(SaleBanner $saleBanner)
    {
        $this->deleteImage($saleBanner);
        $saleBanner->delete();

        return redirect()
            ->route('admin.sale-banners.index')
            ->with('success', 'Sale banner deleted successfully.');
    }

    protected function validateBanner(Request $request): array
    {
        return $request->validate([
            'badge_text' => ['nullable', 'string', 'max:60'],
            'title' => ['required', 'string', 'max:120'],
            'subtitle' => ['nullable', 'string', 'max:500'],
            'button_text' => ['nullable', 'string', 'max:40'],
            'button_url' => ['nullable', 'string', 'max:255'],
            'background_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'text_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);
    }

    protected function storeImage(Request $request): string
    {
        $image = $request->file('image');
        $imageName = uniqid('sale_banner_', true).'.'.$image->getClientOriginalExtension();
        File::ensureDirectoryExists(storage_path('app/public/sale-banners'));
        $image->move(storage_path('app/public/sale-banners'), $imageName);

        return 'storage/sale-banners/'.$imageName;
    }

    protected function deleteImage(SaleBanner $banner): void
    {
        if (! $banner->image_path) {
            return;
        }

        $imagePath = public_path($banner->image_path);

        if (is_file($imagePath)) {
            unlink($imagePath);
        }
    }
}
