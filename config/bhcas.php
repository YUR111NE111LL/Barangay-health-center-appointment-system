<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Identity
    |--------------------------------------------------------------------------
    */
    'name' => 'Barangay Health Center Appointment System',
    'acronym' => 'BHCAS',
    'subtitle' => 'Multi-Tenant SaaS Web Application',

    /*
    |--------------------------------------------------------------------------
    | Login / branding logo
    |--------------------------------------------------------------------------
    | Path under public/ for the logo shown on the login page (e.g. images/bhcs-logo.png).
    | Leave null to show app name text only.
    */
    'logo_path' => env('BHCAS_LOGO_PATH', 'images/bhcs-logo.png'),

    /*
    |--------------------------------------------------------------------------
    | Login page background
    |--------------------------------------------------------------------------
    | Outer background of the login page. Options: "teal" (gradient, default),
    | "slate" (light gray), or "custom" with login_background_color set to a hex.
    */
    'login_background' => env('BHCAS_LOGIN_BACKGROUND', 'teal'),
    'login_background_color' => env('BHCAS_LOGIN_BACKGROUND_COLOR', null),

    /*
    |--------------------------------------------------------------------------
    | System Admin Support Contact
    |--------------------------------------------------------------------------
    */
    'support' => [
        'phone' => env('BHCAS_SUPPORT_PHONE', '+63 900 000 0000'),
        'email' => env('BHCAS_SUPPORT_EMAIL', 'support@bhcas.local'),
        'hours' => env('BHCAS_SUPPORT_HOURS', 'Mon–Fri, 9:00 AM - 5:00 PM'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Final Project Requirements
    |--------------------------------------------------------------------------
    | Used by the requirements page and for feature checks.
    */
    'requirements' => [
        'multi_tenant' => [
            'label' => 'Multi-Tenant',
            'description' => 'Multiple tenants (barangays/health centers) on a single application.',
            'implementation' => 'Single-database multi-tenancy using tenant_id; Tenant & Plan models; TenantScope and BelongsToTenant trait.',
        ],
        'multi_user_rbac' => [
            'label' => 'Multi-User with RBAC',
            'description' => 'Multiple user types with role-based access control.',
            'implementation' => 'Roles: Super Admin, Health Center Admin, Nurse, Staff, Resident. User.role + Spatie Laravel Permission.',
        ],
        'customizable' => [
            'label' => 'Customizable (design, functions)',
            'description' => 'Tenants can customize look and behavior.',
            'implementation' => 'Plan-based feature flags; per-tenant services; @planFeature Blade directive for UI.',
        ],
        'pricing_model' => [
            'label' => 'Pricing Model (Basic, Pro, Ultimate)',
            'description' => 'Subscription tiers with different capabilities.',
            'implementation' => 'Plans: Basic (50 appts/month), Standard (300), Premium (unlimited). Limits enforced in controllers.',
        ],
        'support_ota' => [
            'label' => 'Support and Updates (OTA updates)',
            'description' => 'Ongoing support and over-the-air updates.',
            'implementation' => 'Single codebase for all tenants; central deployment. Future: version checks, in-app release notes.',
        ],
        'tenancy' => [
            'label' => 'Tenancy (domain/file system)',
            'description' => 'How tenants are identified and isolated.',
            'implementation' => 'Current: single domain + tenant_id. Optional: subdomain/domain-per-tenant; file storage by tenant_id.',
        ],
        'tamper_free' => [
            'label' => 'Tamper Free & Data Isolation',
            'description' => 'Data cannot be mixed or altered across tenants.',
            'implementation' => 'TenantScope on all tenant-scoped queries; Super Admin uses withoutGlobalScope only where intended; unique (tenant_id, email).',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Pricing Tiers (Slide → BHCAS mapping)
    |--------------------------------------------------------------------------
    */
    'pricing_tiers' => [
        ['slide' => 'Basic', 'plan' => 'Basic', 'features' => 'Up to 50 appts/month, manual approval, email notifications, basic reports'],
        ['slide' => 'Pro', 'plan' => 'Standard', 'features' => 'Up to 300 appts/month, automated approval option, appointment history, monthly reports'],
        ['slide' => 'Ultimate', 'plan' => 'Premium', 'features' => 'Unlimited appts, full analytics, inventory tracking, priority support, data export'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Plan-based RBAC: permissions allowed per plan slug
    |--------------------------------------------------------------------------
    | Tenant RBAC is per-tenant and based on the plan they have. Permissions
    | are stored in tenant_role_permissions (tenant_id, role_name, permission_name)
    | and apply only to that tenant—no overlap with other tenants. When Super
    | Admin configures RBAC for a tenant, only these permissions can be assigned.
    | Use '*' for a plan to allow all permissions.
    */
    'plan_permissions' => [
        'basic' => [
            'view appointments',
            'encode appointments',
            'approve appointments',
            'view reports',
            'book appointments',
            'manage schedules',
        ],
        'standard' => [
            'view appointments',
            'encode appointments',
            'approve appointments',
            'view reports',
            'book appointments',
            'manage schedules',
            'record notes',
            'update visit status',
        ],
        'premium' => ['*'], // all permissions
    ],

    /*
    |--------------------------------------------------------------------------
    | Permissions that can be assigned to the Resident role (tenant RBAC only)
    |--------------------------------------------------------------------------
    | Resident role is separate from Staff/Nurse/Admin. Only these permissions
    | are offered when editing Resident in Super Admin → Tenant RBAC.
    */
    'resident_role_permissions' => [
        'book appointments',
    ],

    /*
    |--------------------------------------------------------------------------
    | Daily appointment limit per tenant (per date)
    |--------------------------------------------------------------------------
    | Max number of appointments per tenant per day. Null = no limit (or use plan).
    */
    'daily_appointment_limit' => env('BHCAS_DAILY_APPOINTMENT_LIMIT', 30),

    /*
    |--------------------------------------------------------------------------
    | Tenant Domain Root
    |--------------------------------------------------------------------------
    | Used to auto-generate tenant domains from the barangay value on the
    | Super Admin "Add Tenant" screen (e.g. {barangay}.localhost).
    | Set BHCAS_TENANT_DOMAIN_ROOT to your real domain root in production.
    */
    'tenant_domain_root' => env('BHCAS_TENANT_DOMAIN_ROOT', 'localhost'),

];
