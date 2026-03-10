<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Announcement extends Model
{
    protected $fillable = [
        'tenant_id',
        'title',
        'body',
        'image_path',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
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
