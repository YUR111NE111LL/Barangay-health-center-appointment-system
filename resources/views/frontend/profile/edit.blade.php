@extends('frontend.layouts.app')

@section('title', 'Edit Profile')

@section('content')
<div class="mb-6">
    <a href="{{ route('resident.profile.show') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-teal-600 transition hover:text-teal-700">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        Back to profile
    </a>
</div>

<h1 class="mb-6 text-2xl font-bold text-slate-800">Edit profile</h1>

<form action="{{ route('resident.profile.update') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
    @csrf
    @method('PUT')
    
    <!-- Profile Picture Section -->
    <div class="overflow-hidden rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200/60">
        <h2 class="mb-4 text-lg font-semibold text-slate-800">Profile Picture</h2>
        <div class="space-y-4">
            <!-- Current/Preview Image -->
            <div class="flex flex-col items-center gap-4 sm:flex-row sm:items-start">
                <div class="relative flex-shrink-0">
                    @if($user->profile_picture)
                        <img src="{{ str_contains($user->profile_picture, 'cloudinary.com') ? $user->profile_picture : asset('storage/' . $user->profile_picture) }}" 
                             alt="{{ $user->name }}" 
                             id="profile-preview"
                             class="h-20 w-20 rounded-full border-3 border-slate-200 object-cover object-center shadow-lg ring-2 ring-slate-100 transition-all sm:h-24 sm:w-24">
                    @else
                        <div id="profile-preview" class="flex h-20 w-20 items-center justify-center rounded-full border-3 border-slate-200 bg-gradient-to-br from-teal-400 to-cyan-500 text-2xl font-bold text-white shadow-lg ring-2 ring-slate-100 transition-all sm:h-24 sm:w-24 sm:text-3xl">
                            {{ $user->initials }}
                        </div>
                    @endif
                    <div class="absolute -bottom-0.5 -right-0.5 h-5 w-5 rounded-full border-2 border-white bg-emerald-500 shadow-md"></div>
                </div>
                <div class="flex-1 space-y-3 text-center sm:text-left">
                    <div>
                        <label for="profile_picture" class="mb-1.5 block text-sm font-medium text-slate-700">Upload new picture</label>
                        <input type="file" 
                               name="profile_picture" 
                               id="profile_picture" 
                               accept="image/png,image/jpeg,image/jpg,image/gif,image/webp"
                               class="block w-full text-sm text-slate-500 file:mr-4 file:rounded-lg file:border-0 file:bg-teal-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-teal-700 hover:file:bg-teal-100 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2">
                        <p class="mt-1 text-xs text-slate-500">JPG, PNG, GIF or WEBP. Max size: 2MB</p>
                        @error('profile_picture')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    @if($user->profile_picture)
                    <div class="flex items-center justify-center gap-2 sm:justify-start">
                        <input type="checkbox" name="remove_profile_picture" id="remove_profile_picture" value="1" class="h-4 w-4 rounded border-slate-300 text-rose-600 focus:ring-rose-500">
                        <label for="remove_profile_picture" class="text-sm text-slate-700">Remove current picture</label>
                    </div>
                    @endif
                </div>
            </div>
            
            <!-- Large Preview (shown when new image selected) -->
            <div id="large-preview-container" class="hidden rounded-xl border-2 border-dashed border-teal-200 bg-teal-50/50 p-4">
                <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-teal-700">Preview (This is how it will look)</p>
                <div class="flex justify-center">
                    <img id="large-preview" src="" alt="Large preview" class="max-h-48 max-w-full rounded-lg border-2 border-teal-300 object-cover shadow-md ring-2 ring-teal-400">
                </div>
            </div>
        </div>
    </div>

    <!-- Personal Information Section -->
    <div class="overflow-hidden rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200/60">
        <h2 class="mb-4 text-lg font-semibold text-slate-800">Personal Information</h2>
        <div class="space-y-4">
            <div>
                <label for="name" class="mb-1.5 block text-sm font-medium text-slate-700">
                    Full Name <span class="text-rose-500">*</span>
                </label>
                <input type="text" 
                       name="name" 
                       id="name" 
                       value="{{ old('name', $user->name) }}" 
                       required 
                       class="w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-2.5 text-slate-800 shadow-sm transition focus:border-teal-500 focus:bg-white focus:ring-2 focus:ring-teal-500/20">
                @error('name')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>
            
            <div>
                <label for="purok_address" class="mb-1.5 block text-sm font-medium text-slate-700">
                    <svg class="mr-1 inline h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Purok Address
                </label>
                <input type="text" 
                       name="purok_address" 
                       id="purok_address" 
                       value="{{ old('purok_address', $user->purok_address) }}" 
                       placeholder="e.g., Purok 1, Purok 2, etc."
                       class="w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-2.5 text-slate-800 shadow-sm transition focus:border-teal-500 focus:bg-white focus:ring-2 focus:ring-teal-500/20">
                <p class="mt-1 text-xs text-slate-500">Enter your purok or zone address</p>
                @error('purok_address')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>
            
            <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                <p class="text-sm text-slate-600">
                    <span class="font-medium">Email:</span> 
                    <span class="text-slate-800">{{ $user->email }}</span>
                    <span class="ml-2 text-xs text-slate-500">(cannot be changed here)</span>
                </p>
            </div>
        </div>
    </div>

    <!-- Security Section -->
    <div class="overflow-hidden rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200/60">
        <h2 class="mb-4 text-lg font-semibold text-slate-800">Change Password</h2>
        <div class="space-y-4">
            <div>
                <label for="password" class="mb-1.5 block text-sm font-medium text-slate-700">New password</label>
                <input type="password" 
                       name="password" 
                       id="password" 
                       autocomplete="new-password"
                       class="w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-2.5 text-slate-800 shadow-sm transition focus:border-teal-500 focus:bg-white focus:ring-2 focus:ring-teal-500/20">
                <p class="mt-1 text-xs text-slate-500">Leave blank to keep current password.</p>
                @error('password')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="password_confirmation" class="mb-1.5 block text-sm font-medium text-slate-700">Confirm new password</label>
                <input type="password" 
                       name="password_confirmation" 
                       id="password_confirmation" 
                       autocomplete="new-password"
                       class="w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-2.5 text-slate-800 shadow-sm transition focus:border-teal-500 focus:bg-white focus:ring-2 focus:ring-teal-500/20">
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
        <a href="{{ route('resident.profile.show') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2">
            Cancel
        </a>
        <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-teal-600 px-5 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            Save changes
        </button>
    </div>
</form>

@push('scripts')
<script>
(function() {
    const fileInput = document.getElementById('profile_picture');
    const previewContainer = document.getElementById('profile-preview').parentElement;
    const largePreviewContainer = document.getElementById('large-preview-container');
    const largePreview = document.getElementById('large-preview');
    let preview = document.getElementById('profile-preview');
    const removeCheckbox = document.getElementById('remove_profile_picture');
    
    if (fileInput && preview) {
        fileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Validate file size (2MB)
                if (file.size > 2 * 1024 * 1024) {
                    showToast('File size must be less than 2MB', 'error');
                    fileInput.value = '';
                    if (largePreviewContainer) largePreviewContainer.classList.add('hidden');
                    return;
                }
                
                // Validate file type
                const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                if (!validTypes.includes(file.type)) {
                    showToast('Please select a valid image file (JPG, PNG, GIF, or WEBP)', 'error');
                    fileInput.value = '';
                    if (largePreviewContainer) largePreviewContainer.classList.add('hidden');
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    const imageUrl = e.target.result;
                    
                    // Update small preview (circular)
                    if (preview.tagName === 'IMG') {
                        preview.src = imageUrl;
                        preview.classList.add('ring-2', 'ring-teal-400', 'scale-105');
                    } else {
                        const img = document.createElement('img');
                        img.src = imageUrl;
                        img.className = 'h-20 w-20 rounded-full border-3 border-slate-200 object-cover object-center shadow-lg ring-2 ring-teal-400 transition-all scale-105 sm:h-24 sm:w-24';
                        img.alt = 'Profile preview';
                        preview.parentNode.replaceChild(img, preview);
                        img.id = 'profile-preview';
                        preview = img;
                    }
                    
                    // Show large preview
                    if (largePreviewContainer && largePreview) {
                        largePreview.src = imageUrl;
                        largePreviewContainer.classList.remove('hidden');
                    }
                    
                    // Remove success indicator after animation
                    setTimeout(() => {
                        preview.classList.remove('ring-teal-400', 'scale-105');
                    }, 2000);
                };
                reader.onerror = function() {
                    showToast('Error reading file. Please try again.', 'error');
                    if (largePreviewContainer) largePreviewContainer.classList.add('hidden');
                };
                reader.readAsDataURL(file);
            } else {
                // Hide large preview if file selection cancelled
                if (largePreviewContainer) largePreviewContainer.classList.add('hidden');
                if (preview.tagName === 'IMG') {
                    preview.classList.remove('scale-105');
                }
            }
        });
        
        // Handle remove checkbox
        if (removeCheckbox && preview) {
            removeCheckbox.addEventListener('change', function() {
                if (this.checked) {
                    if (preview.tagName === 'IMG') {
                        preview.style.opacity = '0.5';
                    }
                    if (largePreviewContainer) largePreviewContainer.classList.add('hidden');
                } else {
                    if (preview.tagName === 'IMG') {
                        preview.style.opacity = '1';
                    }
                }
            });
        }
    }
})();
</script>
@endpush
@endsection
