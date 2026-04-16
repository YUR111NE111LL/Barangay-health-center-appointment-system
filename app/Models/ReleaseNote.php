<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class ReleaseNote extends Model
{
    use CentralConnection;

    protected $fillable = [
        'tenant_id',
        'created_by',
        'title',
        'summary',
        'content',
        'version',
        'type',
        'is_pinned',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'is_pinned' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(CentralUser::class, 'created_by');
    }
}
