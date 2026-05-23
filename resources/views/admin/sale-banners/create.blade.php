@extends('admin.maindesign')

@section('page_title', 'New Sale Banner')
@section('page_header', 'New Sale Banner')
@section('page_subtitle', 'Create a promo banner for the storefront.')

@section('dashboard')
<div class="container-fluid">
    <div class="block">
        <div class="title mb-4">
            <strong>Banner Details</strong>
        </div>

        <form action="{{ route('admin.sale-banners.store') }}" method="POST" enctype="multipart/form-data">
            @include('admin.sale-banners._form', ['buttonText' => 'Create Banner'])
        </form>
    </div>
</div>
@endsection
