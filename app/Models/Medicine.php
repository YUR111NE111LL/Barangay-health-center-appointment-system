<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Medicine extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'quantity',
        'is_free',
        'price_per_unit',
        'image_path',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'is_free' => 'boolean',
            'price_per_unit' => 'decimal:2',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function acquisitions(): HasMany
    {
        return $this->hasMany(MedicineAcquisition::class);
    }

    /** Whether residents are charged per unit for this listing (admin sets price). */
    public function isPricedSupply(): bool
    {
        return ! $this->is_free
            && $this->price_per_unit !== null
            && (float) $this->price_per_unit > 0;
    }

    public function getImageUrlAttribute(): ?string
    {
        if (! $this->image_path) {
            return null;
        }
        if (str_contains($this->image_path, 'cloudinary.com')) {
            return $this->image_path;
        }

        return asset('storage/'.$this->image_path);
    }

    public function isOutOfStock(): bool
    {
        return $this->quantity < 1;
    }
}
