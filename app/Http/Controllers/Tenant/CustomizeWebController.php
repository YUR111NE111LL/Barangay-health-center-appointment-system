<?php

namespace App\Http\Controllers\Tenant;

use App\Events\TenantCustomizationUpdated;
use App\Http\Controllers\Controller;
use App\Services\CloudinaryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\File;
use Illuminate\View\View;

/**
 * Plan-based web customization for Barangay Admin only.
 * Only available when the tenant's plan has the web_customization feature.
 */
class CustomizeWebController extends Controller
{
    public function edit(): View
    {
        $tenant = auth()->user()->tenant;
        if (! $tenant || ! $tenant->hasFeature('web_customization')) {
            abort(403, 'Your plan does not include web customization.');
        }

        return view('tenant.customize-web.edit', compact('tenant'));
    }

    public function update(Request $request): RedirectResponse
    {
        $tenant = auth()->user()->tenant;
        if (! $tenant || ! $tenant->hasFeature('web_customization')) {
            abort(403, 'Your plan does not include web customization.');
        }

        $allowedLayouts = $tenant->getAllowedNavLayouts();
        $rules = [
            'site_name' => ['nullable', 'string', 'max:255'],
            'primary_color' => ['nullable', 'string', 'max:20', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'hover_color' => ['nullable', 'string', 'max:20', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'logo' => ['nullable', File::types(['png', 'jpg', 'jpeg', 'gif', 'webp'])->max(2048)],
            'tagline' => ['nullable', 'string', 'max:255'],
            'footer_text' => ['nullable', 'string', 'max:1000'],
            'theme' => ['nullable', 'string', 'in:default,modern,minimal'],
            'nav_layout' => ['nullable', 'string', 'in:'.implode(',', $allowedLayouts)],
        ];
        if ($tenant->hasFeature('full_web_customization')) {
            $rules['custom_css'] = ['nullable', 'string', 'max:50000'];
            $rules['font_family'] = ['nullable', 'string', 'in:default,inter,open-sans,roboto,lora,poppins'];
            $rules['nav_order'] = ['nullable', 'array'];
            $rules['nav_order.*'] = ['string', 'in:'.implode(',', \App\Models\Tenant::residentNavItemKeys())];
            $rules['appearance_content_width'] = ['nullable', 'string', 'in:standard,narrow,wide'];
            $rules['appearance_logo_shape'] = ['nullable', 'string', 'in:circle,rounded,square'];
            $rules['appearance_page_background'] = ['nullable', 'string', 'in:default,soft_gray,warm,cool'];
            $rules['appearance_accent_style'] = ['nullable', 'string', 'in:default,flat,elevated'];
        }
        $validated = $request->validate($rules);

        $data = [
            'site_name' => $validated['site_name'] ?? null,
            'primary_color' => $validated['primary_color'] ?? null,
            'hover_color' => $validated['hover_color'] ?? null,
            'tagline' => $validated['tagline'] ?? null,
            'footer_text' => $validated['footer_text'] ?? null,
            'theme' => $validated['theme'] ?? 'default',
            'nav_layout' => in_array($validated['nav_layout'] ?? 'navbar', $allowedLayouts, true) ? ($validated['nav_layout'] ?? 'navbar') : 'navbar',
        ];
        if ($tenant->hasFeature('full_web_customization')) {
            $data['custom_css'] = ! empty(trim($validated['custom_css'] ?? '')) ? trim($validated['custom_css']) : null;
            $data['font_family'] = in_array($validated['font_family'] ?? 'default', ['default', 'inter', 'open-sans', 'roboto', 'lora', 'poppins'], true) ? ($validated['font_family'] ?? 'default') : 'default';
            if (isset($validated['nav_order']) && is_array($validated['nav_order'])) {
                $keys = \App\Models\Tenant::residentNavItemKeys();
                $data['nav_order'] = array_values(array_filter($validated['nav_order'], fn ($k) => in_array($k, $keys, true)));
                if (count($data['nav_order']) !== count($keys)) {
                    $data['nav_order'] = array_values(array_intersect($keys, $data['nav_order'])) ?: $keys;
                }
            }
            $cw = $validated['appearance_content_width'] ?? 'standard';
            $ls = $validated['appearance_logo_shape'] ?? 'circle';
            $pb = $validated['appearance_page_background'] ?? 'default';
            $ac = $validated['appearance_accent_style'] ?? 'default';
            $data['appearance_settings'] = [
                'content_width' => in_array($cw, ['standard', 'narrow', 'wide'], true) ? $cw : 'standard',
                'logo_shape' => in_array($ls, ['circle', 'rounded', 'square'], true) ? $ls : 'circle',
                'page_background' => in_array($pb, ['default', 'soft_gray', 'warm', 'cool'], true) ? $pb : 'default',
                'accent_style' => in_array($ac, ['default', 'flat', 'elevated'], true) ? $ac : 'default',
            ];
        }

        if ($request->hasFile('logo')) {
            // Delete old logo from Cloudinary if exists
            if ($tenant->logo_path) {
                if (str_contains($tenant->logo_path, 'cloudinary.com')) {
                    $publicId = basename(parse_url($tenant->logo_path, PHP_URL_PATH), '.'.pathinfo($tenant->logo_path, PATHINFO_EXTENSION));
                    CloudinaryService::delete($publicId, 'image');
                } else {
                    // Legacy local storage cleanup
                    Storage::disk('public')->delete($tenant->logo_path);
                }
            }

            // Upload to Cloudinary
            $uploadResult = CloudinaryService::uploadImage(
                $request->file('logo'),
                "tenant_logos/{$tenant->id}",
                [
                    'transformation' => [
                        'width' => 500,
                        'height' => 500,
                        'crop' => 'limit',
                        'quality' => 'auto',
                        'format' => 'auto',
                    ],
                ]
            );

            if ($uploadResult) {
                $data['logo_path'] = $uploadResult['secure_url'];
            } else {
                return back()->withInput()->withErrors(['logo' => 'Failed to upload logo. Please try again.']);
            }
        }

        if ($request->boolean('remove_logo')) {
            if ($tenant->logo_path) {
                if (str_contains($tenant->logo_path, 'cloudinary.com')) {
                    $publicId = basename(parse_url($tenant->logo_path, PHP_URL_PATH), '.'.pathinfo($tenant->logo_path, PATHINFO_EXTENSION));
                    CloudinaryService::delete($publicId, 'image');
                } else {
                    // Legacy local storage cleanup
                    Storage::disk('public')->delete($tenant->logo_path);
                }
            }
            $data['logo_path'] = null;
        }

        $tenant->update($data);

        TenantCustomizationUpdated::dispatch($tenant);

        return redirect()->route('backend.customize-web.edit')->with('success', 'Web customization saved. Other users will see changes in real time.');
    }
}
