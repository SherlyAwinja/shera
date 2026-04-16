<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
        $rules = [
            'category_id' => 'required',
            'brand_id' => 'required',
            'product_name' => 'required|max:200',
            'product_code' => 'required|max:30',
            'product_price' => 'required|numeric|gt:0',
            'variants' => 'required|array|min:1',
            'variants.*.size' => 'required|string|max:100',
            'variants.*.color' => 'required|string|max:200',
            'variants.*.stock' => 'required|integer|min:0',
            'gender' => 'required|in:men,women,unisex,kids',
            'occasion' => 'required|array|min:1',
            'occasion.*' => 'in:work,cassual,travel,gym',
        ];

        $productId = $this->route('product');
        if($this->isMethod('post')) {
            $rules['product_url']=[
                'nullable',
                Rule::unique('products', 'product_url'),
            ];
        } elseif ($this->isMethod('put')||$this->isMethod('patch')) {
            $rules['product_url'] = [
                'required',
                Rule::unique('products', 'product_url')->ignore($productId),
            ];
        }
        return $rules;
    }

    public function messages() {
        return [
            'category_id.required' => 'Category is required.',
            'brand_id.required' => 'Brand is required.',
            'product_name.required' => 'Product name is required.',
            'product_code.required' => 'Product code is required.',
            'product_price.required' => 'Product price is required.',
            'product_price.numeric' => 'Valid product price is required.',
            'variants.required' => 'Add at least one product variant.',
            'variants.array' => 'Variants must be submitted as a valid list.',
            'variants.min' => 'Add at least one product variant.',
            'variants.*.size.required' => 'Each variant must include a size.',
            'variants.*.color.required' => 'Each variant must include a color.',
            'variants.*.stock.required' => 'Each variant must include a quantity.',
            'variants.*.stock.integer' => 'Each variant quantity must be a whole number.',
            'variants.*.stock.min' => 'Each variant quantity must be zero or greater.',
            'gender.required' => 'Gender is required.',
            'gender.in' => 'Please select a valid gender option.',
            'occasion.required' => 'Occasion is required.',
            'occasion.array' => 'Please select at least one valid occasion.',
            'occasion.min' => 'Please select at least one occasion.',
            'occasion.*.in' => 'Please select a valid occasion option.',
            'product_url.required' => 'Product URL is required when updating',
            'product_url.unique' => 'Product URL must be unique',
        ];
    }

    protected function prepareForValidation(): void
    {
        $variants = $this->input('variants');

        if (!is_array($variants)) {
            return;
        }

        $normalizedVariants = [];

        foreach ($variants as $variant) {
            if (!is_array($variant)) {
                continue;
            }

            $size = trim((string) ($variant['size'] ?? ''));
            $color = trim((string) ($variant['color'] ?? ''));
            $stock = $variant['stock'] ?? null;

            if ($size === '' && $color === '' && ($stock === null || $stock === '')) {
                continue;
            }

            $normalizedVariants[] = [
                'size' => $size,
                'color' => $color,
                'stock' => $stock,
            ];
        }

        $this->merge([
            'variants' => $normalizedVariants,
        ]);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $variants = $this->input('variants', []);
            $seen = [];

            foreach ($variants as $index => $variant) {
                $size = strtolower(trim((string) ($variant['size'] ?? '')));
                $color = strtolower(trim((string) ($variant['color'] ?? '')));

                if ($size === '' || $color === '') {
                    continue;
                }

                $variantKey = $size . '|' . $color;

                if (isset($seen[$variantKey])) {
                    $validator->errors()->add(
                        'variants.' . $index . '.color',
                        'Duplicate variant combination detected. Each size and color pair must be unique.'
                    );
                }

                $seen[$variantKey] = true;
            }
        });
    }
}
