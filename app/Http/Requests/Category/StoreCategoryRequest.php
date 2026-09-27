<?php

namespace App\Http\Requests\Category;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:categories,name',
            ],
            'description' => [
                'nullable',
                'string',
            ],
            'status' => [
                'nullable',
                Rule::in(['active', 'inactive']),
            ],
        ];
    }
}