<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class TenantCustomCssController extends Controller
{
    /**
     * Return tenant branding + custom CSS as one stylesheet (hover color, font, Premium custom CSS).
     * Keeps Blade layouts free of inline <style> and style="..." to avoid CSS linter false positives.
     */
    public function __invoke(): Response
    {
        $tenant = auth()->user()?->tenant;
        $parts = [];

        if ($tenant) {
            $hoverColor = $tenant->getHoverColor();
            $parts[] = ":root { --tenant-hover-color: {$hoverColor}; }";
            $parts[] = '.tenant-brand-nav a:hover, .tenant-brand-nav button:hover { background-color: var(--tenant-hover-color, #14b8a6) !important; }';
            if ($tenant->getFontFamilyCss()) {
                $fontCss = $tenant->getFontFamilyCss();
                $parts[] = "body { font-family: {$fontCss}; }";
            }
            if ($tenant->hasFeature('full_web_customization') && ! empty(trim($tenant->custom_css ?? ''))) {
                $parts[] = str_replace('</style>', '\3C/style>', $tenant->custom_css);
            }
        }

        $css = implode("\n", $parts);

        return response($css, 200, [
            'Content-Type' => 'text/css; charset=UTF-8',
            'Cache-Control' => 'private, max-age=60',
        ]);
    }
}
