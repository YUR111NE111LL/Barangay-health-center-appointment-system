<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\CentralConnection;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\TenantRun;
use Stancl\Tenancy\Database\Models\Domain;
use Stancl\Tenancy\Events;

class Tenant extends Model implements TenantWithDatabase
{
    use CentralConnection;
    use HasDatabase;
    use HasFactory;
    use TenantRun;

    /**
     * Ensure Stancl tenancy events are dispatched from this custom tenant model.
     *
     * Without this, Stancl will not fire `TenantCreated`/`TenantDeleted`, and your
     * DB provisioning jobs (`CreateDatabase`, `MigrateDatabase`) won't run.
     */
    protected $dispatchesEvents = [
        'saving' => Events\SavingTenant::class,
        'saved' => Events\TenantSaved::class,
        'creating' => Events\CreatingTenant::class,
        'created' => Events\TenantCreated::class,
        'updating' => Events\UpdatingTenant::class,
        'updated' => Events\TenantUpdated::class,
        'deleting' => Events\DeletingTenant::class,
        'deleted' => Events\TenantDeleted::class,
    ];

    /**
     * Tenants are not scoped by tenant_id (they define the tenant).
     * Do not use BelongsToTenant on this model.
     */

    // No automatic tenant seeding here; real barangay data is added manually.

    protected $fillable = [
        'data',
        'plan_id',
        'name',
        'slug',
        'site_name',
        'primary_color',
        'hover_color',
        'logo_path',
        'tagline',
        'footer_text',
        'theme',
        'font_family',
        'custom_css',
        'appearance_settings',
        'nav_layout',
        'nav_order',
        'address',
        'contact_number',
        'email',
        'is_active',
        'subscription_ends_at',
        'expiry_notification_sent_at',
        'grace_period_ends_at',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'is_active' => 'boolean',
            'subscription_ends_at' => 'datetime',
            'expiry_notification_sent_at' => 'datetime',
            'grace_period_ends_at' => 'datetime',
            'nav_order' => 'array',
            'appearance_settings' => 'array',
        ];
    }

    public function getTenantKeyName(): string
    {
        return 'id';
    }

    public function getTenantKey(): mixed
    {
        return $this->getAttribute($this->getTenantKeyName());
    }

    /**
     * Stancl Tenancy internal keys prefix used by the database bootstrapper.
     *
     * This project stores internal tenancy values inside the `data` column
     * (e.g. `data.tenancy_db_name`), so we only need the prefix for Stancl.
     */
    public static function internalPrefix(): string
    {
        return 'tenancy_';
    }

    public function getInternal(string $key): mixed
    {
        $data = $this->getAttribute('data') ?? [];

        return $data['tenancy_'.$key] ?? null;
    }

    public function setInternal(string $key, mixed $value): static
    {
        $data = $this->getAttribute('data') ?? [];
        $data['tenancy_'.$key] = $value;
        $this->setAttribute('data', $data);

        return $this;
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function domains(): HasMany
    {
        return $this->hasMany(Domain::class);
    }

    /** Primary domain (first domain) used for tenant identification. */
    public function getPrimaryDomainAttribute(): ?string
    {
        $domain = $this->domains()->first();

        return $domain?->domain;
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'tenant_id');
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class, 'tenant_id');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'tenant_id');
    }

    public function announcements(): HasMany
    {
        return $this->hasMany(Announcement::class, 'tenant_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class, 'tenant_id');
    }

    public function supportTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class, 'tenant_id');
    }

    public function releaseNotes(): HasMany
    {
        return $this->hasMany(ReleaseNote::class, 'tenant_id');
    }

    /**
     * Whether the tenant can still create appointments this month (under plan limit).
     */
    public function canExceedAppointmentLimit(): bool
    {
        $plan = $this->plan;
        if (! $plan || $plan->isUnlimited()) {
            return true;
        }
        $count = $this->appointmentCountForMonth(now()->year, now()->month);

        return $count < $plan->max_appointments_per_month;
    }

    /**
     * Count appointments (all statuses) for the given month.
     */
    public function appointmentCountForMonth(int $year, int $month): int
    {
        return $this->appointments()
            ->whereYear('scheduled_date', $year)
            ->whereMonth('scheduled_date', $month)
            ->count();
    }

    /**
     * Check if the tenant's plan has a feature (e.g. 'inventory', 'advanced_analytics').
     */
    public function hasFeature(string $feature): bool
    {
        $plan = $this->plan;
        if (! $plan) {
            return false;
        }
        $map = [
            'inventory' => 'has_inventory_tracking',
            'advanced_analytics' => 'has_advanced_analytics',
            'automated_approval' => 'has_automated_approval',
            'monthly_reports' => 'has_monthly_reports',
            'data_export' => 'has_data_export',
            'priority_support' => 'has_priority_support',
            'web_customization' => 'has_web_customization',
            'full_web_customization' => 'has_full_web_customization',
            'announcements_events' => 'has_announcements_events',
        ];

        $column = $map[$feature] ?? $feature;
        if (! in_array($column, [
            'has_inventory_tracking', 'has_advanced_analytics', 'has_automated_approval',
            'has_monthly_reports', 'has_data_export', 'has_priority_support', 'has_appointment_history', 'has_email_notifications', 'has_web_customization', 'has_full_web_customization', 'has_announcements_events',
        ], true)) {
            return false;
        }

        return (bool) $plan->{$column};
    }

    /**
     * Get remaining appointment slots for the current month (null = unlimited).
     */
    public function remainingAppointmentsThisMonth(): ?int
    {
        $plan = $this->plan;
        if (! $plan || $plan->isUnlimited()) {
            return null;
        }
        $count = $this->appointmentCountForMonth(now()->year, now()->month);

        return max(0, $plan->max_appointments_per_month - $count);
    }

    /**
     * Max users allowed by this tenant's plan (0 = unlimited).
     */
    public function maxUsersFromPlan(): int
    {
        $plan = $this->plan;
        if (! $plan) {
            return 0;
        }

        return (int) $plan->max_users;
    }

    /**
     * Whether the tenant can add another user (under plan limit).
     */
    public function canAddUser(): bool
    {
        $max = $this->maxUsersFromPlan();
        if ($max === 0) {
            return true;
        }

        return $this->users()->count() < $max;
    }

    /**
     * Display name for the tenant's web (custom site name or default name).
     */
    public function getDisplayName(): string
    {
        return $this->site_name ?: $this->name;
    }

    /**
     * Human-friendly barangay label for emails (same idea as login/register: subdomain from domain,
     * then public site name — not the internal tenant record name alone).
     */
    public function barangayDisplayName(): string
    {
        $domain = $this->relationLoaded('domains')
            ? $this->domains->first()?->domain
            : $this->domains()->first()?->domain;

        if (is_string($domain) && $domain !== '') {
            return Str::of($domain)->before('.')->replace('-', ' ')->title()->toString();
        }

        if (filled($this->site_name)) {
            return $this->site_name;
        }

        return (string) ($this->name ?? '');
    }

    /**
     * Primary color for branding (hex), or default Bootstrap primary.
     */
    public function getPrimaryColor(): string
    {
        return $this->primary_color ?: '#0d6efd';
    }

    /**
     * Hover color for nav links and buttons (hex), or lightened primary.
     */
    public function getHoverColor(): string
    {
        return $this->hover_color ?: '#0d9488';
    }

    /**
     * CSS font-family value when Premium font is set; null = use default.
     */
    public function getFontFamilyCss(): ?string
    {
        $slug = $this->font_family ?? 'default';
        $map = [
            'inter' => '"Inter", ui-sans-serif, system-ui, sans-serif',
            'open-sans' => '"Open Sans", ui-sans-serif, system-ui, sans-serif',
            'roboto' => '"Roboto", ui-sans-serif, system-ui, sans-serif',
            'lora' => '"Lora", ui-serif, Georgia, serif',
            'poppins' => '"Poppins", ui-sans-serif, system-ui, sans-serif',
        ];

        return $map[$slug] ?? null;
    }

    /**
     * Premium font options (slug => label) for Customize web.
     */
    public static function fontFamilyOptions(): array
    {
        return [
            'default' => 'Default (system)',
            'inter' => 'Inter',
            'open-sans' => 'Open Sans',
            'roboto' => 'Roboto',
            'lora' => 'Lora',
            'poppins' => 'Poppins',
        ];
    }

    /**
     * Google Fonts stylesheet URL for the given font slug (null for default).
     */
    public static function fontFamilyGoogleUrl(?string $slug): ?string
    {
        if (! $slug || $slug === 'default') {
            return null;
        }
        $urls = [
            'inter' => 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap',
            'open-sans' => 'https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;500;600;700&display=swap',
            'roboto' => 'https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;600;700&display=swap',
            'lora' => 'https://fonts.googleapis.com/css2?family=Lora:wght@400;500;600;700&display=swap',
            'poppins' => 'https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap',
        ];

        return $urls[$slug] ?? null;
    }

    /**
     * Allowed nav layout values for this plan. Basic = navbar only (no customize). Standard = navbar, dropdown. Premium = navbar, sidebar, dropdown.
     */
    public function getAllowedNavLayouts(): array
    {
        if (! $this->plan) {
            return ['navbar'];
        }
        if ($this->hasFeature('full_web_customization')) {
            return ['navbar', 'sidebar', 'dropdown'];
        }
        if ($this->hasFeature('web_customization')) {
            return ['navbar', 'dropdown'];
        }

        return ['navbar'];
    }

    /**
     * Public URL for the tenant logo, or null (supports Cloudinary and local storage paths).
     */
    public function logoUrl(): ?string
    {
        if (! $this->logo_path) {
            return null;
        }

        return str_contains($this->logo_path, 'cloudinary.com')
            ? $this->logo_path
            : asset('storage/'.$this->logo_path);
    }

    /**
     * @return array<string, string>
     */
    public static function appearanceContentWidthOptions(): array
    {
        return [
            'standard' => 'Standard width',
            'narrow' => 'Narrow (more focused)',
            'wide' => 'Wide (more space)',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function appearanceLogoShapeOptions(): array
    {
        return [
            'circle' => 'Circle (recommended)',
            'rounded' => 'Rounded square',
            'square' => 'Square',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function appearancePageBackgroundOptions(): array
    {
        return [
            'default' => 'Default (light gray)',
            'soft_gray' => 'Soft gray',
            'warm' => 'Warm cream',
            'cool' => 'Cool blue tint',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function appearanceAccentStyleOptions(): array
    {
        return [
            'default' => 'Default bar',
            'flat' => 'Flat (no shadow)',
            'elevated' => 'Elevated (stronger shadow)',
        ];
    }

    /**
     * Defaults merged with saved Premium appearance settings.
     *
     * @return array{content_width: string, logo_shape: string, page_background: string, accent_style: string}
     */
    public function mergedAppearanceSettings(): array
    {
        $defaults = [
            'content_width' => 'standard',
            'logo_shape' => 'circle',
            'page_background' => 'default',
            'accent_style' => 'default',
        ];
        $saved = is_array($this->appearance_settings) ? $this->appearance_settings : [];

        return array_merge($defaults, array_intersect_key($saved, array_flip(array_keys($defaults))));
    }

    /**
     * Tailwind classes for the header logo image (fixed footprint for layout stability).
     */
    public function brandLogoImgClass(): string
    {
        $shape = $this->mergedAppearanceSettings()['logo_shape'] ?? 'circle';
        $base = 'h-8 w-8 shrink-0 object-cover ring-2 ring-white/25';

        return match ($shape) {
            'rounded' => $base.' rounded-lg',
            'square' => $base.' rounded-none',
            default => $base.' rounded-full',
        };
    }

    /**
     * Max-width class for the main content column (staff vs resident use different baselines).
     */
    public function appearanceMainMaxWidthClass(string $portal): string
    {
        $w = $this->mergedAppearanceSettings()['content_width'] ?? 'standard';
        if ($portal === 'resident') {
            return match ($w) {
                'narrow' => 'max-w-3xl',
                'wide' => 'max-w-6xl',
                default => 'max-w-4xl',
            };
        }

        return match ($w) {
            'narrow' => 'max-w-5xl',
            'wide' => 'max-w-screen-2xl',
            default => 'max-w-7xl',
        };
    }

    /**
     * Default resident menu item keys for ordering (Premium can reorder).
     */
    public static function residentNavItemKeys(): array
    {
        return ['dashboard', 'book', 'medicine', 'announcements', 'events', 'profile'];
    }

    /**
     * Check if subscription is expired.
     */
    public function isExpired(): bool
    {
        if (! $this->subscription_ends_at) {
            return false;
        }

        return $this->subscription_ends_at->isPast();
    }

    /**
     * Check if tenant is in grace period.
     */
    public function isInGracePeriod(): bool
    {
        if (! $this->isExpired() || ! $this->grace_period_ends_at) {
            return false;
        }

        return $this->grace_period_ends_at->isFuture();
    }

    /**
     * Check if tenant is past grace period (should be deactivated).
     */
    public function isPastGracePeriod(): bool
    {
        if (! $this->grace_period_ends_at) {
            return false;
        }

        return $this->grace_period_ends_at->isPast();
    }

    /**
     * Get days until expiry (negative if expired).
     */
    public function daysUntilExpiry(): ?int
    {
        if (! $this->subscription_ends_at) {
            return null;
        }

        return now()->diffInDays($this->subscription_ends_at, false);
    }

    /**
     * Get days remaining in grace period (null if not in grace period).
     */
    public function daysRemainingInGracePeriod(): ?int
    {
        if (! $this->isInGracePeriod()) {
            return null;
        }

        return max(0, now()->diffInDays($this->grace_period_ends_at, false));
    }

    /**
     * Get subscription status for display.
     */
    public function getSubscriptionStatus(): string
    {
        if (! $this->subscription_ends_at) {
            return 'active';
        }

        if ($this->isPastGracePeriod()) {
            return 'deactivated';
        }

        if ($this->isInGracePeriod()) {
            return 'grace_period';
        }

        if ($this->isExpired()) {
            return 'expired';
        }

        $daysUntil = $this->daysUntilExpiry();
        if ($daysUntil <= 7) {
            return 'expiring_soon';
        }

        return 'active';
    }
}
