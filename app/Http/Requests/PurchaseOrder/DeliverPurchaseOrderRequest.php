<?php

namespace App\Http\Requests\PurchaseOrder;

use Illuminate\Foundation\Http\FormRequest;

class DeliverPurchaseOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->role ===
            'branch_coordinator';
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'delivery_photo' => [
                'nullable',
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
                'required_without:delivery_photos',
            ],

            'delivery_photos' => [
                'nullable',
                'array',
                'min:1',
                'required_without:delivery_photo',
            ],

            'delivery_photos.*' => [
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],

            'date_of_arrival' => [
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
            'delivery_photo.required_without' =>
                'At least one proof of delivery photo is required.',

            'delivery_photo.image' =>
                'The proof of delivery must be a valid image.',

            'delivery_photo.mimes' =>
                'The proof of delivery must be a JPG, JPEG, PNG, or WEBP image.',

            'delivery_photo.max' =>
                'The proof of delivery image must not exceed 5 MB.',

            'delivery_photos.required_without' =>
                'At least one proof of delivery photo is required.',

            'delivery_photos.array' =>
                'The proof of delivery attachments must be a valid list of files.',

            'delivery_photos.min' =>
                'At least one proof of delivery photo is required.',

            'delivery_photos.*.file' =>
                'Each proof of delivery attachment must be a valid file.',

            'delivery_photos.*.image' =>
                'Each proof of delivery attachment must be a valid image.',

            'delivery_photos.*.mimes' =>
                'Each proof of delivery attachment must be a JPG, JPEG, PNG, or WEBP image.',

            'delivery_photos.*.max' =>
                'Each proof of delivery attachment must not exceed 5 MB.',

            'date_of_arrival.required' =>
                'The date of arrival is required.',

            'date_of_arrival.date_format' =>
                'The date of arrival must be a valid date in YYYY-MM-DD format.',
        ];
    }
}