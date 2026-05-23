@extends('admin.maindesign')

@section('page_title', 'Edit Sale Banner')
@section('page_header', 'Edit Sale Banner')
@section('page_subtitle', 'Update promo copy, schedule, image, and visibility.')

@section('dashboard')
<div class="container-fluid">
    <div class="block">
        <div class="title mb-4">
            <strong>Banner Details</strong>
        </div>

        <form action="{{ route('admin.sale-banners.update', $banner) }}" method="POST" enctype="multipart/form-data">
            @method('PUT')
            @include('admin.sale-banners._form', ['buttonText' => 'Update Banner'])
        </form>
    </div>
</div>
@endsection
