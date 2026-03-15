<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'sku' => [
                'required',
                'string',
                Rule::unique('products', 'sku')->ignore($this->route('product')?->id)
            ],
            'cost_price' => 'required|numeric|min:0',
            'sale_price' => 'required|numeric|min:0',
            'tax_percentage' => 'required|numeric|min:0|max:100',
            'status' => 'required|in:active,inactive'
        ];
    }
}