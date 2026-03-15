<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'category_id' => 'required',
            'brand_id' => 'required',
            'product_name' => 'required|max:200',
            'product_code' => 'required|max:30',
            'product_price' => 'required|numeric|gt:0',
            'product_color' => 'required|array|min:1',
            'product_color.*' => 'string|max:200',
            'gender' => 'required|in:men,women,unisex,kids',
            'occasion' => 'required|array|min:1',
            'occasion.*' => 'in:work,cassual,travel,gym',
        ];
    }

    public function messages() {
        return [
            'category_id.required' => 'Category is required.',
            'brand_id.required' => 'Brand is required.',
            'product_name.required' => 'Product name is required.',
            'product_code.required' => 'Product code is required.',
            'product_price.required' => 'Product price is required.',
            'product_price.numeric' => 'Valid product price is required.',
            'product_color.required' => 'Product color is required.',
            'product_color.array' => 'Please select at least one product color.',
            'product_color.min' => 'Please select at least one product color.',
            'gender.required' => 'Gender is required.',
            'gender.in' => 'Please select a valid gender option.',
            'occasion.required' => 'Occasion is required.',
            'occasion.array' => 'Please select at least one valid occasion.',
            'occasion.min' => 'Please select at least one occasion.',
            'occasion.*.in' => 'Please select a valid occasion option.',
        ];
    }
}
