<button class="btn w-100" style="background:#D2042D; color:white;"
        data-bs-toggle="modal" data-bs-target="#deleteModal">
    Delete Account
</button>

<!-- MODAL -->
<div class="modal fade" id="deleteModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content p-3">

      <form method="POST" action="{{ route('profile.destroy') }}">
        @csrf
        @method('DELETE')

        <h5 class="mb-3 text-danger">Confirm Delete</h5>
        <p class="text-muted small">
          This action is permanent. Enter your password to continue.
        </p>

        <input type="password" name="password" class="form-control mb-3" placeholder="Password">

        @error('password')
          <small class="text-danger">{{ $message }}</small>
        @enderror

        <div class="d-flex gap-2">
          <button type="button" class="btn btn-secondary w-50" data-bs-dismiss="modal">
            Cancel
          </button>
          <button type="submit" class="btn btn-danger w-50">
            Delete
          </button>
        </div>

      </form>

    </div>
  </div>
</div>