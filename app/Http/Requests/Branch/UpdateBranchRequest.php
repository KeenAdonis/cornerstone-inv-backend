<?php

namespace App\Http\Requests\Branch;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBranchRequest extends FormRequest
{
    /**
     * Determine whether the user is authorized
     * to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply
     * to the request.
     */
    public function rules(): array
    {
        $branchId = $this->route('branch')->id;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('branches', 'code')
                    ->ignore($branchId),
            ],

            'area' => [
                'required',
                Rule::in([
                    'luzon',
                    'visayas',
                    'mindanao',
                ]),
            ],

            'address' => [
                'required',
                'string',
            ],

            'status' => [
                'nullable',
                Rule::in([
                    'active',
                    'inactive',
                ]),
            ],
        ];
    }
}