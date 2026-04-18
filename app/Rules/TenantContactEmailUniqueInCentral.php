<?php

namespace App\Rules;

use App\Models\Tenant;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Ensures the contact email is not already stored on another tenant (central `tenants.email`).
 */
final class TenantContactEmailUniqueInCentral implements ValidationRule
{
    public function __construct(
        private ?int $ignoreTenantId = null
    ) {}

    /**
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || trim($value) === '') {
            return;
        }

        $normalized = strtolower(trim($value));

        $query = Tenant::query()->whereRaw('LOWER(TRIM(email)) = ?', [$normalized]);

        if ($this->ignoreTenantId !== null) {
            $query->where('id', '!=', $this->ignoreTenantId);
        }

        if ($query->exists()) {
            $fail(__('This email is already used by an existing barangay site. Use a different email or contact support.'));
        }
    }
}
