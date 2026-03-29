<?php

namespace App\Models;

use App\Events\AppointmentSaved;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model
{
    use BelongsToTenant, HasFactory;

    protected static function booted(): void
    {
        static::updated(function (Appointment $appointment): void {
            if ($appointment->wasChanged('status')) {
                event(new AppointmentSaved($appointment));
            }
        });
        static::created(function (Appointment $appointment): void {
            event(new AppointmentSaved($appointment));
        });
    }

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_NO_SHOW = 'no_show';

    public static function statuses(): array
    {
        return [
            'Pending' => self::STATUS_PENDING,
            'Approved' => self::STATUS_APPROVED,
            'Completed' => self::STATUS_COMPLETED,
            'Cancelled' => self::STATUS_CANCELLED,
            'No Show' => self::STATUS_NO_SHOW,
        ];
    }

    protected $fillable = [
        'tenant_id',
        'user_id',
        'service_id',
        'scheduled_date',
        'scheduled_time',
        'status',
        'complaint',
        'notes',
        'rejection_reason',
        'approved_at',
        'approved_by',
        'visited_at',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_date' => 'date',
            'approved_at' => 'datetime',
            'visited_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Alias for the resident/patient who booked the appointment. */
    public function resident(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Scope: appointments scheduled for today.
     */
    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('scheduled_date', today());
    }

    /**
     * Scope: pending status.
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope: approved status.
     */
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_APPROVED);
    }
}
