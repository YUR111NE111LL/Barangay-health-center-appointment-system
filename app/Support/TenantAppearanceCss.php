<?php

namespace App\Support;

use App\Models\Tenant;

/**
 * Builds safe, scoped CSS from Premium "appearance" settings (no raw user CSS required).
 */
final class TenantAppearanceCss
{
    public static function toCssString(Tenant $tenant): string
    {
        if (! $tenant->hasFeature('full_web_customization')) {
            return '';
        }

        $settings = $tenant->mergedAppearanceSettings();
        $parts = [];

        $bg = $settings['page_background'] ?? 'default';
        $bgRules = match ($bg) {
            'soft_gray' => 'background-color: #f8fafc !important;',
            'warm' => 'background-color: #fffbeb !important;',
            'cool' => 'background-color: #f0f9ff !important;',
            default => null,
        };
        if ($bgRules !== null) {
            $parts[] = 'body { '.$bgRules.' }';
        }

        $accent = $settings['accent_style'] ?? 'default';
        $navRules = match ($accent) {
            'flat' => '.tenant-brand-nav { box-shadow: none !important; border-bottom-width: 1px; }',
            'elevated' => '.tenant-brand-nav { box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.12), 0 4px 6px -4px rgb(0 0 0 / 0.1) !important; }',
            default => null,
        };
        if ($navRules !== null) {
            $parts[] = $navRules;
        }

        return implode("\n", array_filter($parts));
    }
}
