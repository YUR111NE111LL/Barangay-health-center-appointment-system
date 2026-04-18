@extends('tenant.layouts.app')
@section('title', 'New Announcement')
@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-slate-800">New Health Announcement</h1>
    <p class="mt-1 text-slate-500">Share health tips or barangay health updates with residents.</p>
</div>
<form action="{{ route('backend.announcements.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
    @csrf
    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200/60">
        <div class="space-y-4">
            <div>
                <label for="title" class="mb-1 block text-sm font-medium text-slate-700">Title *</label>
                <input type="text" name="title" id="title" value="{{ old('title') }}" required class="w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-2.5 focus:border-teal-500 focus:ring-teal-500">
                @error('title')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="body" class="mb-1 block text-sm font-medium text-slate-700">Content *</label>
                <textarea name="body" id="body" rows="6" required class="w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-2.5 focus:border-teal-500 focus:ring-teal-500">{{ old('body') }}</textarea>
                @error('body')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>
            @if(auth()->user()->tenant?->hasFeature('announcements_events'))
            <div>
                <label for="image" class="mb-1 block text-sm font-medium text-slate-700">Image (optional)</label>
                <div id="image-preview-container" class="mb-3 hidden">
                    <img id="image-preview" src="" alt="Image preview" class="max-h-48 w-full rounded-lg border-2 border-slate-200 object-cover shadow-sm">
                </div>
                <input type="file" name="image" id="image" accept=".png,.jpg,.jpeg,.gif,.webp" class="w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-2.5 file:mr-3 file:rounded-lg file:border-0 file:bg-teal-50 file:px-3 file:py-1.5 file:text-teal-700">
                <p class="mt-1 text-xs text-slate-500">Standard/Premium plan. PNG, JPG, GIF or WebP, max 2MB.</p>
                @error('image')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>
            @endif
            <div class="flex items-center gap-2">
                <input type="checkbox" name="is_published" id="is_published" value="1" {{ old('is_published', true) ? 'checked' : '' }} class="h-4 w-4 rounded border-slate-300 text-teal-600 focus:ring-teal-500">
                <label for="is_published" class="text-sm text-slate-700">Publish so residents can see it</label>
            </div>
            <div class="flex flex-col gap-1">
                <div class="flex items-center gap-2">
                    <input type="checkbox" name="notify_users_by_email" id="notify_users_by_email" value="1" {{ old('notify_users_by_email') ? 'checked' : '' }} class="h-4 w-4 rounded border-slate-300 text-teal-600 focus:ring-teal-500">
                    <label for="notify_users_by_email" class="text-sm text-slate-700">Notify tenant users by email</label>
                </div>
                <p class="ml-6 text-xs text-slate-500">Sends one email per approved user with an email address in this barangay (same mail settings as appointment notifications). Only runs when the announcement is published.</p>
            </div>
        </div>
    </div>
    <div class="flex gap-3">
        <button type="submit" class="rounded-xl bg-teal-600 px-4 py-2.5 font-medium text-white hover:bg-teal-700">Create announcement</button>
        <a href="{{ route('backend.announcements.index') }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 font-medium text-slate-700 hover:bg-slate-50">Cancel</a>
    </div>
</form>

@push('scripts')
<script>
(function() {
    const imageInput = document.getElementById('image');
    const previewContainer = document.getElementById('image-preview-container');
    const preview = document.getElementById('image-preview');
    
    if (imageInput && previewContainer && preview) {
        imageInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                if (file.size > 2 * 1024 * 1024) {
                    showToast('File size must be less than 2MB', 'error');
                    imageInput.value = '';
                    previewContainer.classList.add('hidden');
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    previewContainer.classList.remove('hidden');
                    preview.classList.add('ring-2', 'ring-teal-400');
                    setTimeout(() => {
                        preview.classList.remove('ring-teal-400');
                    }, 2000);
                };
                reader.readAsDataURL(file);
            } else {
                previewContainer.classList.add('hidden');
            }
        });
    }
})();
</script>
@endpush
@endsection
