@extends('backend.layouts.app')

@section('title', 'Edit Announcement')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-slate-800">Edit Announcement</h1>
</div>

<form action="{{ route('backend.announcements.update', $announcement) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
    @csrf
    @method('PUT')
    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200/60">
        <div class="space-y-4">
            <div>
                <label for="title" class="mb-1 block text-sm font-medium text-slate-700">Title <span class="text-rose-500">*</span></label>
                <input type="text" name="title" id="title" value="{{ old('title', $announcement->title) }}" required class="w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-2.5 focus:border-teal-500 focus:ring-teal-500">
                @error('title')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="body" class="mb-1 block text-sm font-medium text-slate-700">Content <span class="text-rose-500">*</span></label>
                <textarea name="body" id="body" rows="6" required class="w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-2.5 focus:border-teal-500 focus:ring-teal-500">{{ old('body', $announcement->body) }}</textarea>
                @error('body')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>
            @if(auth()->user()->tenant?->hasFeature('announcements_events'))
            <div>
                <label for="image" class="mb-1 block text-sm font-medium text-slate-700">Image</label>
                <div class="mb-3">
                    @if($announcement->image_path)
                        <div class="mb-2 flex items-center gap-3">
                            <img src="{{ $announcement->image_url }}" alt="Current image" id="current-image" class="max-h-24 rounded-lg border-2 border-slate-200 object-cover shadow-sm">
                            <label class="flex items-center gap-2 text-sm text-slate-600">
                                <input type="checkbox" name="remove_image" value="1" id="remove_image" class="rounded border-slate-300 text-teal-600 focus:ring-teal-500"> 
                                Remove image
                            </label>
                        </div>
                    @endif
                    <div id="new-image-preview-container" class="mb-2 hidden">
                        <p class="mb-1 text-xs font-medium text-slate-600">New image preview:</p>
                        <img id="new-image-preview" src="" alt="New image preview" class="max-h-48 w-full rounded-lg border-2 border-teal-300 object-cover shadow-sm ring-2 ring-teal-400">
                    </div>
                </div>
                <input type="file" name="image" id="image" accept=".png,.jpg,.jpeg,.gif,.webp" class="w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-2.5 file:mr-3 file:rounded-lg file:border-0 file:bg-teal-50 file:px-3 file:py-1.5 file:text-teal-700">
                <p class="mt-1 text-xs text-slate-500">PNG, JPG, GIF or WebP, max 2MB.</p>
                @error('image')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>
            @endif
            <div class="flex items-center gap-2">
                <input type="checkbox" name="is_published" id="is_published" value="1" {{ old('is_published', $announcement->is_published) ? 'checked' : '' }} class="h-4 w-4 rounded border-slate-300 text-teal-600 focus:ring-teal-500">
                <label for="is_published" class="text-sm text-slate-700">Published</label>
            </div>
        </div>
    </div>
    <div class="flex gap-3">
        <button type="submit" class="rounded-xl bg-teal-600 px-4 py-2.5 font-medium text-white hover:bg-teal-700">Save</button>
        <a href="{{ route('backend.announcements.index') }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 font-medium text-slate-700 hover:bg-slate-50">Cancel</a>
    </div>
</form>

@push('scripts')
<script>
(function() {
    const imageInput = document.getElementById('image');
    const previewContainer = document.getElementById('new-image-preview-container');
    const preview = document.getElementById('new-image-preview');
    const currentImage = document.getElementById('current-image');
    const removeCheckbox = document.getElementById('remove_image');
    
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
                    if (currentImage) {
                        currentImage.style.opacity = '0.5';
                    }
                    setTimeout(() => {
                        preview.classList.remove('ring-teal-400');
                    }, 2000);
                };
                reader.readAsDataURL(file);
            } else {
                previewContainer.classList.add('hidden');
                if (currentImage) {
                    currentImage.style.opacity = '1';
                }
            }
        });
        
        if (removeCheckbox && currentImage) {
            removeCheckbox.addEventListener('change', function() {
                if (this.checked) {
                    currentImage.style.opacity = '0.5';
                } else {
                    currentImage.style.opacity = '1';
                }
            });
        }
    }
})();
</script>
@endpush
@endsection
