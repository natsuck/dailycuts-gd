@extends('maindesign')

@section('content')

<section class="py-8 md:py-12 px-4 md:px-margin-desktop">
  <div class="max-w-[700px] mx-auto">

    <div class="text-center mb-8">
      <h2 class="font-headline-md text-headline-md text-on-surface">Account Settings</h2>
    </div>

    @if(session('status'))
      <div class="bg-green-50 border border-green-300 text-green-800 px-4 py-3 rounded-lg mb-6 font-body-md">
        {{ session('status') === 'profile-updated' ? 'Profile updated successfully.' : session('status') }}
      </div>
    @endif

    {{-- Profile Information --}}
    <div class="bg-surface-container-lowest border border-outline-variant rounded-lg p-6 mb-4">
      <h3 class="font-headline-sm text-headline-sm text-on-surface mb-4">Profile Information</h3>

      <form method="POST" action="{{ route('profile.update') }}">
        @csrf
        @method('PATCH')

        <div class="mb-4">
          <label for="profile-name" class="font-body-md text-body-md text-on-surface mb-2 block">Name</label>
          <input type="text" id="profile-name" name="name"
                 class="w-full border border-outline-variant rounded-lg px-4 py-2 bg-surface focus:ring-primary focus:border-primary"
                 value="{{ old('name', $user->name) }}" required>
          @error('name')
            <p class="text-error text-body-sm mt-1">{{ $message }}</p>
          @enderror
        </div>

        <div class="mb-4">
          <label for="profile-email" class="font-body-md text-body-md text-on-surface mb-2 block">Email</label>
          <input type="email" id="profile-email" name="email"
                 class="w-full border border-outline-variant rounded-lg px-4 py-2 bg-surface focus:ring-primary focus:border-primary"
                 value="{{ old('email', $user->email) }}" required>
          @error('email')
            <p class="text-error text-body-sm mt-1">{{ $message }}</p>
          @enderror
        </div>

        <button type="submit" class="w-full bg-primary text-on-primary font-bold py-3 rounded-lg uppercase tracking-widest text-label-caps hover:opacity-90 active:scale-[0.98] transition-all">
          Save Changes
        </button>
      </form>
    </div>

    {{-- Change Password --}}
    <div class="bg-surface-container-lowest border border-outline-variant rounded-lg p-6 mb-4">
      <h3 class="font-headline-sm text-headline-sm text-on-surface mb-4">Change Password</h3>

      <form method="POST" action="{{ route('password.update') }}">
        @csrf
        @method('PUT')

        <div class="mb-4">
          <label for="current-password" class="font-body-md text-body-md text-on-surface mb-2 block">Current Password</label>
          <input type="password" id="current-password" name="current_password"
                 class="w-full border border-outline-variant rounded-lg px-4 py-2 bg-surface focus:ring-primary focus:border-primary">
          @error('current_password')
            <p class="text-error text-body-sm mt-1">{{ $message }}</p>
          @enderror
        </div>

        <div class="mb-4">
          <label for="new-password" class="font-body-md text-body-md text-on-surface mb-2 block">New Password</label>
          <input type="password" id="new-password" name="password"
                 class="w-full border border-outline-variant rounded-lg px-4 py-2 bg-surface focus:ring-primary focus:border-primary">
          @error('password')
            <p class="text-error text-body-sm mt-1">{{ $message }}</p>
          @enderror
        </div>

        <div class="mb-4">
          <label for="confirm-password" class="font-body-md text-body-md text-on-surface mb-2 block">Confirm Password</label>
          <input type="password" id="confirm-password" name="password_confirmation"
                 class="w-full border border-outline-variant rounded-lg px-4 py-2 bg-surface focus:ring-primary focus:border-primary">
        </div>

        <button type="submit" class="w-full bg-primary text-on-primary font-bold py-3 rounded-lg uppercase tracking-widest text-label-caps hover:opacity-90 active:scale-[0.98] transition-all">
          Update Password
        </button>
      </form>
    </div>

    {{-- Delete Account --}}
    <div class="bg-surface-container-lowest border border-outline-variant rounded-lg p-6">
      <h3 class="font-headline-sm text-headline-sm text-error mb-2">Delete Account</h3>
      <p class="text-on-surface-variant font-body-md text-body-md mb-4">Once you delete your account, there is no going back. Please be certain.</p>

      @include('profile.partials.delete-user-form')
    </div>

  </div>
</section>

@endsection
