@php
    $medicineModel = $medicine ?? null;
    $isFreeSelected = old('is_free', $medicineModel === null ? '1' : ($medicineModel->is_free ? '1' : '0'));
@endphp
<div class="space-y-4">
    <div>
        <label for="name" class="mb-1 block text-sm font-medium text-slate-700">Name <span class="text-rose-500">*</span></label>
        <input type="text" name="name" id="name" value="{{ old('name', $medicineModel?->name) }}" required class="w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-2.5 focus:border-teal-500 focus:ring-teal-500">
        @error('name')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="description" class="mb-1 block text-sm font-medium text-slate-700">Description</label>
        <textarea name="description" id="description" rows="3" class="w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-2.5 focus:border-teal-500 focus:ring-teal-500">{{ old('description', $medicineModel?->description) }}</textarea>
        @error('description')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="quantity" class="mb-1 block text-sm font-medium text-slate-700">Quantity in stock <span class="text-rose-500">*</span></label>
        <input type="number" name="quantity" id="quantity" value="{{ old('quantity', $medicineModel?->quantity ?? 0) }}" min="0" max="999999" required class="w-full max-w-xs rounded-xl border-slate-300 bg-slate-50 px-4 py-2.5 focus:border-teal-500 focus:ring-teal-500">
        @error('quantity')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="is_free" class="mb-1 block text-sm font-medium text-slate-700">Resident pricing <span class="text-rose-500">*</span></label>
        <select name="is_free" id="is_free" class="w-full max-w-md rounded-xl border-slate-300 bg-slate-50 px-4 py-2.5 focus:border-teal-500 focus:ring-teal-500">
            <option value="1" @selected($isFreeSelected === '1')>Free — no charge to residents</option>
            <option value="0" @selected($isFreeSelected === '0')>Priced — charge per unit ({{ config('bhcas.currency_symbol', '₱') }})</option>
        </select>
        <p class="mt-1 text-xs text-slate-500">Inventory and acquisition totals use this when residents acquire stock.</p>
        @error('is_free')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="price_per_unit" class="mb-1 block text-sm font-medium text-slate-700">Price per unit</label>
        <div class="flex max-w-xs items-center gap-2">
            <span class="text-sm text-slate-600">{{ config('bhcas.currency_symbol', '₱') }}</span>
            <input type="number" name="price_per_unit" id="price_per_unit" value="{{ old('price_per_unit', $medicineModel?->price_per_unit) }}" min="0.01" step="0.01" max="999999.99" class="w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-2.5 focus:border-teal-500 focus:ring-teal-500" placeholder="0.00">
        </div>
        <p class="mt-1 text-xs text-slate-500">Required when &ldquo;Priced&rdquo; is selected. Leave empty for free supplies.</p>
        @error('price_per_unit')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="image" class="mb-1 block text-sm font-medium text-slate-700">Image (optional)</label>
        @if($medicineModel?->image_path)
            <p class="mb-2 text-xs text-slate-500">Current image shown below. Upload a new file to replace it.</p>
            <img src="{{ $medicineModel->image_url }}" alt="" class="mb-3 h-24 w-24 rounded-lg object-cover ring-1 ring-slate-200/80" />
        @endif
        <input type="file" name="image" id="image" accept=".png,.jpg,.jpeg,.gif,.webp" class="w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-2.5 file:mr-3 file:rounded-lg file:border-0 file:bg-teal-50 file:px-3 file:py-1.5 file:text-teal-700">
        <p class="mt-1 text-xs text-slate-500">PNG, JPG, GIF or WebP, max 2MB.</p>
        @error('image')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
    </div>
</div>
