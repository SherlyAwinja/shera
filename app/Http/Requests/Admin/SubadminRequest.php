<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Models\Admin;

class SubadminRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'name' => 'required',
            'mobile' => 'required|numeric',
            'image' => 'nullable|image',
        ];
        
        // Email is required only when creating (id is empty)
        if ($this->input('id') == "" || !$this->has('id')) {
            $rules['email'] = 'required|email';
            $rules['password'] = 'required|min:6';
        } else {
            $rules['email'] = 'required|email';
            $rules['password'] = 'nullable|min:6';
        }
        
        return $rules;
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Name is required',
            'mobile.required' => 'Mobile is required',
            'mobile.numeric' => 'Valid Mobile Number is required',
            'image.image' => 'Valid Image is required',
            'email.required' => 'Email is required',
            'email.email' => 'Valid Email is required',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->input('id') == "") {
                $subadminCount = Admin::where('email', $this->input('email'))->count();
                if ($subadminCount > 0) {
                    $validator->errors()->add('email', 'Subadmin already exists');
                }
            }
        });
    }

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(redirect()->back()->withErrors($validator)->withInput());
    }
}
