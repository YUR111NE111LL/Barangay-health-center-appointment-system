<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RejectAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasTenantPermission('approve appointments') ?? false;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'rejection_reason' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
