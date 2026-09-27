<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInventoryLevelsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return in_array(
            $this->user()?->role,
            [
                'admin',
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
            'par_level' => [
                'required',
                'numeric',
                'gte:0',
            ],

            'reorder_level' => [
                'required',
                'numeric',
                'gte:0',
                'lte:par_level',
            ],
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'par_level.required' =>
                'The PAR level is required.',

            'par_level.numeric' =>
                'The PAR level must be a number.',

            'par_level.gte' =>
                'The PAR level cannot be negative.',

            'reorder_level.required' =>
                'The reorder level is required.',

            'reorder_level.numeric' =>
                'The reorder level must be a number.',

            'reorder_level.gte' =>
                'The reorder level cannot be negative.',

            'reorder_level.lte' =>
                'The reorder level cannot be greater than the PAR level.',
        ];
    }
}