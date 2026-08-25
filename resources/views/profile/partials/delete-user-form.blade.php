<div x-data="{ open: false }">
  <button @click="open = true" type="button" class="w-full bg-error text-white font-bold py-3 rounded-lg uppercase tracking-widest text-label-caps hover:opacity-90 active:scale-[0.98] transition-all">
    Delete Account
  </button>

  {{-- Modal backdrop --}}
  <div x-show="open" x-transition:enter="transition-opacity duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition-opacity duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" @click="open = false" class="fixed inset-0 bg-black/50 z-50" style="display:none"></div>

  {{-- Modal panel --}}
  <div x-show="open" x-transition:enter="transition-transform duration-200" x-transition:enter-start="scale-95 opacity-0" x-transition:enter-end="scale-100 opacity-100" x-transition:leave="transition-transform duration-200" x-transition:leave-start="scale-100 opacity-100" x-transition:leave-end="scale-95 opacity-0" @click.away="open = false" class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display:none">
    <div class="bg-surface-container-lowest rounded-xl shadow-xl w-full max-w-md p-6" @click.stop>

      <h3 class="font-headline-sm text-headline-sm text-error mb-2">Confirm Delete</h3>
      <p class="text-on-surface-variant font-body-md text-body-md mb-4">This action is permanent. Enter your password to continue.</p>

      <form method="POST" action="{{ route('profile.destroy') }}">
        @csrf
        @method('DELETE')

        <div class="mb-4">
          <input type="password" name="password" placeholder="Password"
                 class="w-full border border-outline-variant rounded-lg px-4 py-2 bg-surface focus:ring-primary focus:border-primary">
          @error('password')
            <p class="text-error text-body-sm mt-1">{{ $message }}</p>
          @enderror
        </div>

        <div class="flex gap-3">
          <button type="button" @click="open = false" class="flex-1 border border-outline-variant text-on-surface font-bold py-3 rounded-lg hover:bg-surface-container-low transition-all">
            Cancel
          </button>
          <button type="submit" class="flex-1 bg-error text-white font-bold py-3 rounded-lg hover:opacity-90 active:scale-[0.98] transition-all">
            Delete
          </button>
        </div>
      </form>

    </div>
  </div>
</div>
