@extends('admin.maindesign')

@section('update_category')

    @if(session('category_updated_message'))
        <div class="alert alert-success mb-4">{{ session('category_updated_message') }}</div>
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
                <h5 class="card-title mb-0">Update Category</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.postupdatecategory', $category->id) }}" method="POST">
                    @csrf
                    <div class="form-group mb-3">
                        <label for="category"><strong>Category Name</strong></label>
                        <input type="text" name="category" id="category" class="form-control @error('category') is-invalid @enderror" value="{{ old('category', $category->category) }}">
                        @error('category')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-save mr-1"></i> Update Category
                    </button>
                    <a href="{{ route('admin.viewcategory') }}" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>

@endsection
