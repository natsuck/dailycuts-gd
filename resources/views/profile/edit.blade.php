@extends('maindesign')

@section('content')

<section class="py-8 md:py-12 px-4 md:px-40">
  <div class="mx-auto" style="max-width: 700px;">

    <div class="text-center mb-6">
      <h2 class="font-headline-md text-headline-md text-on-surface">Account Settings</h2>
    </div>

    @if(session('status'))
      <div class="bg-green-50 border border-green-300 text-green-800 px-4 py-3 rounded mb-4">
        {{ session('status') === 'profile-updated' ? 'Profile updated successfully.' : session('status') }}
      </div>
    @endif

    {{-- Profile Information --}}
    <div class="profile-card mb-4">
      <div class="profile-card-body">
        <h6 class="mb-3 font-bold">Profile Information</h6>

        <form method="POST" action="{{ route('profile.update') }}">
          @csrf
          @method('PATCH')

          <div class="mb-3">
            <label class="form-label">Name</label>
            <input type="text" name="name" class="form-control"
                   value="{{ old('name', $user->name) }}" required>
            @error('name')
              <small class="text-danger">{{ $message }}</small>
            @enderror
          </div>

          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control"
                   value="{{ old('email', $user->email) }}" required>
            @error('email')
              <small class="text-danger">{{ $message }}</small>
            @enderror
          </div>

          <button type="submit" class="btn w-100"
                  style="background:#D2042D; color:white;">
            Save Changes
          </button>
        </form>
      </div>
    </div>

    {{-- Change Password --}}
    <div class="profile-card mb-4">
      <div class="profile-card-body">
        <h6 class="mb-3 font-bold">Change Password</h6>

        <form method="POST" action="{{ route('password.update') }}">
          @csrf
          @method('PUT')

          <div class="mb-3">
            <label class="form-label">Current Password</label>
            <input type="password" name="current_password" class="form-control">
            @error('current_password')
              <small class="text-danger">{{ $message }}</small>
            @enderror
          </div>

          <div class="mb-3">
            <label class="form-label">New Password</label>
            <input type="password" name="password" class="form-control">
            @error('password')
              <small class="text-danger">{{ $message }}</small>
            @enderror
          </div>

          <div class="mb-3">
            <label class="form-label">Confirm Password</label>
            <input type="password" name="password_confirmation" class="form-control">
          </div>

          <button type="submit" class="btn w-100"
                  style="background:#D2042D; color:white;">
            Update Password
          </button>
        </form>
      </div>
    </div>

    {{-- Delete Account --}}
    <div class="profile-card">
      <div class="profile-card-body">
        <h6 class="mb-3 font-bold text-danger">Delete Account</h6>
        <p class="text-muted small mb-3">Once you delete your account, there is no going back. Please be certain.</p>

        @include('profile.partials.delete-user-form')
      </div>
    </div>

  </div>
</section>

@endsection
