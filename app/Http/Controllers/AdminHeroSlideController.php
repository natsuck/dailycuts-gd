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
        $validated = $this->validateSlide($request);
        $validated['is_active'] = $request->boolean('is_active');
        $validated['sort_order'] = (int) ($validated['sort_order'] ?? 0);

        if ($request->hasFile('image')) {
            $validated['image_path'] = $this->storeImage($request);
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
        $validated = $this->validateSlide($request);
        $validated['is_active'] = $request->boolean('is_active');
        $validated['sort_order'] = (int) ($validated['sort_order'] ?? 0);

        if ($request->hasFile('image')) {
            $this->deleteImage($heroSlide);
            $validated['image_path'] = $this->storeImage($request);
        }

        $heroSlide->update($validated);

        return redirect()
            ->route('admin.hero-slides.index')
            ->with('success', 'Hero slide updated successfully.');
    }

    public function destroy(HeroSlide $heroSlide)
    {
        $this->deleteImage($heroSlide);
        $heroSlide->delete();

        return redirect()
            ->route('admin.hero-slides.index')
            ->with('success', 'Hero slide deleted successfully.');
    }

    protected function validateSlide(Request $request): array
    {
        return $request->validate([
            'tag' => ['nullable', 'string', 'max:60'],
            'heading' => ['required', 'string', 'max:120'],
            'subheading' => ['nullable', 'string', 'max:500'],
            'cta_text' => ['nullable', 'string', 'max:40'],
            'cta_link' => ['nullable', 'url', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);
    }

    protected function storeImage(Request $request): string
    {
        $image = $request->file('image');
        $imageName = uniqid('hero_slide_', true).'.'.$image->getClientOriginalExtension();
        File::ensureDirectoryExists(storage_path('app/public/hero-slides'));
        $image->move(storage_path('app/public/hero-slides'), $imageName);

        return 'storage/hero-slides/'.$imageName;
    }

    protected function deleteImage(HeroSlide $slide): void
    {
        if (! $slide->image_path || Str::startsWith($slide->image_path, 'frontend/')) {
            return;
        }

        $relativePath = Str::after($slide->image_path, 'storage/');
        $imagePath = storage_path('app/public/hero-slides/'.$relativePath);

        if (is_file($imagePath)) {
            unlink($imagePath);
        }
    }
}
