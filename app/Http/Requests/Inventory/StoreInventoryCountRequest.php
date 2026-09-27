<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class StoreInventoryCountRequest extends FormRequest
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

            'items' => [
                'required',
                'array',
                'min:1',
            ],

            'items.*.inventory_id' => [
                'required',
                'integer',
                'distinct',
                'exists:inventories,id',
            ],

            'items.*.counted_quantity' => [
                'required',
                'numeric',
                'min:0',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ];
    }
}