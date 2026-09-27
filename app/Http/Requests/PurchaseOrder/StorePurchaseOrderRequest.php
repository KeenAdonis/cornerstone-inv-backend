<?php

namespace App\Http\Requests\PurchaseOrder;

use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'branch_coordinator';
    }

    public function rules(): array
    {
        return [
            'branch_id' => [
                'required',
                'integer',
                'exists:branches,id',
            ],

            'warehouse_id' => [
                'required',
                'integer',
                'exists:warehouses,id',
            ],

            'items' => [
                'required',
                'array',
                'min:1',
            ],

            'items.*.product_id' => [
                'required',
                'integer',
                'exists:products,id',
                'distinct',
            ],

            'items.*.quantity' => [
                'required',
                'numeric',
                'gt:0',
            ],

            'notes' => [
                'nullable',
                'string',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'branch_id.required' =>
                'The branch is required.',

            'branch_id.integer' =>
                'The selected branch is invalid.',

            'branch_id.exists' =>
                'The selected branch does not exist.',

            'warehouse_id.required' =>
                'The warehouse is required.',

            'warehouse_id.integer' =>
                'The selected warehouse is invalid.',

            'warehouse_id.exists' =>
                'The selected warehouse does not exist.',

            'items.required' =>
                'At least one product is required.',

            'items.min' =>
                'At least one product is required.',

            'items.*.product_id.required' =>
                'A product is required.',

            'items.*.product_id.exists' =>
                'The selected product does not exist.',

            'items.*.product_id.distinct' =>
                'A product cannot be added more than once.',

            'items.*.quantity.required' =>
                'A quantity is required.',

            'items.*.quantity.numeric' =>
                'The quantity must be a number.',

            'items.*.quantity.gt' =>
                'The quantity must be greater than zero.',
        ];
    }
}