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

            'tracking_number' => [
                'nullable',
                'string',
                'max:255',
                'required_unless:delivery_type,in_house',
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

            'tracking_number.required_unless' =>
                'A tracking number is required for this delivery type.',

            'tracking_number.string' =>
                'The tracking number must be a valid text value.',

            'tracking_number.max' =>
                'The tracking number may not exceed 255 characters.',

            'ship_out_date.required' =>
                'The ship out date is required.',

            'ship_out_date.date_format' =>
                'The ship out date must be a valid date in YYYY-MM-DD format.',
        ];
    }
}