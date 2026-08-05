@extends('admin.maindesign')

@section('add_coupon')

@if(session('success'))
    <div class="alert alert-success mb-4">{{ session('success') }}</div>
@endif

@if($errors->any())
    <div class="alert alert-danger mb-4">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Create New Coupon</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.coupons.store') }}" method="POST" data-submit-once>
                @csrf

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label><strong>Coupon Code</strong></label>
                            <input type="text" name="code" class="form-control @error('code') is-invalid @enderror" placeholder="e.g. SUMMER20" value="{{ old('code') }}">
                            @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label><strong>Type</strong></label>
                            <select name="type" class="form-control @error('type') is-invalid @enderror" id="coupon-type">
                                <option value="percentage" {{ old('type') == 'percentage' ? 'selected' : '' }}>Percentage</option>
                                <option value="fixed" {{ old('type') == 'fixed' ? 'selected' : '' }}>Fixed</option>
                                <option value="free_shipping" {{ old('type') == 'free_shipping' ? 'selected' : '' }}>Free Shipping</option>
                            </select>
                            @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <small class="form-text text-muted">Free Shipping waives the delivery fee when the order reaches the Minimum Order amount.</small>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group mb-3">
                            <label><strong>Value</strong></label>
                            <input type="number" name="value" class="form-control @error('value') is-invalid @enderror" placeholder="0.00" value="{{ old('value') }}" step="0.01" min="0">
                            @error('value')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group mb-3">
                            <label><strong>Minimum Order (&#8369;)</strong></label>
                            <input type="number" name="min_order" class="form-control @error('min_order') is-invalid @enderror" placeholder="0.00" value="{{ old('min_order') }}" step="0.01" min="0">
                            @error('min_order')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group mb-3">
                            <label><strong>Maximum Discount (&#8369;)</strong></label>
                            <input type="number" name="max_discount" class="form-control @error('max_discount') is-invalid @enderror" placeholder="0.00" value="{{ old('max_discount') }}" step="0.01" min="0">
                            @error('max_discount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group mb-3">
                            <label><strong>Usage Limit</strong></label>
                            <input type="number" name="usage_limit" class="form-control @error('usage_limit') is-invalid @enderror" placeholder="1" value="{{ old('usage_limit') }}" min="1">
                            @error('usage_limit')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group mb-3">
                            <label><strong>Start Date</strong></label>
                            <input type="date" name="starts_at" class="form-control @error('starts_at') is-invalid @enderror" value="{{ old('starts_at') }}">
                            @error('starts_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group mb-3">
                            <label><strong>Expiry Date</strong></label>
                            <input type="date" name="expires_at" class="form-control @error('expires_at') is-invalid @enderror" value="{{ old('expires_at') }}">
                            @error('expires_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                <div class="form-group mb-4">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" name="is_active" class="custom-control-input" id="is_active" value="1" {{ old('is_active', 1) ? 'checked' : '' }}>
                        <label class="custom-control-label" for="is_active"><strong>Active</strong></label>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-save mr-1"></i> Create Coupon
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var type = document.getElementById('coupon-type');
        var valueField = document.querySelector('input[name="value"]');
        var maxField = document.querySelector('input[name="max_discount"]');
        var valueForm = valueField.closest('.form-group');
        var maxForm = maxField.closest('.form-group');

        function toggleFreeShipping() {
            var isFree = type.value === 'free_shipping';
            valueForm.style.display = isFree ? 'none' : '';
            maxForm.style.display = isFree ? 'none' : '';
            if (isFree) {
                valueField.value = '0';
                maxField.value = '';
            }
        }

        type.addEventListener('change', toggleFreeShipping);
        toggleFreeShipping();
    });
</script>

@endsection
