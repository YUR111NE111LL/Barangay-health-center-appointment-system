@extends('backend.layouts.app')

@section('title', 'Customize web')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-slate-800">Customize web</h1>
    <p class="mt-1 text-slate-500">Customize how your barangay's site appears to residents and staff. Available on your current plan.</p>
</div>

<div id="customize-live-preview" class="mb-6 rounded-2xl border-2 border-dashed border-slate-200 bg-slate-50/50 p-4" style="--preview-primary: {{ e($tenant->primary_color ?? '#0d9488') }}; --preview-hover: {{ e($tenant->hover_color ?? '#14b8a6') }};" data-fallback-name="{{ e($tenant->name) }}">
    <p class="mb-2 text-xs font-medium uppercase tracking-wide text-slate-400">Live preview</p>
    <div class="preview-nav rounded-xl px-4 py-3 text-white shadow-sm" style="background: var(--preview-primary);">
        <div class="flex items-center justify-between">
            <span id="preview-site-name" class="font-semibold">{{ $tenant->site_name ?: $tenant->name }}</span>
            <div class="flex gap-1">
                <a href="#" class="preview-link rounded-lg px-2 py-1 text-sm text-white/90" style="--preview-hover: var(--preview-hover);">Link 1</a>
                <a href="#" class="preview-link rounded-lg px-2 py-1 text-sm text-white/90">Link 2</a>
            </div>
        </div>
    </div>
    <p class="mt-2 text-xs text-slate-500">Changes above update here as you type or choose options.</p>
</div>

@push('styles')
<style>
#customize-live-preview .preview-link:hover { background-color: var(--preview-hover) !important; }
#customize-live-preview.theme-modern .preview-nav { border-radius: 0 0 0.75rem 0.75rem; }
#customize-live-preview.theme-minimal .preview-nav { box-shadow: none; }
</style>
@endpush

<form action="{{ route('backend.customize-web.update') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
    @csrf
    @method('PUT')
    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200/60">
        <h2 class="mb-4 text-lg font-semibold text-slate-800">Branding</h2>
        <div class="space-y-4">
            <div>
                <label for="site_name" class="mb-1 block text-sm font-medium text-slate-700">Site name</label>
                <input type="text" name="site_name" id="site_name" value="{{ old('site_name', $tenant->site_name) }}" placeholder="{{ $tenant->name }}" class="w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-2.5 focus:border-teal-500 focus:ring-teal-500">
                <p class="mt-1 text-xs text-slate-500">Leave blank to use your barangay name ({{ $tenant->name }}).</p>
                @error('site_name')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="primary_color" class="mb-1 block text-sm font-medium text-slate-700">Primary color</label>
                <div class="flex items-center gap-3">
                    <input type="color" id="primary_color_swatch" value="{{ old('primary_color', $tenant->primary_color) ?: '#0d9488' }}" class="h-10 w-14 cursor-pointer rounded-lg border border-slate-300 p-1">
                    <input type="text" name="primary_color" id="primary_color" value="{{ old('primary_color', $tenant->primary_color) }}" placeholder="#0d9488" maxlength="20" class="w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-2.5 focus:border-teal-500 focus:ring-teal-500">
                </div>
                <p class="mt-1 text-xs text-slate-500">Hex color for navbar and buttons.</p>
                @error('primary_color')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="hover_color" class="mb-1 block text-sm font-medium text-slate-700">Hover color</label>
                <div class="flex items-center gap-3">
                    <input type="color" id="hover_color_swatch" value="{{ old('hover_color', $tenant->hover_color) ?: '#14b8a6' }}" class="h-10 w-14 cursor-pointer rounded-lg border border-slate-300 p-1">
                    <input type="text" name="hover_color" id="hover_color" value="{{ old('hover_color', $tenant->hover_color) }}" placeholder="#14b8a6" maxlength="20" class="w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-2.5 focus:border-teal-500 focus:ring-teal-500">
                </div>
                <p class="mt-1 text-xs text-slate-500">Hex color for nav and button hover states. Leave blank to use a default.</p>
                @error('hover_color')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="logo" class="mb-1 block text-sm font-medium text-slate-700">Logo</label>
                <div class="mb-3">
                    <div id="logo-preview-container" class="flex items-center gap-3">
                        @if($tenant->logo_path)
                            <img src="{{ str_contains($tenant->logo_path, 'cloudinary.com') ? $tenant->logo_path : asset('storage/' . $tenant->logo_path) }}" 
                                 alt="Current logo" 
                                 id="logo-preview"
                                 class="max-h-20 rounded-lg border-2 border-slate-200 object-contain shadow-sm">
                        @else
                            <div id="logo-preview" class="hidden max-h-20 rounded-lg border-2 border-slate-200 object-contain shadow-sm"></div>
                        @endif
                    </div>
                    @if($tenant->logo_path)
                        <label class="mt-2 flex items-center gap-2 text-sm text-slate-600">
                            <input type="checkbox" name="remove_logo" value="1" id="remove_logo" class="rounded border-slate-300 text-teal-600 focus:ring-teal-500"> 
                            Remove logo
                        </label>
                    @endif
                </div>
                <input type="file" name="logo" id="logo" accept=".png,.jpg,.jpeg,.gif,.webp" class="w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-2.5 file:mr-3 file:rounded-lg file:border-0 file:bg-teal-50 file:px-3 file:py-1.5 file:text-teal-700">
                <p class="mt-1 text-xs text-slate-500">PNG, JPG, GIF or WebP, max 2MB.</p>
                @error('logo')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="tagline" class="mb-1 block text-sm font-medium text-slate-700">Tagline</label>
                <input type="text" name="tagline" id="tagline" value="{{ old('tagline', $tenant->tagline) }}" placeholder="e.g. Serving our community" class="w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-2.5 focus:border-teal-500 focus:ring-teal-500">
                @error('tagline')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="footer_text" class="mb-1 block text-sm font-medium text-slate-700">Footer text</label>
                <textarea name="footer_text" id="footer_text" rows="2" maxlength="1000" class="w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-2.5 focus:border-teal-500 focus:ring-teal-500">{{ old('footer_text', $tenant->footer_text) }}</textarea>
                <p class="mt-1 text-xs text-slate-500">Shown at the bottom of resident and staff pages.</p>
                @error('footer_text')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>
        </div>
    </div>

    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200/60">
        <h2 class="mb-4 text-lg font-semibold text-slate-800">Design</h2>
        <div class="space-y-4">
            <div>
                <label for="theme" class="mb-1 block text-sm font-medium text-slate-700">Theme</label>
                <select name="theme" id="theme" class="w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-2.5 focus:border-teal-500 focus:ring-teal-500">
                    <option value="default" {{ old('theme', $tenant->theme ?? 'default') === 'default' ? 'selected' : '' }}>Default</option>
                    <option value="modern" {{ old('theme', $tenant->theme ?? 'default') === 'modern' ? 'selected' : '' }}>Modern</option>
                    <option value="minimal" {{ old('theme', $tenant->theme ?? 'default') === 'minimal' ? 'selected' : '' }}>Minimal</option>
                </select>
                <p class="mt-1 text-xs text-slate-500">Overall look of your site (colors, spacing, typography).</p>
                @error('theme')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>

            @if($tenant->hasFeature('full_web_customization'))
            <div>
                <label for="font_family" class="mb-1 block text-sm font-medium text-slate-700">Font (Premium)</label>
                <select name="font_family" id="font_family" class="w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-2.5 focus:border-teal-500 focus:ring-teal-500">
                    @foreach(\App\Models\Tenant::fontFamilyOptions() as $value => $label)
                    <option value="{{ $value }}" {{ old('font_family', $tenant->font_family ?? 'default') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-slate-500">Premium: change the site font. Applies to resident and staff pages.</p>
                @error('font_family')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>
            @endif

            @php
                $allowedLayouts = $tenant->getAllowedNavLayouts();
                $currentNavLayout = old('nav_layout', $tenant->nav_layout ?? 'navbar');
                $navLabels = ['navbar' => 'Nav bar', 'sidebar' => 'Sidebar', 'dropdown' => 'Dropdown'];
                $residentNavItems = ['dashboard' => 'My Appointments', 'book' => 'Book', 'announcements' => 'Announcements', 'events' => 'Events'];
                $savedOrder = old('nav_order', $tenant->nav_order ?? []);
                $navOrder = !empty($savedOrder) ? $savedOrder : array_keys($residentNavItems);
            @endphp
            <div>
                <span class="mb-2 block text-sm font-medium text-slate-700">Navigation style</span>
                <p class="mb-3 text-xs text-slate-500">Choose how the menu appears on resident and staff pages. @if(count($allowedLayouts) === 2)<strong>Standard:</strong> Nav bar or Dropdown. @elseif(count($allowedLayouts) >= 3)<strong>Premium:</strong> Full options. @endif</p>
                <div class="flex flex-wrap gap-3">
                    @foreach($allowedLayouts as $layout)
                    <label class="flex cursor-pointer items-center gap-2 rounded-xl border-2 px-4 py-3 transition {{ $currentNavLayout === $layout ? 'border-teal-500 bg-teal-50' : 'border-slate-200 bg-white hover:border-slate-300' }}">
                        <input type="radio" name="nav_layout" value="{{ $layout }}" {{ $currentNavLayout === $layout ? 'checked' : '' }} class="rounded-full border-slate-300 text-teal-600 focus:ring-teal-500">
                        <span class="font-medium text-slate-700">{{ $navLabels[$layout] ?? $layout }}</span>
                    </label>
                    @endforeach
                </div>
                @error('nav_layout')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>

            @if($tenant->hasFeature('full_web_customization'))
            <div>
                <span class="mb-2 block text-sm font-medium text-slate-700">Menu order (drag to reorder)</span>
                <p class="mb-2 text-xs text-slate-500">Premium: drag items to change the order of links on the resident menu.</p>
                <ul id="nav-order-list" class="space-y-2 rounded-xl border border-slate-200 bg-slate-50 p-2">
                    @foreach($navOrder as $key)
                        @if(isset($residentNavItems[$key]))
                        <li class="flex cursor-grab items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 shadow-sm active:cursor-grabbing" draggable="true" data-key="{{ $key }}">
                            <span class="text-slate-400" aria-hidden="true">≡</span>
                            <span class="font-medium text-slate-700">{{ $residentNavItems[$key] }}</span>
                            <input type="hidden" name="nav_order[]" value="{{ $key }}">
                        </li>
                        @endif
                    @endforeach
                </ul>
                @error('nav_order')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>
            @endif

            @if($tenant->hasFeature('full_web_customization'))
            <div>
                <label for="custom_css" class="mb-1 block text-sm font-medium text-slate-700">Custom CSS (Premium)</label>
                <textarea name="custom_css" id="custom_css" rows="12" maxlength="50000" placeholder="/* Override styles site-wide */&#10;.navbar { border-radius: 0.5rem; }" class="w-full rounded-xl border-slate-300 bg-slate-50 font-mono text-sm px-4 py-2.5 focus:border-teal-500 focus:ring-teal-500">{{ old('custom_css', $tenant->custom_css) }}</textarea>
                <p class="mt-1 text-xs text-slate-500">Premium: add your own CSS to alter the whole site. Use with care.</p>
                @error('custom_css')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>
            @endif
        </div>
    </div>

    <div class="flex gap-3">
        <button type="submit" class="rounded-xl bg-teal-600 px-4 py-2.5 font-medium text-white hover:bg-teal-700">Save changes</button>
        <a href="{{ route('backend.dashboard') }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 font-medium text-slate-700 hover:bg-slate-50">Cancel</a>
    </div>
</form>

@push('scripts')
<script>
// Logo preview
(function() {
    const logoInput = document.getElementById('logo');
    const logoPreview = document.getElementById('logo-preview');
    const removeLogoCheckbox = document.getElementById('remove_logo');
    
    if (logoInput && logoPreview) {
        logoInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Validate file size
                if (file.size > 2 * 1024 * 1024) {
                    showToast('File size must be less than 2MB', 'error');
                    logoInput.value = '';
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    if (logoPreview.tagName === 'IMG') {
                        logoPreview.src = e.target.result;
                        logoPreview.classList.add('ring-2', 'ring-teal-400');
                    } else {
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.id = 'logo-preview';
                        img.className = 'max-h-20 rounded-lg border-2 border-slate-200 object-contain shadow-sm ring-2 ring-teal-400';
                        img.alt = 'Logo preview';
                        logoPreview.parentNode.replaceChild(img, logoPreview);
                    }
                    setTimeout(() => {
                        logoPreview.classList.remove('ring-teal-400');
                    }, 2000);
                };
                reader.readAsDataURL(file);
            }
        });
        
        // Hide preview when remove is checked
        if (removeLogoCheckbox) {
            removeLogoCheckbox.addEventListener('change', function() {
                if (this.checked && logoPreview.tagName === 'IMG') {
                    logoPreview.style.opacity = '0.5';
                } else if (logoPreview.tagName === 'IMG') {
                    logoPreview.style.opacity = '1';
                }
            });
        }
    }
})();

document.getElementById('primary_color_swatch').addEventListener('input', function() {
    document.getElementById('primary_color').value = this.value;
});
document.getElementById('primary_color').addEventListener('input', function() {
    var swatch = document.getElementById('primary_color_swatch');
    if (/^#[0-9A-Fa-f]{6}$/.test(this.value) || /^#[0-9A-Fa-f]{3}$/.test(this.value)) {
        swatch.value = this.value;
    }
});
var hoverSwatch = document.getElementById('hover_color_swatch');
var hoverInput = document.getElementById('hover_color');
if (hoverSwatch && hoverInput) {
    hoverSwatch.addEventListener('input', function() { hoverInput.value = this.value; });
    hoverInput.addEventListener('input', function() {
        if (/^#[0-9A-Fa-f]{6}$/.test(this.value) || /^#[0-9A-Fa-f]{3}$/.test(this.value)) {
            hoverSwatch.value = this.value;
        }
    });
}

(function livePreview() {
    var preview = document.getElementById('customize-live-preview');
    var previewSiteName = document.getElementById('preview-site-name');
    var defaultName = (preview && preview.getAttribute('data-fallback-name')) || (previewSiteName ? previewSiteName.textContent.trim() : '') || 'Site name';
    var fontStacks = { 'default': '', 'inter': '"Inter", sans-serif', 'open-sans': '"Open Sans", sans-serif', 'roboto': '"Roboto", sans-serif', 'lora': '"Lora", serif', 'poppins': '"Poppins", sans-serif' };

    function updatePreview() {
        if (!preview) return;
        var siteName = document.getElementById('site_name');
        if (previewSiteName && siteName) {
            var val = (siteName.value || '').trim();
            previewSiteName.textContent = val || defaultName;
        }
        var primary = (document.getElementById('primary_color') || {}).value;
        if (primary && /^#[0-9A-Fa-f]{3,6}$/.test(primary)) {
            preview.style.setProperty('--preview-primary', primary);
        }
        var hover = (document.getElementById('hover_color') || {}).value;
        if (hover && /^#[0-9A-Fa-f]{3,6}$/.test(hover)) {
            preview.style.setProperty('--preview-hover', hover);
        }
        var theme = (document.getElementById('theme') || {}).value;
        preview.classList.remove('theme-default', 'theme-modern', 'theme-minimal');
        if (theme) preview.classList.add('theme-' + theme);

        var fontEl = document.getElementById('font_family');
        if (fontEl && fontStacks[fontEl.value]) {
            preview.style.fontFamily = fontStacks[fontEl.value] || '';
        } else if (fontEl) {
            preview.style.fontFamily = '';
        }
    }

    ['input', 'change'].forEach(function(ev) {
        ['site_name', 'primary_color', 'primary_color_swatch', 'hover_color', 'hover_color_swatch', 'theme', 'font_family'].forEach(function(id) {
            var el = document.getElementById(id);
            if (el) el.addEventListener(ev, updatePreview);
        });
    });
    document.querySelectorAll('input[name="nav_layout"]').forEach(function(radio) {
        radio.addEventListener('change', updatePreview);
    });
    updatePreview();
})();
(function() {
    var list = document.getElementById('nav-order-list');
    if (!list) return;
    var items = list.querySelectorAll('li[draggable="true"]');
    var dragged = null;
    items.forEach(function(li) {
        li.addEventListener('dragstart', function(e) {
            dragged = li;
            e.dataTransfer.setData('text/plain', li.getAttribute('data-key'));
            li.classList.add('opacity-50');
        });
        li.addEventListener('dragend', function() {
            li.classList.remove('opacity-50');
            dragged = null;
        });
        li.addEventListener('dragover', function(e) {
            e.preventDefault();
            if (dragged && dragged !== li) {
                var rect = li.getBoundingClientRect();
                var mid = rect.top + rect.height / 2;
                if (e.clientY < mid) {
                    list.insertBefore(dragged, li);
                } else {
                    list.insertBefore(dragged, li.nextSibling);
                }
            }
        });
    });
})();
</script>
@endpush
@endsection
