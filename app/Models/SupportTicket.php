<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class SupportTicket extends Model
{
    use CentralConnection;

    public const STATUS_OPEN = 'open';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_RESOLVED = 'resolved';

    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'reporter_name',
        'reporter_email',
        'ticket_no',
        'category',
        'priority',
        'subject',
        'description',
        'attachment_path',
        'status',
        'assigned_to',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SupportTicketMessage::class, 'ticket_id');
    }

    public function getAttachmentUrlAttribute(): ?string
    {
        if (! $this->attachment_path) {
            return null;
        }

        return asset('storage/'.$this->attachment_path);
    }

    public static function statusLabel(?string $status): string
    {
        return match ($status) {
            self::STATUS_OPEN => 'Pending',
            self::STATUS_IN_PROGRESS => 'Fixing',
            self::STATUS_RESOLVED, self::STATUS_CLOSED => 'Done',
            default => 'Pending',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function superAdminStatusChoices(): array
    {
        return [
            'pending' => self::STATUS_OPEN,
            'fixing' => self::STATUS_IN_PROGRESS,
            'done' => self::STATUS_RESOLVED,
        ];
    }

    public static function superAdminKeyFromStatus(string $status): string
    {
        return match ($status) {
            self::STATUS_IN_PROGRESS => 'fixing',
            self::STATUS_RESOLVED, self::STATUS_CLOSED => 'done',
            default => 'pending',
        };
    }
}
