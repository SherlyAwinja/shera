<?php

namespace App\Http\Requests\Front;

use Illuminate\Foundation\Http\FormRequest;

class RequestWalletTopUpRequest extends FormRequest
{
    protected $errorBag = 'walletTopUp';

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'amount' => is_numeric($this->input('amount'))
                ? round((float) $this->input('amount'), 2)
                : $this->input('amount'),
            'note' => filled($this->input('note'))
                ? trim((string) $this->input('note'))
                : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:1'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.min' => 'The minimum wallet top-up request is KSH.1.00.',
        ];
    }
}
