<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class DetailRequest extends FormRequest
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
            'name' => 'required|regex:/^[\pL\s\-]+$/u|max:255',
            'mobile' => 'required|numeric|digits:10',
            'image' => 'image',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Name is required',
            'name.regex' => 'Valid name is required',
            'name.max' => 'Valid name is required',
            'mobile.required' => 'Mobile number is required',
            'mobile.numeric' => 'Valid mobile number is required',
            'mobile.digits' => 'Valid mobile number is required',
            'image.image' => 'Valid image is required',
        ];
    }
}
