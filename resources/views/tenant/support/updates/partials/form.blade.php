<div class="grid gap-4 sm:grid-cols-2">
    <div class="sm:col-span-2">
        <label for="title" class="mb-1 block text-sm font-medium text-slate-700">Title <span class="text-rose-500">*</span></label>
        <input type="text" name="title" id="title" value="{{ old('title', $note->title ?? '') }}" class="w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-2.5" required>
    </div>
    <div>
        <label for="type" class="mb-1 block text-sm font-medium text-slate-700">Type <span class="text-rose-500">*</span></label>
        <select name="type" id="type" class="w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-2.5" required>
            @foreach(['feature', 'fix', 'maintenance', 'security'] as $type)
                <option value="{{ $type }}" {{ old('type', $note->type ?? 'feature') === $type ? 'selected' : '' }}>{{ ucfirst($type) }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="version" class="mb-1 block text-sm font-medium text-slate-700">Version <span class="text-rose-500">*</span></label>
        <input type="text" name="version" id="version" value="{{ old('version', $note->version ?? '') }}" class="w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-2.5" required>
    </div>
    <div class="sm:col-span-2">
        <label for="summary" class="mb-1 block text-sm font-medium text-slate-700">Summary <span class="text-rose-500">*</span></label>
        <input type="text" name="summary" id="summary" value="{{ old('summary', $note->summary ?? '') }}" class="w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-2.5" required>
    </div>
    <div class="sm:col-span-2">
        <label for="content" class="mb-1 block text-sm font-medium text-slate-700">Details <span class="text-rose-500">*</span></label>
        <textarea name="content" id="content" rows="6" class="w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-2.5" required>{{ old('content', $note->content ?? '') }}</textarea>
    </div>
    <div>
        <label for="published_at" class="mb-1 block text-sm font-medium text-slate-700">Published at <span class="text-rose-500">*</span></label>
        <input type="datetime-local" name="published_at" id="published_at" value="{{ old('published_at', isset($note) && $note->published_at ? $note->published_at->format('Y-m-d\\TH:i') : now()->format('Y-m-d\\TH:i')) }}" class="w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-2.5" required>
    </div>
    <div class="flex items-end">
        <label class="inline-flex items-center gap-2 text-sm text-slate-700">
            <input type="checkbox" name="is_pinned" value="1" {{ old('is_pinned', $note->is_pinned ?? false) ? 'checked' : '' }} class="rounded border-slate-300 text-teal-600">
            Pin this update
        </label>
    </div>
</div>
