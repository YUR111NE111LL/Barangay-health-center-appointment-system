<?php

use App\Models\Tenant;

test('merged appearance settings fill defaults', function (): void {
    $tenant = Tenant::make([]);
    expect($tenant->mergedAppearanceSettings()['logo_shape'])->toBe('circle');
});

test('appearance main width maps content_width for staff and resident', function (): void {
    $tenant = Tenant::make(['appearance_settings' => ['content_width' => 'narrow']]);
    expect($tenant->appearanceMainMaxWidthClass('staff'))->toBe('max-w-5xl');
    expect($tenant->appearanceMainMaxWidthClass('resident'))->toBe('max-w-3xl');

    $tenantWide = Tenant::make(['appearance_settings' => ['content_width' => 'wide']]);
    expect($tenantWide->appearanceMainMaxWidthClass('staff'))->toBe('max-w-screen-2xl');
    expect($tenantWide->appearanceMainMaxWidthClass('resident'))->toBe('max-w-6xl');
});

test('brand logo img class reflects logo_shape', function (): void {
    $circle = Tenant::make(['appearance_settings' => ['logo_shape' => 'circle']]);
    expect($circle->brandLogoImgClass())->toContain('rounded-full');

    $square = Tenant::make(['appearance_settings' => ['logo_shape' => 'square']]);
    expect($square->brandLogoImgClass())->toContain('rounded-none');
});

test('logo url resolves cloudinary and storage paths', function (): void {
    $cloud = Tenant::make(['logo_path' => 'https://res.cloudinary.com/demo/image/upload/v1/sample.png']);
    expect($cloud->logoUrl())->toBe('https://res.cloudinary.com/demo/image/upload/v1/sample.png');

    $local = Tenant::make(['logo_path' => 'logos/x.png']);
    expect($local->logoUrl())->toContain('storage/logos/x.png');
});
