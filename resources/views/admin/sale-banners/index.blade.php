@extends('admin.maindesign')

@section('page_title', 'Sale Banners')
@section('page_header', 'Sale Banners')
@section('page_subtitle', 'Configure storefront promo banners without editing code.')

@section('page_actions')
    <a href="{{ route('admin.sale-banners.create') }}" class="btn btn-primary btn-sm">
        <i class="fa fa-plus mr-1"></i> New Banner
    </a>
@endsection

@push('styles')
<style>
    .sale-banner-admin .banner-preview {
        border-radius: 8px;
        min-height: 92px;
        overflow: hidden;
        padding: 16px;
        position: relative;
    }

    .sale-banner-admin .banner-preview img {
        border-radius: 6px;
        height: 70px;
        object-fit: cover;
        width: 96px;
    }

    .sale-banner-admin .banner-preview-title {
        font-weight: 700;
        line-height: 1.2;
    }

    .sale-banner-admin .banner-status {
        border-radius: 999px;
        display: inline-block;
        font-size: 0.72rem;
        font-weight: 700;
        padding: 0.4rem 0.6rem;
        text-transform: uppercase;
    }

    .sale-banner-admin .banner-status-active {
        background: #28a745;
        color: #fff;
    }

    .sale-banner-admin .banner-status-inactive {
        background: #6c757d;
        color: #fff;
    }
</style>
@endpush

@section('dashboard')
<div class="container-fluid sale-banner-admin">
    @if(session('success'))
        <div class="alert alert-success mb-4">{{ session('success') }}</div>
    @endif

    <div class="block">
        <div class="title mb-3">
            <strong>Current Banners</strong>
            <p class="text-muted mb-0">Active banners display on the storefront when their date window is valid.</p>
        </div>

        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th style="width: 45%;">Preview</th>
                        <th>Status</th>
                        <th>Schedule</th>
                        <th class="text-right">Order</th>
                        <th style="width: 170px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($banners as $banner)
                        <tr>
                            <td>
                                <div class="banner-preview d-flex align-items-center" style="background: {{ $banner->background_color }}; color: {{ $banner->text_color }};">
                                    @if($banner->image_path)
                                        <img src="{{ asset($banner->image_path) }}" alt="{{ $banner->title }}" class="mr-3">
                                    @endif
                                    <div>
                                        @if($banner->badge_text)
                                            <div class="small text-uppercase">{{ $banner->badge_text }}</div>
                                        @endif
                                        <div class="banner-preview-title">{{ $banner->title }}</div>
                                        @if($banner->subtitle)
                                            <div class="small">{{ Str::limit($banner->subtitle, 90) }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="banner-status {{ $banner->is_active ? 'banner-status-active' : 'banner-status-inactive' }}">
                                    {{ $banner->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td>
                                <div>{{ $banner->starts_at ? $banner->starts_at->format('M d, Y h:i A') : 'Starts anytime' }}</div>
                                <div class="text-muted">{{ $banner->ends_at ? 'Ends '.$banner->ends_at->format('M d, Y h:i A') : 'No end date' }}</div>
                            </td>
                            <td class="text-right">{{ $banner->sort_order }}</td>
                            <td>
                                <a href="{{ route('admin.sale-banners.edit', $banner) }}" class="btn btn-sm btn-primary btn-block">Edit</a>
                                <form action="{{ route('admin.sale-banners.destroy', $banner) }}" method="POST" class="mt-2">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger btn-block" onclick="return confirm('Delete this sale banner?')">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-5">No sale banners yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-center mt-4">
            {{ $banners->links() }}
        </div>
    </div>
</div>
@endsection
