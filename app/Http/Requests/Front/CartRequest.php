<?php

namespace App\Http\Requests\Front;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CartRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // allow guest too
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $routeName = $this->route() ? $this->route()->getName() : null;
        $method = $this->method();

        $rules = [];

        // Store (Add to Cart)
        if ($routeName === 'cart.store' || ($method === 'POST' && $this->routeIs('cart.store'))) {
            $rules = [
                'product_id' => 'required|exists:products,id',
                'size'       => 'nullable|string',  // Optional for quick-add buttons
                'color'      => 'nullable|string|max:200',
                'qty'        => 'required|integer|min:1',
                'replace_qty' => 'nullable|boolean',
            ];
        }

        // Update Cart
        elseif ($routeName === 'cart.update' || ($method === 'PATCH' && $this->routeIs('cart.update'))) {
            $rules = [
                'qty' => 'required|integer|min:1',
                'size' => 'nullable|string',
                'color' => 'nullable|string|max:200',
                // cart id comes from route (validated via route model binding if used)
            ];
        }

        // Delete Cart Item
        elseif ($routeName === 'cart.destroy' || ($method === 'DELETE' && $this->routeIs('cart.destroy'))) {
            $rules = [
                // no fields required
            ];
        }

        return $rules;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('size')) {
            $this->merge([
                'size' => trim((string) $this->input('size')) ?: null,
            ]);
        }

        if ($this->has('color')) {
            $this->merge([
                'color' => trim((string) $this->input('color')) ?: null,
            ]);
        }

        if (!$this->exists('replace_qty')) {
            return;
        }

        $replaceQty = $this->input('replace_qty');

        if (is_array($replaceQty)) {
            return;
        }

        $normalizedReplaceQty = filter_var(
            $replaceQty,
            FILTER_VALIDATE_BOOLEAN,
            FILTER_NULL_ON_FAILURE
        );

        if ($normalizedReplaceQty !== null) {
            $this->merge([
                'replace_qty' => $normalizedReplaceQty,
            ]);
        }
    }

    public function messages(): array
    {
        return [
            'product_id.required' => 'Product is required.',
            'product_id.exists'   => 'Product not found.',
            'size.required'       => 'Please select a size.',
            'color.string'        => 'Please select a valid color.',
            'qty.required'        => 'Please enter quantity.',
            'qty.integer'         => 'Quantity must be a number.',
            'qty.min'             => 'Quantity must be at least 1.',
        ];
    }
}
