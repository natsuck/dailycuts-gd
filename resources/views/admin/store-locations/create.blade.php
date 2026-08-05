@extends('admin.maindesign')

@section('page_title', 'New Store Location')
@section('page_header', 'New Store Location')
@section('page_subtitle', 'Add a store location to show on the storefront.')

@section('dashboard')
  <div class="container-fluid">
    <div class="block">
      <div class="title mb-4">
        <strong>Location Details</strong>
      </div>

      <form action="{{ route('admin.store-locations.store') }}" method="POST" data-submit-once>
        @include('admin.store-locations._form', ['buttonText' => 'Create Location'])
      </form>
    </div>
  </div>
@endsection
