<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class BannerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // You can addrole-based logic if needed
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules =[
            'type' => 'required|string|max:255',
            'link' => 'nullable|url|max:500',
            'title'=> 'required|string|max:255',
            'alt'=> 'nullable|string|max:255',
            'sort'=> 'required|integer|min:0',
            'status' => 'nullable|in:0,1',
        ];

        //For create or edit - validate image only if uploading
        if ($this->isMethod('post') || $this->hasFile('image')) {
            $rules['image'] = 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048';// Max 2MB
        }
        return $rules;
    }

    /**
     * Custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'type.required' => 'Please select the banner type.',
            'title.required' => 'Please enter the banner title.',
            'image.required' => 'Please upload a banner image.',
            'image.image' => 'The uploaded file must be an image.',
            'image.mimes' => 'The banner image must be a file of type: jpeg, png, jpg, gif, svg.',
            'image.max' => 'The banner image must be less than 2MB.',
        ];
    }
}
