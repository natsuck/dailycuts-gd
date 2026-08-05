@extends('admin.maindesign')

@section('page_title', 'Store Locations')
@section('page_header', 'Store Locations')
@section('page_subtitle', 'Manage the storefront locations shown to customers and the Lalamove delivery pickup.')

@section('page_actions')
  <a href="{{ route('admin.store-locations.create') }}" class="btn btn-primary btn-sm">
    <i class="fa fa-plus mr-1"></i>
    New Location
  </a>
@endsection

@section('dashboard')
  <div class="container-fluid">
    @if (session('success'))
      <div class="alert alert-success mb-4">{{ session('success') }}</div>
    @endif

    <div class="block">
      <div class="title mb-3">
        <strong>Current Locations</strong>
        <p class="text-muted mb-0">
          Active locations appear on the storefront "Find Our Stores" page. The location marked
          <span class="text-success font-weight-bold">Pickup</span> is used as the delivery origin.
        </p>
      </div>

      <div class="table-responsive">
        <table class="table table-hover">
          <thead>
            <tr>
              <th>Name</th>
              <th>Address</th>
              <th>Phone</th>
              <th class="text-center">Status</th>
              <th class="text-center">Pickup</th>
              <th class="text-right">Order</th>
              <th style="width: 170px">Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($locations as $location)
              <tr>
                <td>
                  <strong>{{ $location->name }}</strong>
                </td>
                <td class="text-muted">
                  {{ $location->fullAddress() }}
                  <div class="small">
                    {{ number_format($location->lat, 7) }}, {{ number_format($location->lng, 7) }}
                  </div>
                </td>
                <td>{{ $location->phone }}</td>
                <td class="text-center">
                  <span class="badge {{ $location->is_active ? 'badge-success' : 'badge-secondary' }}">
                    {{ $location->is_active ? 'Active' : 'Inactive' }}
                  </span>
                </td>
                <td class="text-center">
                  @if ($location->is_pickup)
                    <span class="badge badge-primary">Pickup</span>
                  @else
                    <span class="text-muted">&mdash;</span>
                  @endif
                </td>
                <td class="text-right">{{ $location->sort_order }}</td>
                <td>
                  <a
                    href="{{ route('admin.store-locations.edit', $location) }}"
                    class="btn btn-sm btn-primary btn-block"
                  >
                    Edit
                  </a>
                  <form
                    action="{{ route('admin.store-locations.destroy', $location) }}"
                    method="POST"
                    class="mt-2"
                  >
                    @csrf
                    @method('DELETE')
                    <button
                      type="submit"
                      class="btn btn-sm btn-outline-danger btn-block"
                      onclick="return confirm('Delete this store location?');"
                    >
                      Delete
                    </button>
                  </form>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="text-muted py-5 text-center">No store locations yet.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="d-flex justify-content-center mt-4">
        {{ $locations->links() }}
      </div>
    </div>
  </div>
@endsection
