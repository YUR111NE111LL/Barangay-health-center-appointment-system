<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicineAcquisition extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'medicine_id',
        'quantity',
        'unit_price_snapshot',
        'line_total',
        'is_free',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price_snapshot' => 'decimal:2',
            'line_total' => 'decimal:2',
            'is_free' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function medicine(): BelongsTo
    {
        return $this->belongsTo(Medicine::class);
    }
}
