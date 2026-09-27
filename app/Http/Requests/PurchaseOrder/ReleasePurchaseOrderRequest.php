<?php

namespace App\Http\Requests\PurchaseOrder;

use Illuminate\Foundation\Http\FormRequest;

class ReleasePurchaseOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->role ===
            'warehouse_coordinator';
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'delivery_type' => [
                'required',
                'in:in_house,trucking,bus,air_cargo,forwarding',
            ],

            'ship_out_date' => [
                'required',
                'date_format:Y-m-d',
            ],
        ];
    }

    /**
     * Get custom validation messages for the request.
     */
    public function messages(): array
    {
        return [
            'delivery_type.required' =>
                'A delivery type is required.',

            'delivery_type.in' =>
                'Please select a valid delivery type.',

            'ship_out_date.required' =>
                'The ship out date is required.',

            'ship_out_date.date_format' =>
                'The ship out date must be a valid date in YYYY-MM-DD format.',
        ];
    }
}