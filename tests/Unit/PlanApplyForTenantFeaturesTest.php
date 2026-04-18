<?php

use App\Models\Plan;

uses(Tests\TestCase::class);

test('apply for tenant feature labels omit plan-only flags not listed for public apply', function (): void {
    $plan = new Plan([
        'slug' => 'premium',
        'has_automated_approval' => true,
        'has_appointment_history' => true,
        'has_priority_support' => true,
        'has_data_export' => true,
        'has_email_notifications' => true,
        'has_inventory_tracking' => true,
        'has_monthly_reports' => true,
        'has_advanced_analytics' => true,
        'has_web_customization' => true,
        'has_full_web_customization' => true,
        'has_announcements_events' => true,
    ]);

    $labels = $plan->applyForTenantFeatureLabels();

    expect($labels)->not->toContain(__('Automated approval'))
        ->not->toContain(__('Priority support'))
        ->not->toContain(__('Data export'))
        ->toContain(__('Appointment history'))
        ->toContain(__('Email notifications'))
        ->toContain(__('Inventory tracking'))
        ->toContain(__('Custom roles up to 10'));
});

test('apply for tenant falls back to full enabled labels when config list is empty', function (): void {
    config(['bhcas.apply_for_tenant_feature_columns' => []]);

    $plan = new Plan([
        'slug' => 'basic',
        'has_automated_approval' => true,
        'has_appointment_history' => false,
    ]);

    expect($plan->applyForTenantFeatureLabels())->toContain(__('Automated approval'));
});
