@extends('maindesign')

@section('content')

<section class="profile_section layout_padding">
  <div class="container" style="max-width: 700px;">

    <div class="heading_container heading_center mb-4">
      <h2>Account Settings</h2>
    </div>

    <div class="profile-card">

      <div class="profile-card-body">

        <form method="POST" action="{{ route('profile.update') }}">
          @csrf
          @method('PATCH')

          <!-- NAME -->
          <div class="mb-3">
            <label class="form-label">Name</label>
            <input type="text" name="name" class="form-control"
                   value="{{ old('name', $user->name) }}" required>
          </div>

          <!-- EMAIL -->
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control"
                   value="{{ old('email', $user->email) }}" required>
          </div>

          <hr>

          <!-- PASSWORD SECTION -->
          <h6 class="mb-2">Change Password</h6>

          <div class="mb-3">
            <input type="password" name="current_password"
                   class="form-control" placeholder="Current Password">
          </div>

          <div class="mb-3">
            <input type="password" name="password"
                   class="form-control" placeholder="New Password">
          </div>

          <div class="mb-3">
            <input type="password" name="password_confirmation"
                   class="form-control" placeholder="Confirm Password">
          </div>

          <!-- SINGLE BUTTON 🔥 -->
          <button type="submit" class="btn w-100"
                  style="background:#D2042D; color:white;">
            Save Changes
          </button>

        </form>

      </div>
    </div>
  </div>
</section>

@endsection