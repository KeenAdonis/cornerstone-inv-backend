<?php

namespace App\Http\Requests\StockIn;

use Illuminate\Foundation\Http\FormRequest;

class StoreStockInRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'warehouse_coordinator';
    }

    public function rules(): array
    {
        return [
            'received_at' => [
                'required',
                'date',
            ],

            'notes' => [
                'nullable',
                'string',
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

    public function messages(): array
    {
        return [
            'received_at.required' => 'The received date and time is required.',
            'received_at.date' => 'The received date and time must be a valid date.',

            'items.required' => 'At least one product is required.',
            'items.min' => 'At least one product is required.',

            'items.*.product_id.required' => 'A product is required.',
            'items.*.product_id.exists' => 'The selected product does not exist.',
            'items.*.product_id.distinct' => 'A product cannot be added more than once.',

            'items.*.quantity.required' => 'A quantity is required.',
            'items.*.quantity.numeric' => 'The quantity must be a number.',
            'items.*.quantity.gt' => 'The quantity must be greater than zero.',
        ];
    }
}