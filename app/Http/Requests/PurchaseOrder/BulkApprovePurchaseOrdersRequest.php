<?php

namespace App\Http\Requests\PurchaseOrder;

use Illuminate\Foundation\Http\FormRequest;

class BulkApprovePurchaseOrdersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'purchase_order_ids' => [
                'required',
                'array',
                'min:1',
            ],
            'purchase_order_ids.*' => [
                'required',
                'integer',
                'distinct',
                'exists:purchase_orders,id',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'purchase_order_ids.required' =>
                'Please select at least one purchase order.',

            'purchase_order_ids.array' =>
                'Invalid purchase order selection.',

            'purchase_order_ids.min' =>
                'Please select at least one purchase order.',

            'purchase_order_ids.*.integer' =>
                'Invalid purchase order ID.',

            'purchase_order_ids.*.distinct' =>
                'Duplicate purchase order IDs are not allowed.',

            'purchase_order_ids.*.exists' =>
                'One or more selected purchase orders do not exist.',
        ];
    }
}