@extends('admin.maindesign')

@section('page_title', 'Hero Slides')
@section('page_header', 'Hero Slides')
@section('page_subtitle', 'Customize the homepage hero carousel without editing code.')

@section('page_actions')
  <a href="{{ route('admin.hero-slides.create') }}" class="btn btn-primary btn-sm">
    <i class="fa fa-plus mr-1"></i>
    New Slide
  </a>
@endsection

@push('styles')
  <style>
    .hero-slide-admin .slide-preview {
      border-radius: 8px;
      min-height: 92px;
      overflow: hidden;
      padding: 16px;
      position: relative;
    }

    .hero-slide-admin .slide-preview img {
      border-radius: 6px;
      height: 70px;
      object-fit: cover;
      width: 96px;
    }

    .hero-slide-admin .slide-preview-title {
      font-weight: 700;
      line-height: 1.2;
    }

    .hero-slide-admin .slide-status {
      border-radius: 999px;
      display: inline-block;
      font-size: 0.72rem;
      font-weight: 700;
      padding: 0.4rem 0.6rem;
      text-transform: uppercase;
    }

    .hero-slide-admin .slide-status-active {
      background: #28a745;
      color: #fff;
    }

    .hero-slide-admin .slide-status-inactive {
      background: #6c757d;
      color: #fff;
    }
  </style>
@endpush

@section('dashboard')
  <div class="container-fluid hero-slide-admin">
    @if (session('success'))
      <div class="alert alert-success mb-4">{{ session('success') }}</div>
    @endif

    <div class="block">
      <div class="title mb-3">
        <strong>Current Slides</strong>
        <p class="text-muted mb-0">
          Active slides display in the homepage carousel, ordered by their sort order.
        </p>
      </div>

      <div class="table-responsive">
        <table class="table-hover table">
          <thead>
            <tr>
              <th style="width: 45%">Preview</th>
              <th>Status</th>
              <th class="text-right">Order</th>
              <th style="width: 170px">Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($slides as $slide)
              <tr>
                <td>
                  <div class="slide-preview d-flex align-items-center bg-light">
                    @if ($slide->image_path)
                      <img
                        src="{{ asset($slide->image_path) }}"
                        alt="{{ $slide->heading }}"
                        class="mr-3"
                      />
                    @endif

                    <div>
                      @if ($slide->tag)
                        <div class="small text-uppercase text-muted">{{ $slide->tag }}</div>
                      @endif

                      <div class="slide-preview-title">{{ $slide->heading }}</div>
                      @if ($slide->subheading)
                        <div class="small text-muted">
                          {{ Str::limit($slide->subheading, 90) }}
                        </div>
                      @endif
                    </div>
                  </div>
                </td>
                <td>
                  <span
                    class="slide-status {{ $slide->is_active ? 'slide-status-active' : 'slide-status-inactive' }}"
                  >
                    {{ $slide->is_active ? 'Active' : 'Inactive' }}
                  </span>
                </td>
                <td class="text-right">{{ $slide->sort_order }}</td>
                <td>
                  <a
                    href="{{ route('admin.hero-slides.edit', $slide) }}"
                    class="btn btn-sm btn-primary btn-block"
                  >
                    Edit
                  </a>
                  <form
                    action="{{ route('admin.hero-slides.destroy', $slide) }}"
                    method="POST"
                    class="mt-2"
                  >
                    @csrf
                    @method('DELETE')
                    <button
                      type="submit"
                      class="btn btn-sm btn-outline-danger btn-block"
                      onclick="return confirm('Delete this hero slide?');"
                    >
                      Delete
                    </button>
                  </form>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="4" class="text-muted py-5 text-center">No hero slides yet.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="d-flex justify-content-center mt-4">
        {{ $slides->links() }}
      </div>
    </div>
  </div>
@endsection
