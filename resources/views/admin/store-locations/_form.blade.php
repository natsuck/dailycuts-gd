@csrf

<div class="form-group">
  <label for="name">Location Name</label>
  <input
    type="text"
    id="name"
    name="name"
    class="form-control"
    value="{{ old('name', $location->name) }}"
    placeholder="Pasay Branch"
    maxlength="255"
  />
  @error('name')
    <small class="text-danger">{{ $message }}</small>
  @enderror
</div>

<div class="form-group">
  <label for="address">Street Address</label>
  <input
    type="text"
    id="address"
    name="address"
    class="form-control"
    value="{{ old('address', $location->address) }}"
    placeholder="161 Vallhalla Extension"
    maxlength="255"
  />
  @error('address')
    <small class="text-danger">{{ $message }}</small>
  @enderror
</div>

<div class="row">
  <div class="col-md-6">
    <div class="form-group">
      <label for="city">City <small class="text-muted">(optional)</small></label>
      <input
        type="text"
        id="city"
        name="city"
        class="form-control"
        value="{{ old('city', $location->city) }}"
        placeholder="Pasay City, Metro Manila"
        maxlength="255"
      />
      @error('city')
        <small class="text-danger">{{ $message }}</small>
      @enderror
    </div>
  </div>

  <div class="col-md-6">
    <div class="form-group">
      <label for="phone">Phone</label>
      <input
        type="text"
        id="phone"
        name="phone"
        class="form-control"
        value="{{ old('phone', $location->phone) }}"
        placeholder="+63 929 178 5657"
        maxlength="30"
      />
      @error('phone')
        <small class="text-danger">{{ $message }}</small>
      @enderror
    </div>
  </div>
</div>

<div class="row">
  <div class="col-md-6">
    <div class="form-group">
      <label for="lat">Latitude</label>
      <input
        type="number"
        step="any"
        id="lat"
        name="lat"
        class="form-control"
        value="{{ old('lat', $location->lat) }}"
        placeholder="14.5378000"
      />
      @error('lat')
        <small class="text-danger">{{ $message }}</small>
      @enderror
    </div>
  </div>

  <div class="col-md-6">
    <div class="form-group">
      <label for="lng">Longitude</label>
      <input
        type="number"
        step="any"
        id="lng"
        name="lng"
        class="form-control"
        value="{{ old('lng', $location->lng) }}"
        placeholder="121.0022000"
      />
      @error('lng')
        <small class="text-danger">{{ $message }}</small>
      @enderror
    </div>
  </div>
</div>

<div class="row">
  <div class="col-md-6">
    <div class="form-group">
      <label for="sort_order">Sort Order</label>
      <input
        type="number"
        id="sort_order"
        name="sort_order"
        class="form-control"
        value="{{ old('sort_order', $location->sort_order ?? 0) }}"
        min="0"
      />
      @error('sort_order')
        <small class="text-danger">{{ $message }}</small>
      @enderror
    </div>
  </div>
</div>

<div class="custom-control custom-switch mb-3">
  <input
    type="checkbox"
    class="custom-control-input"
    id="is_active"
    name="is_active"
    value="1"
    {{ old('is_active', $location->is_active) ? 'checked' : '' }}
  />
  <label class="custom-control-label" for="is_active">Active (visible on the storefront)</label>
</div>

<div class="custom-control custom-switch mb-3">
  <input
    type="checkbox"
    class="custom-control-input"
    id="is_pickup"
    name="is_pickup"
    value="1"
    {{ old('is_pickup', $location->is_pickup) ? 'checked' : '' }}
  />
  <label class="custom-control-label" for="is_pickup">
    Use as delivery pickup (Lalamove origin)
  </label>
</div>

<div class="d-flex justify-content-end">
  <a href="{{ route('admin.store-locations.index') }}" class="btn btn-outline-secondary mr-2">
    Cancel
  </a>
  <button type="submit" class="btn btn-primary">{{ $buttonText }}</button>
</div>
