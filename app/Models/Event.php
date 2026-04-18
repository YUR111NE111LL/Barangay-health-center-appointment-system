<?php

namespace App\Models;

use App\Models\Concerns\UsesTenantDatabaseConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class Event extends Model
{
    use UsesTenantDatabaseConnection;

    protected $fillable = [
        'tenant_id',
        'title',
        'description',
        'image_path',
        'event_date',
        'event_time',
        'location',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'is_published' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Scope route model binding to the current tenant so implicit binding never resolves
     * an ID from another barangay when the tenant database is shared or misconfigured.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        $field = $field ?? $this->getRouteKeyName();

        $query = static::query()->where($field, $value);

        if (tenant()) {
            $query->where('tenant_id', (int) tenant()->getTenantKey());
        } elseif (Auth::check() && Auth::user()?->tenant_id !== null) {
            $query->where('tenant_id', Auth::user()->tenant_id);
        }

        return $query->firstOrFail();
    }

    public function getImageUrlAttribute(): ?string
    {
        if (! $this->image_path) {
            return null;
        }

        // If it's a Cloudinary URL, return it directly
        if (str_contains($this->image_path, 'cloudinary.com')) {
            return $this->image_path;
        }

        // Legacy local storage support
        return asset('storage/'.$this->image_path);
    }
}
