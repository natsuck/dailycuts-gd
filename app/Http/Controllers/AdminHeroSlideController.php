<?php

namespace App\Http\Controllers;

use App\Models\HeroSlide;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class AdminHeroSlideController extends Controller
{
    public function index()
    {
        $slides = HeroSlide::orderBy('sort_order')
            ->orderByDesc('created_at')
            ->paginate(10);

        return view('admin.hero-slides.index', compact('slides'));
    }

    public function create()
    {
        return view('admin.hero-slides.create', [
            'slide' => new HeroSlide([
                'is_active' => true,
                'sort_order' => (int) HeroSlide::max('sort_order') + 1,
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateSlide($request, false);
        $validated['is_active'] = $request->boolean('is_active');
        $validated['sort_order'] = (int) ($validated['sort_order'] ?? 0);

        if ($request->hasFile('image')) {
            $validated['image_path'] = $this->storeImage($request, 'image');
        }

        if ($request->hasFile('mobile_image')) {
            $validated['mobile_image_path'] = $this->storeImage($request, 'mobile_image', 'mobile');
        }

        HeroSlide::create($validated);

        return redirect()
            ->route('admin.hero-slides.index')
            ->with('success', 'Hero slide created successfully.');
    }

    public function edit(HeroSlide $heroSlide)
    {
        return view('admin.hero-slides.edit', [
            'slide' => $heroSlide,
        ]);
    }

    public function update(Request $request, HeroSlide $heroSlide)
    {
        $validated = $this->validateSlide($request, true);
        $validated['is_active'] = $request->boolean('is_active');
        $validated['sort_order'] = (int) ($validated['sort_order'] ?? 0);

        if ($request->hasFile('image')) {
            $this->deleteImage($heroSlide->image_path);
            $validated['image_path'] = $this->storeImage($request, 'image');
        }

        if ($request->hasFile('mobile_image')) {
            $this->deleteImage($heroSlide->mobile_image_path);
            $validated['mobile_image_path'] = $this->storeImage($request, 'mobile_image', 'mobile');
        }

        $heroSlide->update($validated);

        return redirect()
            ->route('admin.hero-slides.index')
            ->with('success', 'Hero slide updated successfully.');
    }

    public function destroy(HeroSlide $heroSlide)
    {
        $this->deleteImage($heroSlide->image_path);
        $this->deleteImage($heroSlide->mobile_image_path);
        $heroSlide->delete();

        return redirect()
            ->route('admin.hero-slides.index')
            ->with('success', 'Hero slide deleted successfully.');
    }

    protected function validateSlide(Request $request, bool $forUpdate = false): array
    {
        $validated = $request->validate([
            'tag' => ['nullable', 'string', 'max:60'],
            'heading' => ['nullable', 'string', 'max:120'],
            'subheading' => ['nullable', 'string', 'max:500'],
            'cta_text' => ['nullable', 'string', 'max:40'],
            'cta_link' => ['nullable', 'url', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'mobile_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        $validated['heading'] = $validated['heading'] ?? '';
        $validated['subheading'] = $validated['subheading'] ?? '';

        return $validated;
    }

    protected function storeImage(Request $request, string $field = 'image', string $subdir = ''): string
    {
        $image = $request->file($field);
        $imageName = uniqid('hero_slide_', true).'.'.$image->guessExtension();
        $directory = storage_path('app/public/hero-slides/'.($subdir ? $subdir.'/' : ''));
        File::ensureDirectoryExists($directory);
        $image->move($directory, $imageName);

        return 'storage/hero-slides/'.($subdir ? $subdir.'/' : '').$imageName;
    }

    protected function deleteImage(?string $path): void
    {
        if (! $path || Str::startsWith($path, 'frontend/')) {
            return;
        }

        $imagePath = storage_path('app/public/'.Str::after($path, 'storage/'));

        if (is_file($imagePath)) {
            unlink($imagePath);
        }
    }
}
