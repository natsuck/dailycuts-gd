@extends('admin.maindesign')

@section('page_title', 'New Hero Slide')
@section('page_header', 'New Hero Slide')
@section('page_subtitle', 'Create a slide for the homepage hero carousel.')

@section('dashboard')
  <div class="container-fluid">
    <div class="block">
      <div class="title mb-4">
        <strong>Slide Details</strong>
      </div>

      <form
        action="{{ route('admin.hero-slides.store') }}"
        method="POST"
        enctype="multipart/form-data"
        data-submit-once
      >
        @include('admin.hero-slides._form', ['buttonText' => 'Create Slide'])
      </form>
    </div>
  </div>
@endsection
