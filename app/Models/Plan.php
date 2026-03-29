<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class Plan extends Model
{
    use CentralConnection;
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'max_appointments_per_month',
        'max_users',
        'has_automated_approval',
        'has_appointment_history',
        'has_monthly_reports',
        'has_inventory_tracking',
        'has_advanced_analytics',
        'has_priority_support',
        'has_data_export',
        'has_email_notifications',
        'has_web_customization',
        'has_full_web_customization',
        'has_announcements_events',
        'price',
    ];

    protected function casts(): array
    {
        return [
            'has_automated_approval' => 'boolean',
            'has_appointment_history' => 'boolean',
            'has_monthly_reports' => 'boolean',
            'has_inventory_tracking' => 'boolean',
            'has_advanced_analytics' => 'boolean',
            'has_priority_support' => 'boolean',
            'has_data_export' => 'boolean',
            'has_email_notifications' => 'boolean',
            'has_web_customization' => 'boolean',
            'has_full_web_customization' => 'boolean',
            'has_announcements_events' => 'boolean',
            'price' => 'decimal:2',
        ];
    }

    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class);
    }

    /**
     * Check if this plan has unlimited appointments (Premium).
     */
    public function isUnlimited(): bool
    {
        return $this->max_appointments_per_month === 0;
    }

    /**
     * Formatted subscription price for public pricing UI (stored `price` is monthly).
     */
    public function formattedPrice(): string
    {
        if ($this->price === null) {
            return __('Contact us');
        }

        $symbol = (string) config('bhcas.currency_symbol', '₱');

        return $symbol.number_format((float) $this->price, 2);
    }

    /**
     * Short limits line for plan comparison (appointments + users).
     */
    public function pricingSummaryLine(): string
    {
        $parts = [];
        if ($this->max_appointments_per_month === 0) {
            $parts[] = __('Unlimited appointments/mo');
        } else {
            $parts[] = __(':count appointments/mo', ['count' => $this->max_appointments_per_month]);
        }

        if ($this->max_users === 0) {
            $parts[] = __('Unlimited users');
        } else {
            $parts[] = __('Up to :count users', ['count' => $this->max_users]);
        }

        return implode(' · ', $parts);
    }
}
