<?php

namespace App\Http\Requests\SuperAdmin;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePlanPriceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return (bool) $this->user()?->isSuperAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'price' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
        ];
    }

    public function messages(): array
    {
        return [
            'price.required' => __('Please enter a monthly price.'),
            'price.numeric' => __('Plan price must be a valid number.'),
            'price.min' => __('Plan price cannot be negative.'),
            'price.decimal' => __('Plan price can have up to 2 decimal places only.'),
        ];
    }
}
