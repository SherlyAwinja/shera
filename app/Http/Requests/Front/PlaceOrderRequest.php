<?php

namespace App\Http\Requests\Front;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PlaceOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $paymentMethods = array_keys(config('checkout.payment.methods', []));

        return [
            'address_id' => ['nullable', 'integer', 'exists:user_addresses,id'],
            'payment_method' => ['required', 'string', Rule::in($paymentMethods)],
        ];
    }

    public function messages(): array
    {
        return [
            'payment_method.required' => 'Choose a payment method before placing the order.',
            'payment_method.in' => 'Select a valid payment method before placing the order.',
        ];
    }
}
