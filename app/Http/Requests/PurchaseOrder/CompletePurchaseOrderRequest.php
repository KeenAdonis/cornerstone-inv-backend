<?php

namespace App\Http\Requests\PurchaseOrder;

use Illuminate\Foundation\Http\FormRequest;

class CompletePurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'warehouse_coordinator';
    }

    public function rules(): array
    {
        return [];
    }
}