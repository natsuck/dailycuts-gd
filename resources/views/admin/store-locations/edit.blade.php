@extends('admin.maindesign')

@section('page_title', 'Edit Store Location')
@section('page_header', 'Edit Store Location')
@section('page_subtitle', 'Update this store location.')

@section('dashboard')
  <div class="container-fluid">
    <div class="block">
      <div class="title mb-4">
        <strong>Location Details</strong>
      </div>

      <form
        action="{{ route('admin.store-locations.update', $location) }}"
        method="POST"
        data-submit-once
      >
        @csrf
        @method('PATCH')
        @include('admin.store-locations._form', ['buttonText' => 'Save Changes'])
      </form>
    </div>
  </div>
@endsection
