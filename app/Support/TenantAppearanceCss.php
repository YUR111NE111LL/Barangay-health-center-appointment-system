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

        $sidebarDensity = $settings['sidebar_density'] ?? 'comfortable';
        if ($sidebarDensity === 'compact') {
            $parts[] = 'body.layout-sidebar .sidebar-drawer nav a, body.layout-sidebar .sidebar-drawer nav button { padding-top: 0.45rem !important; padding-bottom: 0.45rem !important; font-size: 0.82rem !important; }';
            $parts[] = 'body.layout-sidebar .sidebar-drawer nav { gap: 0.2rem !important; }';
        }

        $sidebarSurface = $settings['sidebar_surface'] ?? 'solid';
        $sidebarRules = match ($sidebarSurface) {
            'soft' => 'body.layout-sidebar .sidebar-drawer { background: linear-gradient(180deg, rgb(13 148 136 / 0.95) 0%, rgb(15 118 110 / 0.95) 100%) !important; }',
            'glass' => 'body.layout-sidebar .sidebar-drawer { background: rgb(13 148 136 / 0.82) !important; backdrop-filter: blur(6px); -webkit-backdrop-filter: blur(6px); }',
            default => null,
        };
        if ($sidebarRules !== null) {
            $parts[] = $sidebarRules;
        }

        $cardStyle = $settings['card_style'] ?? 'default';
        $cardRules = match ($cardStyle) {
            'soft' => 'main .rounded-2xl.bg-white, main .rounded-xl.bg-white { background-color: rgb(248 250 252 / 0.92) !important; box-shadow: 0 1px 2px rgb(15 23 42 / 0.06) !important; }',
            'outlined' => 'main .rounded-2xl.bg-white, main .rounded-xl.bg-white { box-shadow: none !important; border: 1px solid rgb(148 163 184 / 0.35) !important; }',
            default => null,
        };
        if ($cardRules !== null) {
            $parts[] = $cardRules;
        }

        $buttonShape = $settings['button_shape'] ?? 'rounded';
        $buttonRules = match ($buttonShape) {
            'pill' => 'button, input[type=submit], input[type=button], a.rounded-xl, a.rounded-lg { border-radius: 9999px !important; }',
            'square' => 'button, input[type=submit], input[type=button], a.rounded-xl, a.rounded-lg { border-radius: 0.5rem !important; }',
            default => null,
        };
        if ($buttonRules !== null) {
            $parts[] = $buttonRules;
        }

        return implode("\n", array_filter($parts));
    }
}
