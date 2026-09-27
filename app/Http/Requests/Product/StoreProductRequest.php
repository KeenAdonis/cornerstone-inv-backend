<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'category_id' => [
                'required',
                'integer',
                'exists:categories,id',
            ],

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'sku' => [
                'required',
                'string',
                'max:255',
                'unique:products,sku',
            ],

            'unit' => [
                'required',
                'string',
                'max:50',
            ],

            'srp' => [
                'required',
                'numeric',
                'min:0',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'status' => [
                'nullable',
                'in:active,inactive',
            ],
        ];
    }
}