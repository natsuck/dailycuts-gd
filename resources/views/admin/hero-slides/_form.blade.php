@csrf

<div class="row">
  <div class="col-lg-8">
    <div class="form-group">
      <label for="heading">Heading</label>
      <input
        type="text"
        id="heading"
        name="heading"
        class="form-control"
        value="{{ old('heading', $slide->heading) }}"
        maxlength="120"
      />
      @error('heading')
        <small class="text-danger">{{ $message }}</small>
      @enderror
    </div>

    <div class="form-group">
      <label for="subheading">Subheading</label>
      <textarea id="subheading" name="subheading" class="form-control" rows="3" maxlength="500">
{{ old('subheading', $slide->subheading) }}</textarea
      >
      @error('subheading')
        <small class="text-danger">{{ $message }}</small>
      @enderror
    </div>

    <div class="row">
      <div class="col-md-6">
        <div class="form-group">
          <label for="tag">Tag / Eyebrow Text</label>
          <input
            type="text"
            id="tag"
            name="tag"
            class="form-control"
            value="{{ old('tag', $slide->tag) }}"
            placeholder="EXCLUSIVE DAILY CUTS"
            maxlength="60"
          />
          @error('tag')
            <small class="text-danger">{{ $message }}</small>
          @enderror
        </div>
      </div>

      <div class="col-md-6">
        <div class="form-group">
          <label for="sort_order">Sort Order</label>
          <input
            type="number"
            id="sort_order"
            name="sort_order"
            class="form-control"
            value="{{ old('sort_order', $slide->sort_order ?? 0) }}"
            min="0"
          />
          @error('sort_order')
            <small class="text-danger">{{ $message }}</small>
          @enderror
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-md-6">
        <div class="form-group">
          <label for="cta_text">Button Text</label>
          <input
            type="text"
            id="cta_text"
            name="cta_text"
            class="form-control"
            value="{{ old('cta_text', $slide->cta_text) }}"
            placeholder="Shop Now"
            maxlength="40"
          />
          @error('cta_text')
            <small class="text-danger">{{ $message }}</small>
          @enderror
        </div>
      </div>

      <div class="col-md-6">
        <div class="form-group">
          <label for="cta_link">Button URL</label>
          <input
            type="text"
            id="cta_link"
            name="cta_link"
            class="form-control"
            value="{{ old('cta_link', $slide->cta_link) }}"
            placeholder="{{ route('shop') }}"
          />
          @error('cta_link')
            <small class="text-danger">{{ $message }}</small>
          @enderror
        </div>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="form-group">
      <label for="image">Slide Image</label>
      <input type="file" id="image" name="image" class="form-control-file" accept="image/*" />
      @error('image')
        <small class="text-danger d-block">{{ $message }}</small>
      @enderror

      @if ($slide->image_path)
        <img
          src="{{ asset($slide->image_path) }}"
          alt="{{ $slide->heading }}"
          class="img-fluid mt-3 rounded"
        />
      @endif
    </div>

    <div class="form-group mt-4">
      <label for="mobile_image">Mobile Image <small class="text-muted">(optional)</small></label>
      <input
        type="file"
        id="mobile_image"
        name="mobile_image"
        class="form-control-file"
        accept="image/*"
      />
      <small class="form-text text-muted">
        Portrait crop of the slide image (e.g. 750x1000). Falls back to the default mobile hero when blank.
      </small>
      @error('mobile_image')
        <small class="text-danger d-block">{{ $message }}</small>
      @enderror

      @if ($slide->mobile_image_path)
        <img
          src="{{ asset($slide->mobile_image_path) }}"
          alt="{{ $slide->heading }} mobile"
          class="img-fluid mt-3 rounded"
        />
      @endif
    </div>

    <div class="custom-control custom-switch mb-4">
      <input
        type="checkbox"
        class="custom-control-input"
        id="is_active"
        name="is_active"
        value="1"
        {{ old('is_active', $slide->is_active) ? 'checked' : '' }}
      />
      <label class="custom-control-label" for="is_active">Active</label>
    </div>
  </div>
</div>

<div class="d-flex justify-content-end">
  <a href="{{ route('admin.hero-slides.index') }}" class="btn btn-outline-secondary mr-2">
    Cancel
  </a>
  <button type="submit" class="btn btn-primary">{{ $buttonText }}</button>
</div>
