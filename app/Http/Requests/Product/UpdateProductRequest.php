<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
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
        $product = $this->route('product');

        return [
            'category_id' => [
                'required',
                'integer',
                'exists:categories,id',
            ],

            'product_code' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('products', 'product_code')
                    ->ignore($product?->id),
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