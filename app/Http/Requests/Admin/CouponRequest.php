<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CouponRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $id = $this->route('coupon') ?? $this->input('id');

        return [
            'coupon_option'       => 'required|string|in:Automatic,Manual,automatic,manual',
            'coupon_code'         => [
                'nullable',
                'string',
                'max:50',
                'required_if:coupon_option,Manual,manual',
                Rule::unique('coupons', 'coupon_code')->ignore($id),
            ],
            'coupon_type'         => 'required|string|in:Single,Multiple,single,multiple',
            'amount_type'         => 'required|string|in:percentage,fixed,Percentage,Fixed',
            'amount'              => 'required|numeric|min:0',
            'expiry_date'         => 'nullable|date',
            'min_qty'             => 'nullable|integer|min:0',
            'max_qty'             => 'nullable|integer|min:0',
            'min_cart_value'      => 'nullable|numeric|min:0',
            'max_cart_value'      => 'nullable|numeric|min:0',
            'usage_limit_per_user'=> 'nullable|integer|min:0',
            'total_usage_limit'   => 'nullable|integer|min:0',
            'max_discount'        => 'nullable|numeric|min:0',
            'visible'             => 'nullable|boolean',
            'status'              => 'nullable|boolean',
            // optional arrays
            'categories'          => 'nullable|array',
            'categories.*'        => 'integer',
            'brands'              => 'nullable|array',
            'brands.*'            => 'integer',
            'users'               => 'nullable|array',
            'users.*'             => 'email',
        ];
    }

    protected function prepareForValidation(): void
    {
        // normalize boolean fields
        $this->merge([
            'visible' => $this->has('visible') ? (int)$this->input('visible') : 0,
            'status'  => $this->has('status') ? (int)$this->input('status') : 0,
        ]);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $minQty = $this->input('min_qty');
            $maxQty = $this->input('max_qty');
            $minCartValue = $this->input('min_cart_value');
            $maxCartValue = $this->input('max_cart_value');

            if ($minQty !== null && $minQty !== '' && $maxQty !== null && $maxQty !== '' && (int) $maxQty < (int) $minQty) {
                $validator->errors()->add('max_qty', 'The max quantity must be greater than or equal to the min quantity.');
            }

            if (
                $minCartValue !== null && $minCartValue !== '' &&
                $maxCartValue !== null && $maxCartValue !== '' &&
                (float) $maxCartValue < (float) $minCartValue
            ) {
                $validator->errors()->add('max_cart_value', 'The max price range must be greater than or equal to the min price range.');
            }
        });
    }
}
