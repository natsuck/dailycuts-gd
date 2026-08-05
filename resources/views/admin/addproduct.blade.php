@extends('admin.maindesign')

@section('add_product')

    @if(session('product_message'))
        <div class="alert alert-success mb-4">{{ session('product_message') }}</div>
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
        <form action="{{ route('admin.postaddproduct') }}" method="POST" enctype="multipart/form-data" data-submit-once>
            @csrf

            <div class="row">
                <div class="col-md-8">
                    <div class="card mb-4">
                        <div class="card-header"><h5 class="card-title mb-0">Product Information</h5></div>
                        <div class="card-body">
                            <div class="form-group mb-3">
                                <label><strong>Product Title</strong></label>
                                <input type="text" name="product_title" class="form-control @error('product_title') is-invalid @enderror" placeholder="Enter product title" value="{{ old('product_title') }}">
                                @error('product_title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="form-group mb-3">
                                <label><strong>Description</strong></label>
                                <textarea name="product_description" class="form-control @error('product_description') is-invalid @enderror" rows="4" placeholder="Product description">{{ old('product_description') }}</textarea>
                                @error('product_description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="form-group mb-3">
                                <label><strong>Category</strong></label>
                                <select name="product_category" class="form-control @error('product_category') is-invalid @enderror">
                                    <option value="">Select category</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->category }}" {{ old('product_category') == $category->category ? 'selected' : '' }}>{{ $category->category }}</option>
                                    @endforeach
                                </select>
                                @error('product_category')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="form-group mb-3">
                                <label><strong>Additional Categories</strong></label>
                                <div class="border rounded p-3" style="max-height: 180px; overflow-y: auto;">
                                    @foreach($categories as $category)
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" name="categories[]" value="{{ $category->id }}" id="category_{{ $category->id }}" {{ (is_array(old('categories')) && in_array($category->id, old('categories'))) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="category_{{ $category->id }}">{{ $category->category }}</label>
                                        </div>
                                    @endforeach
                                </div>
                                <small class="text-muted">Products can belong to multiple categories. The primary category above is always included.</small>
                                @error('categories')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                @error('categories.*')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="form-group mb-3">
                                <label><strong>Product Type</strong></label>
                                <select name="product_type" class="form-control @error('product_type') is-invalid @enderror">
                                    <option value="fresh" {{ old('product_type', 'fresh') === 'fresh' ? 'selected' : '' }}>Fresh</option>
                                    <option value="frozen" {{ old('product_type') === 'frozen' ? 'selected' : '' }}>Frozen</option>
                                    <option value="pantry" {{ old('product_type') === 'pantry' ? 'selected' : '' }}>Pantry Staples</option>
                                    <option value="produce" {{ old('product_type') === 'produce' ? 'selected' : '' }}>Fresh Produce</option>
                                </select>
                                <small class="text-muted">Controls the badge shown on product cards. Fresh is the default.</small>
                                @error('product_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="form-group mb-3">
                                <label><strong>Product Image</strong></label>
                                <input type="file" name="product_image" class="form-control-file @error('product_image') is-invalid @enderror" accept="image/*">
                                @error('product_image')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header"><h5 class="card-title mb-0">Stock & Pricing</h5></div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group mb-3">
                                        <label><strong>Quantity</strong></label>
                                        <input type="number" name="product_quantity" class="form-control @error('product_quantity') is-invalid @enderror" placeholder="0" value="{{ old('product_quantity') }}" min="0">
                                        @error('product_quantity')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group mb-3">
                                        <label><strong>Reorder Level</strong></label>
                                        <input type="number" name="reorder_level" class="form-control" placeholder="10" value="{{ old('reorder_level', 10) }}" min="0">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group mb-3">
                                        <label><strong>Price (&#8369;)</strong></label>
                                        <input type="number" name="product_price" class="form-control @error('product_price') is-invalid @enderror" placeholder="0.00" value="{{ old('product_price') }}" step="0.01" min="0">
                                        @error('product_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                            </div>
                            <div class="form-group mb-0">
                                <label><strong>Expiry Date</strong></label>
                                <input type="date" name="expiry_date" class="form-control" value="{{ old('expiry_date') }}">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header"><h5 class="card-title mb-0">Recommended Pairings</h5></div>
                    <div class="card-body">
                        <p class="text-muted small mb-3">Products suggested alongside this item on the product page and cart. Leave unchecked for none.</p>
                        <div class="pairings-scroll">
                            @forelse($allProducts as $option)
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" name="pairings[]" value="{{ $option->id }}" id="pairing-{{ $option->id }}" {{ in_array($option->id, old('pairings', [])) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="pairing-{{ $option->id }}">{{ $option->product_title }}</label>
                                </div>
                            @empty
                                <p class="text-muted small mb-0">No other products available yet.</p>
                            @endforelse
                        </div>
                        <style>.pairings-scroll { max-height: 240px; overflow-y: auto; border: 1px solid #e9ecef; border-radius: 4px; padding: 10px; }</style>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card mb-4">
                        <div class="card-header"><h5 class="card-title mb-0">Weight Variants</h5></div>
                        <div class="card-body">
                            <p class="text-muted small mb-3">Add weight options like 250g, 500g, 1kg. If variants are added, quantity and price above can be left empty.</p>
                            <div id="variants-container">
                                <div class="variant-row form-row mb-2">
                                    <div class="col">
                                        <input type="text" name="variant_weight[]" class="form-control form-control-sm" placeholder="Weight (e.g. 250g)">
                                    </div>
                                    <div class="col">
                                        <input type="number" name="variant_price[]" class="form-control form-control-sm" placeholder="Price" step="0.01" min="0">
                                    </div>
                                    <div class="col">
                                        <input type="number" name="variant_quantity[]" class="form-control form-control-sm" placeholder="Stock" min="0">
                                    </div>
                                    <div class="col-auto">
                                        <button type="button" onclick="removeVariant(this)" class="btn btn-danger btn-sm"><i class="fa fa-times"></i></button>
                                    </div>
                                </div>
                            </div>
                            <button type="button" onclick="addVariant()" class="btn btn-success btn-sm mt-2">
                                <i class="fa fa-plus mr-1"></i> Add Weight Option
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-lg">
                <i class="fa fa-save mr-1"></i> Add Product
            </button>
        </form>
    </div>

    <script>
        function addVariant() {
            var container = document.getElementById('variants-container');
            var row = document.createElement('div');
            row.className = 'variant-row form-row mb-2';
            row.innerHTML = '<div class="col"><input type="text" name="variant_weight[]" class="form-control form-control-sm" placeholder="Weight (e.g. 500g)"></div>' +
                '<div class="col"><input type="number" name="variant_price[]" class="form-control form-control-sm" placeholder="Price" step="0.01" min="0"></div>' +
                '<div class="col"><input type="number" name="variant_quantity[]" class="form-control form-control-sm" placeholder="Stock" min="0"></div>' +
                '<div class="col-auto"><button type="button" onclick="removeVariant(this)" class="btn btn-danger btn-sm"><i class="fa fa-times"></i></button></div>';
            container.appendChild(row);
        }
        function removeVariant(btn) {
            var container = document.getElementById('variants-container');
            if (container.children.length > 1) {
                btn.closest('.variant-row').remove();
            }
        }
    </script>

@endsection
