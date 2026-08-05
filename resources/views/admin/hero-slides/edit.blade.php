@extends('admin.maindesign')

@section('page_title', 'Edit Hero Slide')
@section('page_header', 'Edit Hero Slide')
@section('page_subtitle', 'Update hero copy, image, order, and visibility.')

@section('dashboard')
  <div class="container-fluid">
    <div class="block">
      <div class="title mb-4">
        <strong>Slide Details</strong>
      </div>

      <form
        action="{{ route('admin.hero-slides.update', $slide) }}"
        method="POST"
        enctype="multipart/form-data"
        data-submit-once
      >
        @method('PUT')
        @include('admin.hero-slides._form', ['buttonText' => 'Update Slide', 'isUpdate' => true])
      </form>
    </div>
  </div>
@endsection
