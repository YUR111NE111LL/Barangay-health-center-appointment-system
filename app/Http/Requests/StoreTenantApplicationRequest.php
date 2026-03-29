<?php

namespace App\Http\Requests;

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
        $this->merge([
            'domain' => null,
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
            'email' => ['required', 'email', 'max:255'],
        ];

        if (Recaptcha::shouldProcess()) {
            $rules['recaptcha_token'] = ['required', 'string'];
        }

        return $rules;
    }
}
