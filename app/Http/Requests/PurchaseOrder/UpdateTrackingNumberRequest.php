<?php

namespace App\Http\Requests\PurchaseOrder;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTrackingNumberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role ===
            'warehouse_coordinator';
    }

    public function rules(): array
    {
        return [
            'tracking_number' => [
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'tracking_number.string' =>
                'The tracking number must be a valid text value.',

            'tracking_number.max' =>
                'The tracking number may not exceed 255 characters.',
        ];
    }
}
