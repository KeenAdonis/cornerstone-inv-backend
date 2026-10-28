<?php

namespace App\Http\Requests\PurchaseOrder;

use Illuminate\Foundation\Http\FormRequest;

class RejectPurchaseOrderDuringDeliveryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'warehouse_coordinator';
    }

    public function rules(): array
    {
        return [
            'rejection_reason' => [
                'required',
                'string',
                'max:1000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'rejection_reason.required' =>
                'A rejection reason is required.',

            'rejection_reason.string' =>
                'The rejection reason must be valid text.',

            'rejection_reason.max' =>
                'The rejection reason may not exceed 1000 characters.',
        ];
    }
}
