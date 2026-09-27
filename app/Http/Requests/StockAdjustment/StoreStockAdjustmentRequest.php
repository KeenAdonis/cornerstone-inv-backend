<?php

namespace App\Http\Requests\StockAdjustment;

use Illuminate\Foundation\Http\FormRequest;

class StoreStockAdjustmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make the request.
     */
    public function authorize(): bool
    {
        return in_array(
            $this->user()?->role,
            [
                'warehouse_coordinator',
                'branch_coordinator',
            ],
            true
        );
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'type' => [
                'required',
                'in:increase,decrease',
            ],

            'reason' => [
                'required',
                'string',
                'max:255',
            ],

            'adjusted_at' => [
                'required',
                'date',
            ],

            'notes' => [
                'nullable',
                'string',
            ],

            'branch_id' => [
                'nullable',
                'integer',
                'exists:branches,id',
            ],

            'warehouse_id' => [
                'nullable',
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
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'type.required' =>
                'The adjustment type is required.',

            'type.in' =>
                'The adjustment type must be either increase or decrease.',

            'reason.required' =>
                'The adjustment reason is required.',

            'reason.string' =>
                'The adjustment reason must be valid text.',

            'reason.max' =>
                'The adjustment reason may not exceed 255 characters.',

            'adjusted_at.required' =>
                'The adjustment date and time is required.',

            'adjusted_at.date' =>
                'The adjustment date and time must be a valid date.',

            'notes.string' =>
                'The notes must be valid text.',

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