<?php

namespace App\Http\Requests\PurchaseOrder;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewPurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'action' => [
                'required',
                'string',
                Rule::in([
                    'approve',
                    'reject',
                ]),
            ],

            'rejection_reason' => [
                'nullable',
                'string',
                'required_if:action,reject',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'action.required' =>
                'The review action is required.',

            'action.in' =>
                'The review action must be approve or reject.',

            'rejection_reason.required_if' =>
                'A rejection reason is required when rejecting a purchase order.',
        ];
    }
}