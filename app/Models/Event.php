<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Event extends Model
{
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
