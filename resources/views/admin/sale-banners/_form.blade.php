@csrf

<div class="row">
    <div class="col-lg-8">
        <div class="form-group">
            <label for="title">Banner Title</label>
            <input type="text" id="title" name="title" class="form-control" value="{{ old('title', $banner->title) }}" required maxlength="120">
            @error('title') <small class="text-danger">{{ $message }}</small> @enderror
        </div>

        <div class="form-group">
            <label for="subtitle">Subtitle</label>
            <textarea id="subtitle" name="subtitle" class="form-control" rows="3" maxlength="500">{{ old('subtitle', $banner->subtitle) }}</textarea>
            @error('subtitle') <small class="text-danger">{{ $message }}</small> @enderror
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="badge_text">Badge Text</label>
                    <input type="text" id="badge_text" name="badge_text" class="form-control" value="{{ old('badge_text', $banner->badge_text) }}" placeholder="Weekend Sale" maxlength="60">
                    @error('badge_text') <small class="text-danger">{{ $message }}</small> @enderror
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label for="sort_order">Sort Order</label>
                    <input type="number" id="sort_order" name="sort_order" class="form-control" value="{{ old('sort_order', $banner->sort_order ?? 0) }}" min="0">
                    @error('sort_order') <small class="text-danger">{{ $message }}</small> @enderror
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="button_text">Button Text</label>
                    <input type="text" id="button_text" name="button_text" class="form-control" value="{{ old('button_text', $banner->button_text) }}" placeholder="Shop Sale" maxlength="40">
                    @error('button_text') <small class="text-danger">{{ $message }}</small> @enderror
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label for="button_url">Button URL</label>
                    <input type="text" id="button_url" name="button_url" class="form-control" value="{{ old('button_url', $banner->button_url) }}" placeholder="{{ route('shop') }}">
                    @error('button_url') <small class="text-danger">{{ $message }}</small> @enderror
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="form-group">
            <label for="image">Banner Image</label>
            <input type="file" id="image" name="image" class="form-control-file" accept="image/*">
            @error('image') <small class="text-danger d-block">{{ $message }}</small> @enderror

            @if($banner->image_path)
                <img src="{{ asset($banner->image_path) }}" alt="{{ $banner->title }}" class="img-fluid mt-3 rounded">
            @endif
        </div>

        <div class="row">
            <div class="col-6">
                <div class="form-group">
                    <label for="background_color">Background</label>
                    <input type="color" id="background_color" name="background_color" class="form-control" value="{{ old('background_color', $banner->background_color ?? '#7a1118') }}">
                    @error('background_color') <small class="text-danger">{{ $message }}</small> @enderror
                </div>
            </div>

            <div class="col-6">
                <div class="form-group">
                    <label for="text_color">Text</label>
                    <input type="color" id="text_color" name="text_color" class="form-control" value="{{ old('text_color', $banner->text_color ?? '#ffffff') }}">
                    @error('text_color') <small class="text-danger">{{ $message }}</small> @enderror
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 col-lg-12">
                <div class="form-group">
                    <label for="starts_at">Starts At</label>
                    <input type="datetime-local" id="starts_at" name="starts_at" class="form-control" value="{{ old('starts_at', optional($banner->starts_at)->format('Y-m-d\TH:i')) }}">
                    @error('starts_at') <small class="text-danger">{{ $message }}</small> @enderror
                </div>
            </div>

            <div class="col-md-6 col-lg-12">
                <div class="form-group">
                    <label for="ends_at">Ends At</label>
                    <input type="datetime-local" id="ends_at" name="ends_at" class="form-control" value="{{ old('ends_at', optional($banner->ends_at)->format('Y-m-d\TH:i')) }}">
                    @error('ends_at') <small class="text-danger">{{ $message }}</small> @enderror
                </div>
            </div>
        </div>

        <div class="custom-control custom-switch mb-4">
            <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1" {{ old('is_active', $banner->is_active) ? 'checked' : '' }}>
            <label class="custom-control-label" for="is_active">Active</label>
        </div>
    </div>
</div>

<div class="d-flex justify-content-end">
    <a href="{{ route('admin.sale-banners.index') }}" class="btn btn-outline-secondary mr-2">Cancel</a>
    <button type="submit" class="btn btn-primary">{{ $buttonText }}</button>
</div>
