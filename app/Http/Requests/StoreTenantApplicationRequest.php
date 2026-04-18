<?php

namespace App\Http\Requests;

use App\Models\TenantApplication;
use App\Rules\TenantContactEmailUniqueInCentral;
use App\Support\Recaptcha;
use Illuminate\Foundation\Http\FormRequest;

class StoreTenantApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $email = $this->input('email');
        $normalized = is_string($email) ? strtolower(trim($email)) : '';

        $this->merge([
            'domain' => null,
            'email' => $normalized !== '' ? $normalized : $email,
        ]);
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'plan_id' => ['required', 'exists:plans,id'],
            'name' => ['required', 'string', 'max:255'],
            'barangay' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'contact_number' => ['nullable', 'string', 'max:50'],
            'email' => [
                'required',
                'email',
                'max:255',
                new TenantContactEmailUniqueInCentral,
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $normalized = strtolower(trim((string) $value));
                    if ($normalized === '') {
                        return;
                    }
                    $blocked = TenantApplication::query()
                        ->whereRaw('LOWER(TRIM(email)) = ?', [$normalized])
                        ->whereIn('status', [
                            TenantApplication::STATUS_PENDING,
                            TenantApplication::STATUS_APPROVED,
                        ])
                        ->exists();
                    if ($blocked) {
                        $fail(__('This email already has a barangay application. You cannot apply again while it is pending or after it has been approved. Use a different email or contact support if you need help.'));
                    }
                },
            ],
        ];

        if (Recaptcha::shouldProcess()) {
            $rules['recaptcha_token'] = ['required', 'string'];
        }

        return $rules;
    }
}
