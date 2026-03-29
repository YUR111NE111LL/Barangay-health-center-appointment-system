<?php

namespace App\Http\Requests;

use App\Models\TenantApplication;
use App\Support\TenantDomainInput;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ApproveTenantApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'domain' => TenantDomainInput::normalizeDomain((string) $this->input('domain', '')),
        ]);
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var TenantApplication $application */
        $application = $this->route('tenant_application');

        return [
            'domain' => [
                'required',
                'string',
                'max:255',
                Rule::unique('domains', 'domain'),
                Rule::unique('tenant_applications', 'domain')
                    ->where('status', TenantApplication::STATUS_PENDING)
                    ->ignore($application->id),
            ],
        ];
    }
}
