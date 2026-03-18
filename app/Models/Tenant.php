<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Stancl\Tenancy\Contracts\Tenant as TenantContract;
use Stancl\Tenancy\Database\Concerns\CentralConnection;
use Stancl\Tenancy\Database\Concerns\TenantRun;
use Stancl\Tenancy\Database\Models\Domain;

class Tenant extends Model implements TenantContract
{
    use CentralConnection;
    use HasFactory;
    use TenantRun;

    /**
     * Tenants are not scoped by tenant_id (they define the tenant).
     * Do not use BelongsToTenant on this model.
     */

    protected static function booted(): void
    {
        static::created(function (Tenant $tenant): void {
            \App\Services\TenantRbacSeeder::seedTenant($tenant->id);
        });
    }

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

    public function getInternal(string $key): mixed
    {
        $data = $this->getAttribute('data') ?? [];

        return $data['tenancy_' . $key] ?? null;
    }

    public function setInternal(string $key, mixed $value): static
    {
        $data = $this->getAttribute('data') ?? [];
        $data['tenancy_' . $key] = $value;
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
     * Default resident menu item keys for ordering (Premium can reorder).
     */
    public static function residentNavItemKeys(): array
    {
        return ['dashboard', 'book', 'announcements', 'events', 'profile'];
    }

    /**
     * Check if subscription is expired.
     */
    public function isExpired(): bool
    {
        if (!$this->subscription_ends_at) {
            return false;
        }
        return $this->subscription_ends_at->isPast();
    }

    /**
     * Check if tenant is in grace period.
     */
    public function isInGracePeriod(): bool
    {
        if (!$this->isExpired() || !$this->grace_period_ends_at) {
            return false;
        }
        return $this->grace_period_ends_at->isFuture();
    }

    /**
     * Check if tenant is past grace period (should be deactivated).
     */
    public function isPastGracePeriod(): bool
    {
        if (!$this->grace_period_ends_at) {
            return false;
        }
        return $this->grace_period_ends_at->isPast();
    }

    /**
     * Get days until expiry (negative if expired).
     */
    public function daysUntilExpiry(): ?int
    {
        if (!$this->subscription_ends_at) {
            return null;
        }
        return now()->diffInDays($this->subscription_ends_at, false);
    }

    /**
     * Get days remaining in grace period (null if not in grace period).
     */
    public function daysRemainingInGracePeriod(): ?int
    {
        if (!$this->isInGracePeriod()) {
            return null;
        }
        return max(0, now()->diffInDays($this->grace_period_ends_at, false));
    }

    /**
     * Get subscription status for display.
     */
    public function getSubscriptionStatus(): string
    {
        if (!$this->subscription_ends_at) {
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
